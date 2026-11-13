package vn.hosoweb.web;

import org.apache.struts2.interceptor.parameter.StrutsParameter;
import org.hibernate.Session;
import vn.hosoweb.model.Platform;
import vn.hosoweb.model.Project;
import vn.hosoweb.service.Repo;

import java.util.LinkedHashMap;
import java.util.Map;

/** Action làm việc trong phạm vi một dự án của người dùng hiện tại. */
public abstract class ProjectScopedAction extends BaseAction {
    protected Long projectId;
    protected Project project;
    protected Map<String, Long> stats = new LinkedHashMap<>();
    protected long total;

    /** Tải dự án (kiểm tra quyền sở hữu) và số liệu tiến độ. Trả về false nếu không tìm thấy. */
    protected boolean loadProject(Session s) {
        project = Repo.project(s, uid(), projectId);
        if (project == null) return false;
        total = 0;
        for (String st : Platform.STATUSES) {
            long c = Repo.countByStatus(s, project.getId(), st);
            stats.put(st, c);
            total += c;
        }
        return true;
    }

    public long getDoneCount() { return stats.getOrDefault(Platform.ST_DONE, 0L); }
    public int getDonePercent() { return total == 0 ? 0 : (int) Math.round(getDoneCount() * 100.0 / total); }

    public Long getProjectId() { return projectId; }
    @StrutsParameter public void setProjectId(Long v) { projectId = v; }
    public Project getProject() { return project; }
    public Map<String, Long> getStats() { return stats; }
    public long getTotal() { return total; }
}
