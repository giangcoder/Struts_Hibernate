package vn.hosoweb.web;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.apache.struts2.ServletActionContext;
import org.apache.struts2.interceptor.parameter.StrutsParameter;
import vn.hosoweb.service.Repo;
import vn.hosoweb.service.Runner;
import vn.hosoweb.util.HibernateUtil;

import java.io.ByteArrayInputStream;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;
import java.util.LinkedHashMap;
import java.util.Map;

/** Bắt đầu / dừng / xem trạng thái lượt chạy trên trình duyệt. */
public class RunnerAction extends BaseAction {
    private Long projectId;
    private String mode;
    private InputStream json;

    public String start() throws Exception {
        boolean owns = HibernateUtil.tx(s -> Repo.project(s, uid(), projectId) != null);
        if (!owns) return "notfound";
        String js;
        try (InputStream in = ServletActionContext.getServletContext().getResourceAsStream("/assets/autofill.js")) {
            js = in == null ? "" : new String(in.readAllBytes(), StandardCharsets.UTF_8);
        }
        String err = Runner.get().start(uid(), projectId, mode, js);
        if (err != null) flashError(err);
        else flash("Đã bắt đầu chạy. Cửa sổ Chrome sẽ mở trên máy này — làm theo khung điều khiển ở góc phải trên.");
        return SUCCESS;
    }

    public String stop() {
        Runner.Status st = Runner.get().status();
        if (st.running() && st.ownerId() != null && st.ownerId().equals(uid())) {
            Runner.get().stop();
            flash("Đã yêu cầu dừng. Lượt chạy dừng sau nền tảng hiện tại.");
        }
        return SUCCESS;
    }

    public String status() throws Exception {
        Runner.Status st = Runner.get().status();
        Map<String, Object> m = new LinkedHashMap<>();
        boolean mine = st.ownerId() != null && st.ownerId().equals(uid());
        m.put("running", st.running() && mine);
        m.put("busyElsewhere", st.running() && !mine);
        if (mine && st.projectId() != null && st.projectId().equals(projectId)) {
            m.put("current", st.current());
            m.put("index", st.index());
            m.put("total", st.total());
            m.put("done", st.done());
            m.put("waiting", st.waiting());
            m.put("skipped", st.skipped());
            m.put("log", st.log());
        } else if (mine && st.running()) {
            m.put("otherProject", st.projectId());
        }
        json = new ByteArrayInputStream(new ObjectMapper().writeValueAsBytes(m));
        return "json";
    }

    public Long getProjectId() { return projectId; }
    @StrutsParameter public void setProjectId(Long v) { projectId = v; }
    @StrutsParameter public void setMode(String v) { mode = v; }
    public InputStream getJson() { return json; }
}
