package vn.hosoweb.service;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.microsoft.playwright.*;
import org.apache.logging.log4j.LogManager;
import org.apache.logging.log4j.Logger;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.Project;
import vn.hosoweb.util.HibernateUtil;
import vn.hosoweb.util.Urls;

import java.nio.file.Path;
import java.nio.file.Paths;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.concurrent.atomic.AtomicReference;

/**
 * Chạy hàng loạt trên trình duyệt thật (Chrome trên máy đang chạy ứng dụng).
 * <p>Với mỗi nền tảng còn cần xử lý: mở trang đăng ký, tự điền thông tin dự án, rồi chờ người dùng
 * (nhập mật khẩu, giải CAPTCHA, bấm Gửi, xác minh email) bấm một nút trên khung nổi:
 * "Đây là trang hồ sơ" (lưu URL hiện tại, Hoàn tất), "Chờ xác minh", "Chờ CAPTCHA", "Bỏ qua" hoặc "Dừng".
 * Không tự bấm gửi, không điền mật khẩu, không đụng CAPTCHA.
 *
 * <p>Mỗi máy chạy một lượt tại một thời điểm. Hồ sơ trình duyệt lưu ở {@code data/browser-profile}
 * nên các đăng nhập (VD: Google) được giữ giữa các lượt.
 */
public final class Runner {
    private static final Logger LOG = LogManager.getLogger(Runner.class);
    private static final Runner INSTANCE = new Runner();
    private static final ObjectMapper JSON = new ObjectMapper();
    private static final DateTimeFormatter T = DateTimeFormatter.ofPattern("HH:mm:ss");

    public static Runner get() { return INSTANCE; }

    /** Ảnh chụp trạng thái để hiển thị trên giao diện. */
    public record Status(boolean running, Long projectId, Long ownerId, String current, int index, int total,
                         int done, int waiting, int skipped, List<String> log) { }

    private record Decision(String action, String url, String username, String step) { }

    private volatile boolean running;
    private volatile boolean stopRequested;
    private volatile Long projectId, ownerId;
    private volatile String current;
    /** Username đang dùng cho nền tảng hiện tại (nhớ qua các lần chuyển trang). */
    private volatile String currentUser;
    private volatile int index, total, done, waiting, skipped;
    private final Deque<String> log = new ArrayDeque<>();
    private final AtomicReference<Decision> decision = new AtomicReference<>();
    /** Trang đang xử lý — chỉ nhận lệnh từ trang này. */
    private volatile Page currentPage;
    /** Mã bí mật của lượt chạy: chỉ khung điều khiển do ứng dụng chèn mới biết, trang web không đọc được. */
    private volatile String token;

    private Runner() { }

    public synchronized Status status() {
        return new Status(running, projectId, ownerId, current, index, total, done, waiting, skipped, new ArrayList<>(log));
    }

    private synchronized void log(String msg) {
        log.addFirst(LocalDateTime.now().format(T) + "  " + msg);
        while (log.size() > 40) log.removeLast();
        LOG.info(msg);
    }

    public void stop() { stopRequested = true; }

    /**
     * Bắt đầu một lượt chạy. Trả về null nếu bắt đầu được, ngược lại là lý do.
     * @param mode null = mọi mục chưa xong, "auto" = không CAPTCHA, "captcha" = chỉ mục CAPTCHA
     */
    public synchronized String start(long owner, long project, String mode, String autofillJs) {
        if (running) return "Đang có một lượt chạy khác. Dừng lượt đó trước.";
        int[] missing = {0};
        List<Long> ids = HibernateUtil.tx(s -> {
            List<Platform> l = s.createQuery("select pl from Platform pl where pl.project.id = :p and pl.project.owner.id = :o "
                            + "and pl.status in (:st) order by pl.position, pl.id", Platform.class)
                    .setParameter("p", project).setParameter("o", owner)
                    .setParameterList("st", Platform.PENDING).list();
            if ("auto".equals(mode)) l.removeIf(Platform::isCaptchaManual);
            else if ("captcha".equals(mode)) l.removeIf(p -> !p.isCaptchaManual());
            int before = l.size();
            l.removeIf(p -> !Urls.isHttpUrl(p.isOwnedProfile() ? p.getExistingProfileUrl() : p.getSignupUrl()));
            missing[0] = before - l.size();
            return l.stream().map(Platform::getId).toList();
        });
        if (ids.isEmpty()) return "Không có mục nào cần xử lý (hoặc các mục còn lại thiếu URL đăng ký).";
        running = true;
        stopRequested = false;
        projectId = project;
        ownerId = owner;
        index = 0; total = ids.size(); done = 0; waiting = 0; skipped = 0;
        current = null;
        log.clear();
        if (missing[0] > 0) log(missing[0] + " mục thiếu URL đăng ký nên không chạy — bổ sung URL ở màn hình Tạo.");
        token = UUID.randomUUID().toString();
        Thread t = new Thread(() -> runSafely(owner, project, ids, autofillJs), "hosoweb-runner");
        t.setDaemon(true);
        t.start();
        return null;
    }

    private void runSafely(long owner, long project, List<Long> ids, String autofillJs) {
        try {
            run(owner, project, ids, autofillJs);
        } catch (Throwable e) {
            LOG.error("Runner lỗi", e);
            log("Lỗi: " + e.getMessage());
        } finally {
            current = null;
            running = false;
            log("Kết thúc lượt chạy: " + done + " hoàn tất, " + waiting + " đang chờ, " + skipped + " bỏ qua.");
        }
    }

    private void run(long owner, long project, List<Long> ids, String autofillJs) throws Exception {
        Project prj = HibernateUtil.tx(s -> s.createQuery("from Project where id = :id and owner.id = :o", Project.class)
                .setParameter("id", project).setParameter("o", owner).uniqueResult());
        if (prj == null) { log("Không tìm thấy dự án."); return; }
        String data = JSON.writeValueAsString(autofillData(prj));

        log("Mở trình duyệt…");
        try (Playwright pw = createPlaywright(); BrowserContext ctx = launch(pw)) {
            ctx.exposeBinding("__hosowebAction", (source, args) -> {
                Map<?, ?> m = args.length > 0 && args[0] instanceof Map<?, ?> mm ? mm : Map.of();
                if (!token.equals(str(m.get("t"))) || source.page() != currentPage) return false; // bỏ qua lệnh giả từ trang web
                String u = str(m.get("username"));
                if (u == null || u.isBlank()) u = currentUser;
                decision.set(new Decision(str(m.get("action")), str(m.get("url")), u, str(m.get("step"))));
                return true;
            });
            ctx.exposeBinding("__hosowebUserSet", (source, args) -> {
                if (source.page() != currentPage) return false;
                if (args.length > 0 && args[0] != null && !args[0].toString().isBlank()) currentUser = args[0].toString();
                return true;
            });
            ctx.exposeBinding("__hosowebInfo", (source, args) -> {
                Map<String, Object> m = new LinkedHashMap<>();
                m.put("name", current);
                m.put("index", index);
                m.put("total", total);
                return m;
            });
            ctx.addInitScript(overlayScript(autofillJs, data, token));

            for (Long id : ids) {
                if (stopRequested) break;
                Platform pl = HibernateUtil.tx(s -> s.get(Platform.class, id));
                if (pl == null || !Platform.PENDING.contains(pl.getStatus())) continue; // đã xử lý ở nơi khác → tránh trùng
                index++;
                current = pl.getName();
                currentUser = prj.getUsernames().isEmpty() ? null : prj.getUsernames().get(0);
                String url = pl.isOwnedProfile() ? pl.getExistingProfileUrl() : pl.getSignupUrl();
                log("(" + index + "/" + total + ") " + pl.getName() + " — mở " + url);
                Page page = ctx.newPage();
                currentPage = page;
                decision.set(null);
                try {
                    page.navigate(url, new Page.NavigateOptions().setTimeout(45_000));
                } catch (PlaywrightException e) {
                    log(pl.getName() + ": không mở được trang (" + firstLine(e.getMessage()) + ")");
                }
                try {
                    if (!pl.isOwnedProfile()) page.evaluate("() => window.__hosowebFill && window.__hosowebFill()");
                } catch (PlaywrightException ignored) { }

                Decision d = waitForDecision(page, ctx);
                apply(id, d);
                try { if (!page.isClosed()) page.close(); } catch (PlaywrightException ignored) { }
                if (d != null && "stop".equals(d.action())) break;
            }
        }
    }

    /** Chờ người dùng bấm nút trên khung nổi. Đóng tab = bỏ qua; đóng trình duyệt = dừng. */
    private Decision waitForDecision(Page page, BrowserContext ctx) {
        while (!stopRequested) {
            Decision d = decision.getAndSet(null);
            if (d != null) return d;
            if (page.isClosed()) return new Decision("skip", null, null, "Đã đóng tab");
            try {
                page.waitForTimeout(400); // trong lúc chờ, Playwright xử lý các lời gọi từ trang
            } catch (PlaywrightException e) {
                if (page.isClosed()) {
                    if (ctx.pages().isEmpty()) { stopRequested = true; return new Decision("stop", null, null, null); }
                    return new Decision("skip", null, null, "Đã đóng tab");
                }
                throw e;
            }
        }
        return new Decision("stop", null, null, null);
    }

    private void apply(Long id, Decision d) {
        if (d == null || "stop".equals(d.action())) return;
        String action = d.action() == null ? "skip" : d.action();
        String msg = HibernateUtil.tx(s -> {
            Platform p = s.get(Platform.class, id);
            if (p == null) return "đã bị xóa";
            switch (action) {
                case "done" -> {
                    String u = Urls.normalize(d.url());
                    if (!Urls.isHttpUrl(u)) return "URL không hợp lệ, chưa lưu";
                    p.setProfileUrl(u);
                    if (d.username() != null && !d.username().isBlank() && (p.getUsername() == null || p.getUsername().isBlank())) {
                        p.setUsername(d.username());
                    }
                    if (!p.isDone() || p.getCompletedAt() == null) p.setCompletedAt(LocalDateTime.now());
                    p.setStatus(Platform.ST_DONE);
                    p.setPendingStep(null);
                    p.setSinbyteStatus(null); p.setSinbyteTaskId(null); p.setSinbyteSentAt(null); p.setIndexStatus(null);
                    done++;
                    return "Hoàn tất → " + u;
                }
                case "verify", "captcha" -> {
                    p.setStatus("verify".equals(action) ? Platform.ST_WAIT_VERIFY : Platform.ST_WAIT_CAPTCHA);
                    if (d.step() != null && !d.step().isBlank()) p.setPendingStep(d.step().trim());
                    if (d.username() != null && !d.username().isBlank() && p.getUsername() == null) p.setUsername(d.username());
                    waiting++;
                    return p.getStatus() + (p.getPendingStep() != null ? " — " + p.getPendingStep() : "");
                }
                default -> {
                    skipped++;
                    return "Bỏ qua" + (d.step() != null ? " (" + d.step() + ")" : "");
                }
            }
        });
        Platform p = HibernateUtil.tx(s -> s.get(Platform.class, id));
        log((p == null ? "#" + id : p.getName()) + ": " + msg);
        if (p != null) HibernateUtil.run(s -> { Platform x = s.get(Platform.class, id); x.touch(); x.getProject().touch(); });
    }

    // ---------- trình duyệt ----------

    private static Playwright createPlaywright() {
        Map<String, String> env = new HashMap<>();
        // Dùng Chrome có sẵn trên máy; chỉ tải Chromium khi không có Chrome.
        if (System.getenv("HOSOWEB_BROWSER_PATH") != null || hasChrome()) env.put("PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD", "1");
        return Playwright.create(new Playwright.CreateOptions().setEnv(env));
    }

    private static boolean hasChrome() {
        for (String p : List.of("/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
                "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
                "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
                "/usr/bin/google-chrome", "/usr/bin/google-chrome-stable")) {
            if (Paths.get(p).toFile().exists()) return true;
        }
        return false;
    }

    private BrowserContext launch(Playwright pw) {
        Path profile = Paths.get(Optional.ofNullable(System.getenv("HOSOWEB_BROWSER_PROFILE")).orElse("data/browser-profile"));
        boolean headless = "true".equalsIgnoreCase(System.getenv("HOSOWEB_RUNNER_HEADLESS"));
        List<String> args = new ArrayList<>();
        String cdp = System.getenv("HOSOWEB_RUNNER_CDP_PORT");
        if (cdp != null) args.add("--remote-debugging-port=" + cdp);
        BrowserType.LaunchPersistentContextOptions o = new BrowserType.LaunchPersistentContextOptions()
                .setHeadless(headless).setViewportSize(null).setArgs(args).setLocale("vi-VN");
        String exe = System.getenv("HOSOWEB_BROWSER_PATH");
        if (exe != null && !exe.isBlank()) {
            return pw.chromium().launchPersistentContext(profile, o.setExecutablePath(Paths.get(exe)));
        }
        if (hasChrome()) {
            try {
                return pw.chromium().launchPersistentContext(profile, o.setChannel("chrome"));
            } catch (PlaywrightException e) {
                log("Không mở được Chrome, dùng Chromium: " + firstLine(e.getMessage()));
                o.setChannel((String) null);
            }
        }
        return pw.chromium().launchPersistentContext(profile, o);
    }

    // ---------- script trên trang ----------

    private static Map<String, Object> autofillData(Project p) {
        Map<String, Object> m = new LinkedHashMap<>();
        m.put("brand", p.getBrandName());
        m.put("website", p.getWebsite());
        m.put("title", p.getTitle());
        m.put("short", p.getShortDescription());
        m.put("full", p.getFullDescription());
        m.put("keywords", p.getSeoKeywords());
        m.put("hashtags", p.getHashtags());
        m.put("usernames", p.getUsernames());
        m.put("email", p.getGmail() != null ? p.getGmail() : p.getPublicEmail());
        m.put("publicEmail", p.getPublicEmail());
        m.put("phone", p.getSmsPhone() != null ? p.getSmsPhone() : p.getPublicPhone());
        m.put("publicPhone", p.getPublicPhone());
        m.put("address", p.getAddress());
        m.put("birthDate", p.getBirthDate());
        m.put("manager", p.getManager());
        m.put("industry", p.getIndustry());
        m.put("hours", p.getOpeningHours());
        return m;
    }

    /** Script chạy trên mọi trang: định nghĩa hàm tự điền (không tự chạy) và khung nút điều khiển. */
    static String overlayScript(String autofillJs, String dataJson, String token) {
        String fill = autofillJs == null ? "" : autofillJs.trim()
                .replaceFirst("\\n\\s*run\\(\\);\\s*\\n\\}\\s*$", "\n}"); // chỉ định nghĩa, không tự điền ngay
        return """
                (function () {
                  if (window.top !== window) return;
                  var ACT = window.__hosowebAction, INFO = window.__hosowebInfo, USET = window.__hosowebUserSet;
                  try { (%s)(%s); } catch (e) {}
                  var f0 = window.__hosowebFill;
                  if (f0) window.__hosowebFill = function () { f0(); try { USET(window.__hosowebUser || ''); } catch (e) {} };
                  var TK = %s;
                  function act(o) { o.username = window.__hosowebUser || ''; o.t = TK; return ACT(o); }
                  function build() {
                    if (!document.body || document.getElementById('__hosoweb_ctrl')) return;
                    var c = document.createElement('div'); c.id = '__hosoweb_ctrl';
                    c.style.cssText = 'position:fixed;z-index:2147483647;top:12px;right:12px;width:270px;background:#17202b;color:#fff;font:13px/1.4 system-ui,sans-serif;padding:10px 12px;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.35)';
                    var h = document.createElement('div'); h.style.cssText = 'font-weight:600;margin-bottom:6px'; h.textContent = 'Hồ sơ Web'; c.appendChild(h);
                    INFO().then(function (i) { if (i && i.name) h.textContent = 'Hồ sơ Web · ' + i.name + ' (' + i.index + '/' + i.total + ')'; });
                    function btn(label, bg, fn) {
                      var b = document.createElement('button'); b.type = 'button'; b.textContent = label;
                      b.style.cssText = 'display:block;width:100%%;margin:4px 0;padding:6px 8px;border-radius:6px;border:0;cursor:pointer;font:inherit;text-align:left;color:#fff;background:' + bg;
                      b.onclick = fn; c.appendChild(b);
                    }
                    var sub = document.createElement('div'); sub.style.cssText = 'display:none;background:#243042;border-radius:8px;padding:8px;margin:4px 0';
                    function ask(text, withInput, def, onOk) {
                      sub.textContent = ''; sub.style.display = 'block';
                      var t = document.createElement('div'); t.style.cssText = 'word-break:break-all;margin-bottom:6px'; t.textContent = text; sub.appendChild(t);
                      var inp = null;
                      if (withInput) { inp = document.createElement('input'); inp.value = def || ''; inp.style.cssText = 'width:100%%;box-sizing:border-box;padding:5px;border-radius:5px;border:0;margin-bottom:6px;color:#111;font:inherit'; sub.appendChild(inp); }
                      var ok = document.createElement('button'); ok.type = 'button'; ok.textContent = 'Xác nhận';
                      ok.style.cssText = 'padding:4px 10px;border-radius:5px;border:0;background:#18794e;color:#fff;cursor:pointer;margin-right:6px;font:inherit';
                      ok.onclick = function () { sub.style.display = 'none'; onOk(inp ? inp.value : null); };
                      var no = document.createElement('button'); no.type = 'button'; no.textContent = 'Hủy';
                      no.style.cssText = 'padding:4px 10px;border-radius:5px;border:0;background:#3b4656;color:#fff;cursor:pointer;font:inherit';
                      no.onclick = function () { sub.style.display = 'none'; };
                      sub.appendChild(ok); sub.appendChild(no);
                    }
                    btn('✅ Đây là trang hồ sơ — lưu URL', '#18794e', function () {
                      ask('Lưu URL này làm URL hồ sơ? ' + location.href, false, null, function () { act({ action: 'done', url: location.href }); });
                    });
                    btn('⚡ Tự điền lại', '#2457d6', function () { window.__hosowebFill && window.__hosowebFill(); });
                    btn('⏳ Chờ xác minh (email/SMS)', '#9a6100', function () {
                      ask('Bước cần làm để hoàn tất:', true, 'Bấm link xác minh trong email', function (v) { act({ action: 'verify', step: v }); });
                    });
                    btn('🧩 Chờ CAPTCHA — làm sau', '#9a6100', function () { act({ action: 'captcha', step: 'Giải CAPTCHA' }); });
                    btn('⏭ Bỏ qua nền tảng này', '#3b4656', function () { act({ action: 'skip' }); });
                    btn('⛔ Dừng lượt chạy', '#c4321f', function () { ask('Dừng lượt chạy?', false, null, function () { act({ action: 'stop' }); }); });
                    c.appendChild(sub);
                    var n = document.createElement('div'); n.style.cssText = 'opacity:.75;font-size:12px;margin-top:4px';
                    n.textContent = 'Tự nhập mật khẩu, giải CAPTCHA, bấm Gửi. Khi vào được trang hồ sơ, bấm nút xanh lá.'; c.appendChild(n);
                    document.body.appendChild(c);
                  }
                  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', build); else build();
                  new MutationObserver(build).observe(document.documentElement, { childList: true, subtree: false });
                })();
                """.formatted(fill, dataJson, JSON_QUOTE(token));
    }

    private static String JSON_QUOTE(String s) {
        try { return JSON.writeValueAsString(s); } catch (Exception e) { return "\"\""; }
    }

    private static String str(Object o) { return o == null ? null : o.toString(); }
    private static String firstLine(String s) { return s == null ? "" : s.split("\\R", 2)[0]; }
}
