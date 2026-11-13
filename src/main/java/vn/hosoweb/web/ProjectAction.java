package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.Project;
import vn.hosoweb.model.SocialLink;
import vn.hosoweb.model.User;
import vn.hosoweb.service.Repo;
import vn.hosoweb.util.HibernateUtil;
import vn.hosoweb.util.Urls;

import java.util.*;

public class ProjectAction extends ProjectScopedAction {
    private List<Project> projects = List.of();
    private Map<Long, long[]> progress = new HashMap<>();
    private Project form = new Project();
    private String linksText;

    /** Trang chủ: danh sách dự án. */
    public String list() {
        HibernateUtil.run(s -> {
            projects = Repo.projects(s, uid());
            for (Project p : projects) {
                List<Object[]> rows = s.createQuery("select status, count(*) from Platform where project.id = :p group by status", Object[].class)
                        .setParameter("p", p.getId()).list();
                long tot = 0, done = 0;
                for (Object[] r : rows) {
                    long c = (Long) r[1];
                    tot += c;
                    if ("Hoàn tất".equals(r[0])) done = c;
                }
                progress.put(p.getId(), new long[]{done, tot});
            }
        });
        return SUCCESS;
    }

    /** Bước 1: tạo dự án chỉ với Tên + Website. */
    public String newForm() { return INPUT; }

    public String create() {
        String name = trim(form.getBrandName());
        String site = Urls.normalize(form.getWebsite());
        if (name == null) addFieldError("form.brandName", "Nhập tên dự án / thương hiệu.");
        if (site != null && !Urls.isHttpUrl(site)) addFieldError("form.website", "Website không hợp lệ.");
        if (hasFieldErrors()) return INPUT;
        projectId = HibernateUtil.tx(s -> {
            Project p = new Project();
            p.setOwner(s.getReference(User.class, uid()));
            Project f = new Project();
            f.setBrandName(name);
            f.setWebsite(site);
            f.setTitle(name);
            List<String> u = suggestUsernames(name, site);
            if (u.size() > 0) f.setUsername1(u.get(0));
            if (u.size() > 1) f.setUsername2(u.get(1));
            if (u.size() > 2) f.setUsername3(u.get(2));
            p.copyEditableFrom(f);
            s.persist(p);
            return p.getId();
        });
        flash("Đã tạo dự án. Bước 2: điền hồ sơ (logo, banner, mô tả, liên kết).");
        return SUCCESS;
    }

    /** Gợi ý username từ tên (bỏ dấu) và tên miền. */
    static List<String> suggestUsernames(String name, String site) {
        String base = java.text.Normalizer.normalize(name.replace('đ', 'd').replace('Đ', 'D'), java.text.Normalizer.Form.NFD)
                .replaceAll("\\p{M}+", "").toLowerCase(Locale.ROOT).replaceAll("[^a-z0-9]+", "");
        String host = site == null ? null : Urls.host(site);
        String dom = host == null ? "" : host.split("\\.")[0].replaceAll("[^a-z0-9]", "");
        LinkedHashSet<String> s = new LinkedHashSet<>();
        if (!dom.isEmpty()) s.add(dom);
        if (!base.isEmpty()) s.add(base.length() > 24 ? base.substring(0, 24) : base);
        String b = dom.isEmpty() ? base : dom;
        if (!b.isEmpty()) { s.add(b + "official"); s.add(b + "vn"); }
        return new ArrayList<>(s).subList(0, Math.min(3, s.size()));
    }

    public String edit() {
        if (projectId == null) return "new";
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            linksText = linksToText(Repo.socialLinks(s, project.getId()));
            return true;
        });
        if (!ok) return "notfound";
        form = project;
        return INPUT;
    }

    private static String linksToText(List<SocialLink> links) {
        StringBuilder sb = new StringBuilder();
        for (SocialLink l : links) sb.append(l.getUrl()).append('\n');
        return sb.toString();
    }

    /** Đồng bộ ô "Liên kết" (mỗi dòng một URL) vào bảng SocialLink, giữ id của link không đổi. */
    private int syncLinks(org.hibernate.Session s, Project p) {
        List<String> urls = new ArrayList<>();
        int bad = 0;
        if (linksText != null) for (String line : linksText.split("\\r?\\n")) {
            String u = Urls.normalize(line);
            if (u == null) continue;
            if (Urls.isHttpUrl(u)) { if (!urls.contains(u)) urls.add(u); } else bad++;
        }
        List<SocialLink> existing = Repo.socialLinks(s, p.getId());
        Set<String> have = new HashSet<>();
        for (SocialLink l : existing) {
            if (urls.contains(l.getUrl())) { have.add(l.getUrl()); continue; }
            for (Platform pl : Repo.platforms(s, p.getId())) {
                Set<Long> set = pl.getAttachedLinkIdSet();
                if (set.remove(l.getId())) pl.setAttachedLinkIdSet(set);
            }
            s.remove(l);
        }
        for (String u : urls) {
            if (have.contains(u)) continue;
            SocialLink l = new SocialLink();
            l.setProject(p);
            l.setUrl(u);
            String h = Urls.host(u);
            l.setLabel(h == null ? u : (h.length() > 100 ? h.substring(0, 100) : h));
            s.persist(l);
        }
        return bad;
    }

    public String save() {
        if (trim(form.getBrandName()) == null) addFieldError("form.brandName", "Nhập tên thương hiệu.");
        for (String[] f : new String[][]{{"form.website", form.getWebsite()}, {"form.logoUrl", form.getLogoUrl()},
                {"form.coverUrl", form.getCoverUrl()}, {"form.googleMapsUrl", form.getGoogleMapsUrl()}}) {
            String v = Urls.normalize(f[1]);
            if (v != null && !Urls.isHttpUrl(v)) addFieldError(f[0], "URL phải bắt đầu bằng http:// hoặc https://");
        }
        form.setWebsite(Urls.normalize(form.getWebsite()));
        form.setLogoUrl(Urls.normalize(form.getLogoUrl()));
        form.setCoverUrl(Urls.normalize(form.getCoverUrl()));
        form.setGoogleMapsUrl(Urls.normalize(form.getGoogleMapsUrl()));
        if (form.getBirthDate() != null && !form.getBirthDate().isBlank()
                && !form.getBirthDate().trim().matches("\\d{4}-\\d{2}-\\d{2}")) addFieldError("form.birthDate", "Ngày sinh dạng YYYY-MM-DD.");
        if (hasFieldErrors()) {
            if (projectId != null) HibernateUtil.run(s -> loadProject(s));
            return INPUT;
        }
        if (projectId == null) return "new";
        int[] bad = {0};
        Long id = HibernateUtil.tx(s -> {
            Project p = Repo.project(s, uid(), projectId);
            if (p == null) return null;
            p.copyEditableFrom(form);
            bad[0] = syncLinks(s, p);
            return p.getId();
        });
        if (id == null) return "notfound";
        projectId = id;
        if (bad[0] > 0) flashError(bad[0] + " dòng liên kết không phải URL hợp lệ nên đã bỏ qua.");
        flash("Đã lưu hồ sơ. Bước 3: dán danh sách nền tảng rồi bấm ▶ Chạy để tạo profile.");
        return SUCCESS;
    }

    public String delete() {
        boolean ok = HibernateUtil.tx(s -> {
            Project p = Repo.project(s, uid(), projectId);
            if (p == null) return false;
            s.createMutationQuery("delete from Platform where project.id = :p").setParameter("p", p.getId()).executeUpdate();
            s.createMutationQuery("delete from SocialLink where project.id = :p").setParameter("p", p.getId()).executeUpdate();
            s.remove(p);
            return true;
        });
        if (ok) flash("Đã xóa dự án.");
        return SUCCESS;
    }

    public List<Project> getProjects() { return projects; }
    public long[] progressOf(Long id) { return progress.getOrDefault(id, new long[]{0, 0}); }
    public Map<Long, long[]> getProgress() { return progress; }

    @StrutsParameter(depth = 1)
    public Project getForm() { return form; }
    public String getLinksText() { return linksText; }
    @StrutsParameter public void setLinksText(String v) { linksText = v; }

    public List<String> getGmailStatuses() { return List.of("Chưa kết nối", "Đã kết nối", "Cần cấp lại quyền"); }
    public List<String> getBusinessTypes() { return List.of("", "Doanh nghiệp", "Hộ kinh doanh", "Cá nhân", "Tổ chức phi lợi nhuận", "Khác"); }
    public List<String> getCompanySizes() { return List.of("", "1–10", "11–50", "51–200", "201–500", "500+"); }
}
