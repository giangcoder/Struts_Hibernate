package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import vn.hosoweb.model.Project;
import vn.hosoweb.model.User;
import vn.hosoweb.service.Repo;
import vn.hosoweb.util.HibernateUtil;
import vn.hosoweb.util.Urls;

import java.util.*;

public class ProjectAction extends ProjectScopedAction {
    private List<Project> projects = List.of();
    private Map<Long, long[]> progress = new HashMap<>();
    private Project form = new Project();

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

    public String edit() {
        if (projectId == null) { form = new Project(); return INPUT; }
        boolean ok = HibernateUtil.tx(s -> loadProject(s));
        if (!ok) return "notfound";
        form = project;
        return INPUT;
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
        Long id = HibernateUtil.tx(s -> {
            Project p;
            if (projectId == null) {
                p = new Project();
                p.setOwner(s.getReference(User.class, uid()));
            } else {
                p = Repo.project(s, uid(), projectId);
                if (p == null) return null;
            }
            p.copyEditableFrom(form);
            if (projectId == null) s.persist(p);
            return p.getId();
        });
        if (id == null) return "notfound";
        projectId = id;
        flash("Đã lưu thông tin dự án.");
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

    public List<String> getGmailStatuses() { return List.of("Chưa kết nối", "Đã kết nối", "Cần cấp lại quyền"); }
    public List<String> getBusinessTypes() { return List.of("", "Doanh nghiệp", "Hộ kinh doanh", "Cá nhân", "Tổ chức phi lợi nhuận", "Khác"); }
    public List<String> getCompanySizes() { return List.of("", "1–10", "11–50", "51–200", "201–500", "500+"); }
}
