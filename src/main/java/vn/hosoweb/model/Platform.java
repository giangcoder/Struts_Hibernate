package vn.hosoweb.model;

import jakarta.persistence.*;
import java.time.LocalDateTime;
import java.util.*;

/** Một nền tảng (Social / Entity / Forum / Khác) cần tạo hồ sơ trong một dự án. */
@Entity
@Table(name = "platform", indexes = @Index(name = "idx_platform_project", columnList = "project_id"))
public class Platform {
    public static final String ST_TODO = "Chưa tạo";
    public static final String ST_IN_PROGRESS = "Đang xử lý";
    public static final String ST_WAIT_VERIFY = "Chờ xác minh";
    public static final String ST_WAIT_CAPTCHA = "Chờ CAPTCHA";
    public static final String ST_DONE = "Hoàn tất";
    public static final String ST_SKIPPED = "Bỏ qua";
    public static final String ST_ERROR = "Lỗi";
    public static final List<String> STATUSES = List.of(ST_TODO, ST_IN_PROGRESS, ST_WAIT_VERIFY,
            ST_WAIT_CAPTCHA, ST_DONE, ST_SKIPPED, ST_ERROR);
    /** Trạng thái còn cần xử lý — dùng cho quy trình "Tạo hàng loạt". */
    public static final List<String> PENDING = List.of(ST_TODO, ST_IN_PROGRESS, ST_WAIT_VERIFY, ST_WAIT_CAPTCHA);

    public static final List<String> GROUPS = List.of("Social", "Entity", "Forum", "Khác");
    public static final List<String> ADMIN_STATUSES = List.of("Chưa rõ", "Có quyền quản trị", "Không có quyền quản trị");
    public static final List<String> SOCIAL_SUPPORT = List.of("Chưa rõ", "Có hỗ trợ", "Không hỗ trợ");

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY, optional = false)
    private Project project;

    @Column(nullable = false, length = 200) private String name;
    @Column(length = 20) private String groupName = "Khác";
    @Column(length = 1000) private String signupUrl;
    @Column(length = 1000) private String existingProfileUrl;
    @Column(length = 50) private String adminStatus = "Chưa rõ";

    @Column(length = 30) private String status = ST_TODO;
    @Column(length = 100) private String username;
    @Column(length = 1000) private String profileUrl;
    private LocalDateTime completedAt;
    @Column(length = 2000) private String note;
    /** Bước cần làm khi đang chờ xác minh / CAPTCHA. */
    @Column(length = 1000) private String pendingStep;

    @Column(length = 20) private String socialSupport = "Chưa rõ";
    /** Danh sách id SocialLink đã gắn, phân tách bằng dấu phẩy. */
    @Column(length = 1000) private String attachedLinkIds;

    @Column(length = 50) private String sinbyteStatus;
    @Column(length = 100) private String sinbyteTaskId;
    private LocalDateTime sinbyteSentAt;

    /** Người dùng đánh dấu nền tảng có CAPTCHA — xử lý thủ công. */
    private Boolean captchaManual;

    private int position;
    private LocalDateTime createdAt = LocalDateTime.now();
    private LocalDateTime updatedAt = LocalDateTime.now();

    public boolean isDone() { return ST_DONE.equals(status); }
    public boolean isOwnedProfile() { return existingProfileUrl != null && !existingProfileUrl.isBlank(); }

    public Set<Long> getAttachedLinkIdSet() {
        Set<Long> s = new LinkedHashSet<>();
        if (attachedLinkIds == null) return s;
        for (String p : attachedLinkIds.split(",")) {
            try { if (!p.isBlank()) s.add(Long.parseLong(p.trim())); } catch (NumberFormatException ignored) { }
        }
        return s;
    }
    public void setAttachedLinkIdSet(Collection<Long> ids) {
        StringJoiner j = new StringJoiner(",");
        if (ids != null) ids.forEach(i -> j.add(String.valueOf(i)));
        attachedLinkIds = j.length() == 0 ? null : j.toString();
    }

    /** CSS class cho nhãn trạng thái. */
    public String getStatusCss() {
        return switch (status == null ? "" : status) {
            case ST_DONE -> "ok";
            case ST_WAIT_VERIFY, ST_WAIT_CAPTCHA, ST_IN_PROGRESS -> "wait";
            case ST_ERROR -> "err";
            case ST_SKIPPED -> "muted";
            default -> "todo";
        };
    }

    private static final java.time.format.DateTimeFormatter FMT = java.time.format.DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
    public String getCompletedAtText() { return completedAt == null ? "" : completedAt.format(FMT); }
    public String getSinbyteSentAtText() { return sinbyteSentAt == null ? "" : sinbyteSentAt.format(FMT); }

    /** Lý do một dòng chưa thể xử lý đầy đủ (hiển thị trên từng dòng). */
    public List<String> issues(Project p) {
        List<String> r = new ArrayList<>();
        if (isDone() || ST_SKIPPED.equals(status)) return r;
        if (isOwnedProfile()) {
            if (!"Có quyền quản trị".equals(adminStatus)) r.add("Hồ sơ hiện có: chưa xác nhận quyền quản trị");
            return r;
        }
        if (signupUrl == null || signupUrl.isBlank()) r.add("Thiếu URL đăng ký");
        if (p.getUsernames().isEmpty()) r.add("Dự án chưa có username");
        if (p.getGmail() == null) r.add("Dự án chưa có Gmail");
        if (ST_WAIT_VERIFY.equals(status) || ST_WAIT_CAPTCHA.equals(status)) {
            r.add(pendingStep == null || pendingStep.isBlank() ? "Chưa ghi bước cần làm" : "Cần làm: " + pendingStep);
        }
        return r;
    }

    public Long getId() { return id; }
    public Project getProject() { return project; }
    public void setProject(Project p) { project = p; }
    public String getName() { return name; }
    public void setName(String v) { name = v; }
    public String getGroupName() { return groupName; }
    public void setGroupName(String v) { groupName = v; }
    public String getSignupUrl() { return signupUrl; }
    public void setSignupUrl(String v) { signupUrl = v; }
    public String getExistingProfileUrl() { return existingProfileUrl; }
    public void setExistingProfileUrl(String v) { existingProfileUrl = v; }
    public String getAdminStatus() { return adminStatus; }
    public void setAdminStatus(String v) { adminStatus = v; }
    public String getStatus() { return status; }
    public void setStatus(String v) { status = v; }
    public String getUsername() { return username; }
    public void setUsername(String v) { username = v; }
    public String getProfileUrl() { return profileUrl; }
    public void setProfileUrl(String v) { profileUrl = v; }
    public LocalDateTime getCompletedAt() { return completedAt; }
    public void setCompletedAt(LocalDateTime v) { completedAt = v; }
    public String getNote() { return note; }
    public void setNote(String v) { note = v; }
    public boolean isCaptchaManual() { return Boolean.TRUE.equals(captchaManual); }
    public void setCaptchaManual(boolean v) { captchaManual = v; }
    public String getPendingStep() { return pendingStep; }
    public void setPendingStep(String v) { pendingStep = v; }
    public String getSocialSupport() { return socialSupport; }
    public void setSocialSupport(String v) { socialSupport = v; }
    public String getAttachedLinkIds() { return attachedLinkIds; }
    public String getSinbyteStatus() { return sinbyteStatus; }
    public void setSinbyteStatus(String v) { sinbyteStatus = v; }
    public String getSinbyteTaskId() { return sinbyteTaskId; }
    public void setSinbyteTaskId(String v) { sinbyteTaskId = v; }
    public LocalDateTime getSinbyteSentAt() { return sinbyteSentAt; }
    public void setSinbyteSentAt(LocalDateTime v) { sinbyteSentAt = v; }
    public int getPosition() { return position; }
    public void setPosition(int v) { position = v; }
    public LocalDateTime getCreatedAt() { return createdAt; }
    public LocalDateTime getUpdatedAt() { return updatedAt; }
    public void touch() { updatedAt = LocalDateTime.now(); }
}
