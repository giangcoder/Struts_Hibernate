package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import org.hibernate.Session;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.Project;
import vn.hosoweb.model.SocialLink;
import vn.hosoweb.service.PlatformParser;
import vn.hosoweb.service.Repo;
import vn.hosoweb.util.HibernateUtil;
import vn.hosoweb.util.Urls;

import java.time.LocalDateTime;
import java.util.*;

/** Danh sách nền tảng, dán danh sách, màn hình "Tạo" từng mục và "Tạo hàng loạt". */
public class PlatformAction extends ProjectScopedAction {
    // danh sách
    private List<Platform> platforms = List.of();
    private Map<Long, List<String>> issues = new HashMap<>();
    private String filterStatus, filterGroup;
    // dán danh sách
    private String lines;
    private boolean asOwned;
    private String ownedAdminStatus = "Chưa rõ";
    // thao tác hàng loạt trên bảng
    private List<Long> ids = new ArrayList<>();
    private String bulkStatus;
    // màn hình tạo
    private Long id;
    private Platform platform;
    private List<SocialLink> socialLinks = List.of();
    private boolean batch;
    private String mode;
    private boolean markCaptcha;
    private Long afterId;
    private int batchIndex, batchTotal;
    private final Platform f = new Platform();
    private List<Long> linkIds = new ArrayList<>();

    // ---------- Danh sách ----------
    public String list() {
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            List<Platform> all = Repo.platforms(s, project.getId());
            List<Platform> shown = new ArrayList<>();
            for (Platform p : all) {
                if (filterStatus != null && !filterStatus.isBlank() && !filterStatus.equals(p.getStatus())) continue;
                if (filterGroup != null && !filterGroup.isBlank() && !filterGroup.equals(p.getGroupName())) continue;
                shown.add(p);
                List<String> is = p.issues(project);
                if (!is.isEmpty()) issues.put(p.getId(), is);
            }
            platforms = shown;
            return true;
        });
        return ok ? SUCCESS : "notfound";
    }

    public String pasteForm() {
        return HibernateUtil.tx(s -> loadProject(s)) ? INPUT : "notfound";
    }

    public String paste() {
        PlatformParser.Result r = PlatformParser.parse(lines);
        int[] added = {0}, dup = {0};
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            Set<String> seen = new HashSet<>();
            for (Platform p : Repo.platforms(s, project.getId())) {
                seen.add(key(p.getName(), p.getSignupUrl() != null ? p.getSignupUrl() : p.getExistingProfileUrl()));
            }
            int pos = Repo.nextPosition(s, project.getId());
            for (PlatformParser.Entry e : r.entries()) {
                if (!seen.add(key(e.name(), e.url()))) { dup[0]++; continue; }
                Platform p = new Platform();
                p.setProject(project);
                p.setName(e.name());
                p.setGroupName(e.group());
                if (asOwned && e.url() != null) {
                    p.setExistingProfileUrl(e.url());
                    p.setAdminStatus(Platform.ADMIN_STATUSES.contains(ownedAdminStatus) ? ownedAdminStatus : "Chưa rõ");
                } else {
                    p.setSignupUrl(e.url());
                }
                p.setCaptchaManual(e.captcha() || markCaptcha);
                p.setPosition(pos++);
                s.persist(p);
                added[0]++;
            }
            project.touch();
            return true;
        });
        if (!ok) return "notfound";
        if (added[0] == 0 && r.errors().isEmpty() && dup[0] == 0) {
            addActionError("Danh sách trống.");
            HibernateUtil.run(s -> loadProject(s));
            return INPUT;
        }
        flash("Đã thêm " + added[0] + " nền tảng" + (dup[0] > 0 ? ", bỏ qua " + dup[0] + " dòng trùng" : "") + ".");
        for (String err : r.errors()) flashError(err);
        return SUCCESS;
    }

    private static String key(String name, String url) {
        return (name == null ? "" : name.trim().toLowerCase(Locale.ROOT)) + "|" + (url == null ? "" : Urls.dedupeKey(url));
    }

    public String bulk() {
        if (ids.isEmpty()) { flashError("Chưa chọn dòng nào."); return SUCCESS; }
        int n = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return 0;
            int c = 0;
            for (Long pid : ids) {
                Platform p = Repo.platform(s, uid(), pid);
                if (p == null || !p.getProject().getId().equals(project.getId())) continue;
                if ("__delete".equals(bulkStatus)) { s.remove(p); c++; continue; }
                if ("__captcha_on".equals(bulkStatus) || "__captcha_off".equals(bulkStatus)) {
                    p.setCaptchaManual("__captcha_on".equals(bulkStatus)); p.touch(); c++; continue;
                }
                if (!Platform.STATUSES.contains(bulkStatus)) continue;
                if (Platform.ST_DONE.equals(bulkStatus) && !Urls.isHttpUrl(p.getProfileUrl())) continue; // chỉ hoàn tất khi có URL thật
                p.setStatus(bulkStatus);
                if (!p.isDone()) p.setCompletedAt(null);
                else if (p.getCompletedAt() == null) p.setCompletedAt(LocalDateTime.now());
                p.touch();
                c++;
            }
            return c;
        });
        flash("Đã cập nhật " + n + " dòng.");
        if (Platform.ST_DONE.equals(bulkStatus) && n < ids.size()) {
            flashError("Một số dòng chưa có URL hồ sơ thực tế nên không thể đánh dấu Hoàn tất.");
        }
        return SUCCESS;
    }

    // ---------- Tạo từng mục ----------
    public String create() {
        return HibernateUtil.tx(this::loadView) ? INPUT : "notfound";
    }

    /** Quy trình "Tạo hàng loạt": mở lần lượt các mục còn cần xử lý. */
    public String batch() {
        batch = true;
        Long next = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return -1L;
            List<Platform> pending = pending(s);
            batchTotal = pending.size();
            int start = 0;
            if (afterId != null) {
                List<Platform> all = Repo.platforms(s, project.getId());
                int afterIdx = indexOf(all, afterId);
                // mục kế tiếp sau afterId trong thứ tự danh sách
                start = pending.size();
                for (int i = 0; i < pending.size(); i++) {
                    if (indexOf(all, pending.get(i).getId()) > afterIdx) { start = i; break; }
                }
            }
            if (start >= pending.size()) return null;
            batchIndex = start + 1;
            return pending.get(start).getId();
        });
        if (next != null && next == -1L) return "notfound";
        if (next == null) {
            flash(batchTotal == 0 ? "Không còn mục nào cần xử lý." : "Đã đi hết lượt. Các mục còn chờ vẫn nằm trong danh sách.");
            return "done";
        }
        id = next;
        return HibernateUtil.tx(this::loadView) ? INPUT : "notfound";
    }

    /** Mục còn cần xử lý, lọc theo chế độ hàng loạt: "auto" = không CAPTCHA, "captcha" = cần CAPTCHA. */
    private List<Platform> pending(Session s) {
        List<Platform> l = s.createQuery("from Platform where project.id = :p and status in (:st) order by position, id", Platform.class)
                .setParameter("p", project.getId()).setParameterList("st", Platform.PENDING).list();
        if (MODE_AUTO.equals(mode)) l.removeIf(Platform::isCaptchaManual);
        else if (MODE_CAPTCHA.equals(mode)) l.removeIf(p -> !p.isCaptchaManual());
        return l;
    }

    public static final String MODE_AUTO = "auto";
    public static final String MODE_CAPTCHA = "captcha";

    public String getModeLabel() {
        if (MODE_AUTO.equals(mode)) return "mục không CAPTCHA";
        if (MODE_CAPTCHA.equals(mode)) return "mục cần CAPTCHA, làm thủ công";
        return "mọi mục";
    }

    /** Dữ liệu cho nút "Tự điền" (bookmarklet) — chỉ thông tin của dự án hiện tại. */
    public String getAutofillJson() {
        Project p = project;
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
        try {
            return new com.fasterxml.jackson.databind.ObjectMapper().writeValueAsString(m);
        } catch (Exception e) {
            return "{}";
        }
    }

    private static int indexOf(List<Platform> l, Long pid) {
        for (int i = 0; i < l.size(); i++) if (l.get(i).getId().equals(pid)) return i;
        return -1;
    }

    private boolean loadView(Session s) {
        platform = Repo.platform(s, uid(), id);
        if (platform == null) return false;
        projectId = platform.getProject().getId();
        if (!loadProject(s)) return false;
        socialLinks = Repo.socialLinks(s, projectId);
        if (batch && batchTotal == 0) {
            List<Platform> pending = pending(s);
            batchTotal = pending.size();
            batchIndex = indexOf(pending, id) + 1;
        }
        return true;
    }

    public String save() {
        String status = Platform.STATUSES.contains(f.getStatus()) ? f.getStatus() : Platform.ST_TODO;
        String profileUrl = Urls.normalize(f.getProfileUrl());
        String signupUrl = Urls.normalize(f.getSignupUrl());
        String existingUrl = Urls.normalize(f.getExistingProfileUrl());
        if (profileUrl != null && !Urls.isHttpUrl(profileUrl)) addFieldError("f.profileUrl", "URL hồ sơ không hợp lệ.");
        if (signupUrl != null && !Urls.isHttpUrl(signupUrl)) addFieldError("f.signupUrl", "URL đăng ký không hợp lệ.");
        if (existingUrl != null && !Urls.isHttpUrl(existingUrl)) addFieldError("f.existingProfileUrl", "URL hồ sơ hiện có không hợp lệ.");
        if (trim(f.getName()) == null) addFieldError("f.name", "Nhập tên nền tảng.");
        if (Platform.ST_DONE.equals(status) && profileUrl == null && existingUrl != null && Urls.isHttpUrl(existingUrl)) {
            profileUrl = existingUrl; // hồ sơ đang sở hữu → URL hồ sơ chính là link hiện có
        }
        if (Platform.ST_DONE.equals(status) && profileUrl == null) {
            addFieldError("f.profileUrl", "Chỉ chọn Hoàn tất khi đã có URL hồ sơ thực tế.");
        }
        if (hasFieldErrors()) {
            if (!HibernateUtil.tx(this::loadView)) return "notfound";
            // Hiển thị lại dữ liệu vừa nhập (đối tượng đã tách khỏi phiên Hibernate, không bị lưu).
            platform.setName(f.getName()); platform.setGroupName(f.getGroupName());
            platform.setSignupUrl(f.getSignupUrl()); platform.setExistingProfileUrl(f.getExistingProfileUrl());
            platform.setAdminStatus(f.getAdminStatus()); platform.setUsername(f.getUsername());
            platform.setProfileUrl(f.getProfileUrl()); platform.setNote(f.getNote());
            platform.setPendingStep(f.getPendingStep()); platform.setSocialSupport(f.getSocialSupport());
            platform.setStatus(status); platform.setAttachedLinkIdSet(linkIds);
            platform.setCaptchaManual(f.isCaptchaManual());
            return INPUT;
        }
        final String pu = profileUrl;
        Long saved = HibernateUtil.tx(s -> {
            Platform p = Repo.platform(s, uid(), id);
            if (p == null) return null;
            p.setName(trim(f.getName()));
            p.setGroupName(Platform.GROUPS.contains(f.getGroupName()) ? f.getGroupName() : "Khác");
            p.setSignupUrl(signupUrl);
            p.setExistingProfileUrl(existingUrl);
            p.setAdminStatus(Platform.ADMIN_STATUSES.contains(f.getAdminStatus()) ? f.getAdminStatus() : "Chưa rõ");
            p.setUsername(trim(f.getUsername()));
            boolean urlChanged = !Objects.equals(p.getProfileUrl(), pu);
            p.setProfileUrl(pu);
            p.setNote(trim(f.getNote()));
            p.setPendingStep(trim(f.getPendingStep()));
            p.setCaptchaManual(f.isCaptchaManual());
            p.setSocialSupport(Platform.SOCIAL_SUPPORT.contains(f.getSocialSupport()) ? f.getSocialSupport() : "Chưa rõ");
            if ("Có hỗ trợ".equals(p.getSocialSupport())) {
                Set<Long> valid = new HashSet<>();
                for (SocialLink l : Repo.socialLinks(s, p.getProject().getId())) valid.add(l.getId());
                List<Long> keep = new ArrayList<>();
                for (Long l : linkIds) if (valid.contains(l)) keep.add(l);
                p.setAttachedLinkIdSet(keep);
            } else {
                p.setAttachedLinkIdSet(List.of());
            }
            // Giữ nguyên thời điểm hoàn tất khi lưu lại — tránh "tạo trùng".
            if (Platform.ST_DONE.equals(status)) {
                if (!p.isDone() || p.getCompletedAt() == null) p.setCompletedAt(LocalDateTime.now());
            } else {
                p.setCompletedAt(null);
            }
            if (urlChanged) { p.setSinbyteStatus(null); p.setSinbyteTaskId(null); p.setSinbyteSentAt(null); p.setIndexStatus(null); }
            p.setStatus(status);
            p.touch();
            p.getProject().touch();
            projectId = p.getProject().getId();
            return p.getId();
        });
        if (saved == null) return "notfound";
        flash("Đã lưu " + trim(f.getName()) + " — " + status + ".");
        if (batch) { afterId = saved; return "next"; }
        return SUCCESS;
    }

    public String delete() {
        Long pid = HibernateUtil.tx(s -> {
            Platform p = Repo.platform(s, uid(), id);
            if (p == null) return null;
            Long prj = p.getProject().getId();
            s.remove(p);
            return prj;
        });
        if (pid == null) return "notfound";
        projectId = pid;
        flash("Đã xóa nền tảng.");
        return SUCCESS;
    }

    // ---------- getters / setters ----------
    public List<Platform> getPlatforms() { return platforms; }
    public Map<Long, List<String>> getIssues() { return issues; }
    public String getFilterStatus() { return filterStatus; }
    @StrutsParameter public void setFilterStatus(String v) { filterStatus = v; }
    public String getFilterGroup() { return filterGroup; }
    @StrutsParameter public void setFilterGroup(String v) { filterGroup = v; }
    public String getLines() { return lines; }
    @StrutsParameter public void setLines(String v) { lines = v; }
    public boolean isAsOwned() { return asOwned; }
    @StrutsParameter public void setAsOwned(boolean v) { asOwned = v; }
    public String getOwnedAdminStatus() { return ownedAdminStatus; }
    @StrutsParameter public void setOwnedAdminStatus(String v) { ownedAdminStatus = v; }
    @StrutsParameter public void setIds(List<Long> v) { ids = v == null ? new ArrayList<>() : v; }
    @StrutsParameter public void setBulkStatus(String v) { bulkStatus = v; }
    public Long getId() { return id; }
    @StrutsParameter public void setId(Long v) { id = v; }
    public Platform getPlatform() { return platform; }
    public List<SocialLink> getSocialLinks() { return socialLinks; }
    public String getMode() { return mode; }
    @StrutsParameter public void setMode(String v) { mode = MODE_AUTO.equals(v) || MODE_CAPTCHA.equals(v) ? v : null; }
    @StrutsParameter public void setMarkCaptcha(boolean v) { markCaptcha = v; }
    public boolean isBatch() { return batch; }
    @StrutsParameter public void setBatch(boolean v) { batch = v; }
    public Long getAfterId() { return afterId; }
    @StrutsParameter public void setAfterId(Long v) { afterId = v; }
    public int getBatchIndex() { return batchIndex; }
    public int getBatchTotal() { return batchTotal; }
    @StrutsParameter(depth = 1) public Platform getF() { return f; }
    @StrutsParameter public void setLinkIds(List<Long> v) { linkIds = v == null ? new ArrayList<>() : v; }
    public boolean isLinkAttached(Long lid) { return platform != null && platform.getAttachedLinkIdSet().contains(lid); }

    /** Các dòng "nhãn → giá trị" để sao chép khi điền form đăng ký (bỏ qua giá trị trống). */
    public List<String[]> getContentRows() {
        Project p = project;
        return rows("Tên thương hiệu", p.getBrandName(), "Website", p.getWebsite(), "Tiêu đề", p.getTitle(),
                "Mô tả ngắn", p.getShortDescription(), "Mô tả đầy đủ", p.getFullDescription(),
                "Từ khóa SEO", p.getSeoKeywords(), "Hashtag", p.getHashtags(),
                "Email công khai", p.getPublicEmail(), "Điện thoại công khai", p.getPublicPhone(),
                "Địa chỉ", p.getAddress(), "Giờ hoạt động", p.getOpeningHours(), "Google Maps", p.getGoogleMapsUrl(),
                "Ngành nghề", p.getIndustry(), "Quy mô", p.getCompanySize(), "Loại hình", p.getBusinessType(),
                "Thị trường", p.getMarket(), "Ngôn ngữ", p.getLanguage());
    }

    public List<String[]> getSignupRows() {
        Project p = project;
        return rows("Gmail", p.getGmail(), "Kết nối Gmail", p.getGmailStatus(), "Người quản lý", p.getManager(),
                "Ngày sinh", p.getBirthDate(), "Số nhận SMS", p.getSmsPhone(), "Người xử lý mã", p.getOtpHandler());
    }

    private static List<String[]> rows(String... kv) {
        List<String[]> r = new ArrayList<>();
        for (int i = 0; i + 1 < kv.length; i += 2) {
            if (kv[i + 1] != null && !kv[i + 1].isBlank()) r.add(new String[]{kv[i], kv[i + 1]});
        }
        return r;
    }

    public List<String> getStatuses() { return Platform.STATUSES; }
    public List<String> getGroups() { return Platform.GROUPS; }
    public List<String> getAdminStatuses() { return Platform.ADMIN_STATUSES; }
    public List<String> getSocialSupports() { return Platform.SOCIAL_SUPPORT; }
}
