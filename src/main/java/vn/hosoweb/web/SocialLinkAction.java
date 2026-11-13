package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.SocialLink;
import vn.hosoweb.service.Repo;
import vn.hosoweb.util.HibernateUtil;
import vn.hosoweb.util.Urls;

import java.util.*;

/** Quản lý "Liên kết social" của dự án. */
public class SocialLinkAction extends ProjectScopedAction {
    private List<SocialLink> links = List.of();
    private Map<Long, Integer> usage = new HashMap<>();
    private long supportCount;
    private String label, url;
    private Long id;

    public String list() {
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            links = Repo.socialLinks(s, project.getId());
            for (Platform p : Repo.platforms(s, project.getId())) {
                if ("Có hỗ trợ".equals(p.getSocialSupport())) supportCount++;
                for (Long l : p.getAttachedLinkIdSet()) usage.merge(l, 1, Integer::sum);
            }
            return true;
        });
        return ok ? SUCCESS : "notfound";
    }

    public String add() {
        String u = Urls.normalize(url);
        if (!Urls.isHttpUrl(u)) { flashError("URL liên kết social không hợp lệ."); return SUCCESS; }
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            SocialLink l = new SocialLink();
            l.setProject(project);
            l.setUrl(u);
            String lb = trim(label);
            if (lb == null) {
                String h = Urls.host(u);
                lb = h == null ? u : h;
            }
            l.setLabel(lb.length() > 100 ? lb.substring(0, 100) : lb);
            s.persist(l);
            return true;
        });
        if (!ok) return "notfound";
        flash("Đã thêm liên kết social.");
        return SUCCESS;
    }

    public String delete() {
        boolean ok = HibernateUtil.tx(s -> {
            if (!loadProject(s)) return false;
            SocialLink l = s.get(SocialLink.class, id);
            if (l == null || !l.getProject().getId().equals(project.getId())) return true;
            for (Platform p : Repo.platforms(s, project.getId())) {
                Set<Long> set = p.getAttachedLinkIdSet();
                if (set.remove(id)) p.setAttachedLinkIdSet(set);
            }
            s.remove(l);
            return true;
        });
        if (!ok) return "notfound";
        flash("Đã xóa liên kết social.");
        return SUCCESS;
    }

    public List<SocialLink> getLinks() { return links; }
    public Map<Long, Integer> getUsage() { return usage; }
    public long getSupportCount() { return supportCount; }
    @StrutsParameter public void setLabel(String v) { label = v; }
    @StrutsParameter public void setUrl(String v) { url = v; }
    @StrutsParameter public void setId(Long v) { id = v; }
}
