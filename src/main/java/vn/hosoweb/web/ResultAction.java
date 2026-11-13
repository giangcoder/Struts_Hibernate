package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.SocialLink;
import vn.hosoweb.service.Csv;
import vn.hosoweb.service.Repo;
import vn.hosoweb.service.SinbyteClient;
import vn.hosoweb.util.HibernateUtil;

import java.io.ByteArrayInputStream;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.*;

/** Kết quả, xuất CSV và Submit index qua Sinbyte. */
public class ResultAction extends ProjectScopedAction {
    private List<Platform> platforms = List.of();
    private Map<Long, String> linkUrls = new HashMap<>();
    private InputStream csvStream;
    private String csvName;
    private String apiKey;
    private List<Long> ids = new ArrayList<>();

    public String results() {
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            platforms = Repo.completed(s, project.getId());
            for (SocialLink l : Repo.socialLinks(s, project.getId())) linkUrls.put(l.getId(), l.getUrl());
            return true;
        });
        return ok ? SUCCESS : "notfound";
    }

    public String attachedLinks(Platform p) {
        StringJoiner j = new StringJoiner(" ");
        for (Long id : p.getAttachedLinkIdSet()) if (linkUrls.containsKey(id)) j.add(linkUrls.get(id));
        return j.toString();
    }

    /** CSV các hồ sơ hoàn tất. */
    public String csvResults() {
        return csv(true);
    }

    /** CSV tiến độ: toàn bộ nền tảng và trạng thái. */
    public String csvProgress() {
        return csv(false);
    }

    private String csv(boolean onlyDone) {
        StringBuilder sb = new StringBuilder("﻿"); // BOM để Excel đọc đúng UTF-8
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            for (SocialLink l : Repo.socialLinks(s, project.getId())) linkUrls.put(l.getId(), l.getUrl());
            List<Platform> rows = onlyDone ? Repo.completed(s, project.getId()) : Repo.platforms(s, project.getId());
            if (onlyDone) {
                sb.append(Csv.row(List.of("Nền tảng", "Nhóm", "Username", "URL hồ sơ", "Hoàn tất lúc", "Ghi chú",
                        "Hỗ trợ liên kết social", "Liên kết social đã gắn", "Trạng thái Sinbyte", "Mã tác vụ Sinbyte", "Gửi Sinbyte lúc")));
                for (Platform p : rows) {
                    sb.append(Csv.row(Arrays.asList(p.getName(), p.getGroupName(), p.getUsername(), p.getProfileUrl(),
                            p.getCompletedAtText(), p.getNote(), p.getSocialSupport(), attachedLinks(p),
                            p.getSinbyteStatus(), p.getSinbyteTaskId(), p.getSinbyteSentAtText())));
                }
            } else {
                sb.append(Csv.row(List.of("STT", "Nền tảng", "Nhóm", "Trạng thái", "URL đăng ký", "Link hồ sơ hiện có",
                        "Quyền quản trị", "Username", "URL hồ sơ", "Hoàn tất lúc", "Bước cần làm", "Ghi chú",
                        "Hỗ trợ liên kết social", "Liên kết social đã gắn", "Trạng thái Sinbyte", "Mã tác vụ Sinbyte")));
                int i = 1;
                for (Platform p : rows) {
                    sb.append(Csv.row(Arrays.asList(i++, p.getName(), p.getGroupName(), p.getStatus(), p.getSignupUrl(),
                            p.getExistingProfileUrl(), p.getAdminStatus(), p.getUsername(), p.getProfileUrl(),
                            p.getCompletedAtText(), p.getPendingStep(), p.getNote(), p.getSocialSupport(), attachedLinks(p),
                            p.getSinbyteStatus(), p.getSinbyteTaskId())));
                }
            }
            return true;
        });
        if (!ok) return "notfound";
        String slug = java.text.Normalizer.normalize(project.getBrandName().replace('đ', 'd').replace('Đ', 'D'),
                java.text.Normalizer.Form.NFD).replaceAll("\\p{M}+", "").replaceAll("[^A-Za-z0-9]+", "-").replaceAll("(^-|-$)", "").toLowerCase(Locale.ROOT);
        if (slug.isEmpty()) slug = "du-an-" + project.getId();
        csvName = slug + (onlyDone ? "-ket-qua-" : "-tien-do-") + LocalDate.now() + ".csv";
        csvStream = new ByteArrayInputStream(sb.toString().getBytes(StandardCharsets.UTF_8));
        return "csv";
    }

    // ---------- Sinbyte ----------
    public String sinbyte() {
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            platforms = Repo.unsentCompleted(s, project.getId(), SinbyteClient.MAX_URLS);
            return true;
        });
        return ok ? SUCCESS : "notfound";
    }

    public String sinbyteSubmit() {
        List<Platform> toSend = new ArrayList<>();
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            Set<Long> wanted = new HashSet<>(ids);
            for (Platform p : Repo.unsentCompleted(s, project.getId(), SinbyteClient.MAX_URLS)) {
                if (wanted.isEmpty() || wanted.contains(p.getId())) toSend.add(p);
            }
            return true;
        });
        if (!ok) return "notfound";
        if (toSend.isEmpty()) { flashError("Không có URL hoàn tất nào chưa gửi."); return SUCCESS; }
        List<String> urls = toSend.stream().map(Platform::getProfileUrl).toList();
        SinbyteClient.Response r = new SinbyteClient().submit(apiKey,
                project.getBrandName() + " " + LocalDate.now(), urls);
        HibernateUtil.run(s -> {
            LocalDateTime now = LocalDateTime.now();
            for (Platform p0 : toSend) {
                Platform p = s.get(Platform.class, p0.getId());
                p.setSinbyteStatus(r.accepted() ? "Đã gửi Sinbyte" : "Gửi lỗi");
                p.setSinbyteTaskId(r.taskId());
                p.setSinbyteSentAt(now);
                p.touch();
            }
        });
        if (r.accepted()) {
            flash("Đã gửi " + urls.size() + " URL tới Sinbyte" + (r.taskId() != null ? " — mã tác vụ " + r.taskId() : "")
                    + ". Xem kết quả lập chỉ mục trong lịch sử Sinbyte.");
        } else {
            flashError("Sinbyte chưa tiếp nhận: " + r.message());
        }
        return SUCCESS;
    }

    public boolean isServerKeyConfigured() { return SinbyteClient.hasServerKey(); }
    public boolean isSinbyteMock() { return SinbyteClient.isMock(); }
    public int getMaxUrls() { return SinbyteClient.MAX_URLS; }

    public List<Platform> getPlatforms() { return platforms; }
    public Map<Long, String> getLinkUrls() { return linkUrls; }
    public InputStream getCsvStream() { return csvStream; }
    public String getCsvName() { return csvName; }
    @StrutsParameter public void setApiKey(String v) { apiKey = v; }
    @StrutsParameter public void setIds(List<Long> v) { ids = v == null ? new ArrayList<>() : v; }
}
