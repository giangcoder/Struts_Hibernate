package vn.hosoweb.web;

import jakarta.servlet.http.HttpSession;
import org.apache.struts2.ServletActionContext;
import org.apache.struts2.interceptor.parameter.StrutsParameter;
import org.mindrot.jbcrypt.BCrypt;
import vn.hosoweb.model.User;
import vn.hosoweb.service.Repo;
import vn.hosoweb.util.HibernateUtil;

public class AuthAction extends BaseAction {
    private String email, password, password2, displayName;

    public String loginForm() { return getUid() != null ? "home" : INPUT; }
    public String registerForm() { return getUid() != null ? "home" : INPUT; }

    public String login() {
        if (trim(email) == null || password == null) {
            addActionError("Nhập email và mật khẩu.");
            return INPUT;
        }
        User u = HibernateUtil.tx(s -> Repo.userByEmail(s, email));
        if (u == null || !BCrypt.checkpw(password, u.getPasswordHash())) {
            addActionError("Email hoặc mật khẩu không đúng.");
            return INPUT;
        }
        signIn(u);
        return SUCCESS;
    }

    public String register() {
        String e = trim(email);
        if (e == null || !e.matches("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$") || e.length() > 190) addFieldError("email", "Email không hợp lệ.");
        if (password == null || password.length() < 8) addFieldError("password", "Mật khẩu tối thiểu 8 ký tự.");
        else if (!password.equals(password2)) addFieldError("password2", "Mật khẩu nhập lại không khớp.");
        if (hasFieldErrors()) return INPUT;
        User created = HibernateUtil.tx(s -> {
            if (Repo.userByEmail(s, e) != null) return null;
            User u = new User();
            u.setEmail(e.toLowerCase());
            u.setDisplayName(trim(displayName) == null ? e : trim(displayName));
            u.setPasswordHash(BCrypt.hashpw(password, BCrypt.gensalt(11)));
            s.persist(u);
            return u;
        });
        if (created == null) {
            addFieldError("email", "Email đã được đăng ký.");
            return INPUT;
        }
        signIn(created);
        flash("Đã tạo tài khoản. Bắt đầu bằng việc tạo dự án đầu tiên.");
        return SUCCESS;
    }

    public String logout() {
        HttpSession s = ServletActionContext.getRequest().getSession(false);
        if (s != null) s.invalidate();
        return SUCCESS;
    }

    private void signIn(User u) {
        HttpSession old = ServletActionContext.getRequest().getSession(false);
        if (old != null) old.invalidate(); // chống session fixation
        HttpSession s = ServletActionContext.getRequest().getSession(true);
        s.setAttribute(S_UID, u.getId());
        s.setAttribute(S_UNAME, u.getDisplayName());
    }

    public String getEmail() { return email; }
    @StrutsParameter public void setEmail(String v) { email = v; }
    @StrutsParameter public void setPassword(String v) { password = v; }
    @StrutsParameter public void setPassword2(String v) { password2 = v; }
    public String getDisplayName() { return displayName; }
    @StrutsParameter public void setDisplayName(String v) { displayName = v; }
}
