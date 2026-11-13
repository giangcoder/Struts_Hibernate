package vn.hosoweb.web;

import jakarta.servlet.http.HttpSession;
import org.apache.struts2.ActionSupport;
import org.apache.struts2.ServletActionContext;

import java.security.SecureRandom;
import java.util.ArrayList;
import java.util.Base64;
import java.util.List;

public abstract class BaseAction extends ActionSupport {
    public static final String S_UID = "uid";
    public static final String S_UNAME = "uname";
    public static final String S_CSRF = "csrf";
    private static final String S_FLASH_OK = "flashOk";
    private static final String S_FLASH_ERR = "flashErr";
    private static final SecureRandom RNG = new SecureRandom();

    protected HttpSession session() {
        return ServletActionContext.getRequest().getSession(true);
    }

    public Long getUid() {
        Object v = ServletActionContext.getRequest().getSession(true).getAttribute(S_UID);
        return v instanceof Long l ? l : null;
    }

    public String getUserName() {
        Object v = session().getAttribute(S_UNAME);
        return v == null ? null : v.toString();
    }

    protected long uid() {
        Long u = getUid();
        if (u == null) throw new IllegalStateException("Chưa đăng nhập");
        return u;
    }

    public String getCsrf() { return csrfToken(session()); }

    public static String csrfToken(HttpSession s) {
        Object t = s.getAttribute(S_CSRF);
        if (t == null) {
            byte[] b = new byte[24];
            RNG.nextBytes(b);
            t = Base64.getUrlEncoder().withoutPadding().encodeToString(b);
            s.setAttribute(S_CSRF, t);
        }
        return t.toString();
    }

    @SuppressWarnings("unchecked")
    private List<String> bucket(String key) {
        Object v = session().getAttribute(key);
        if (v == null) { v = new ArrayList<String>(); session().setAttribute(key, v); }
        return (List<String>) v;
    }

    protected void flash(String msg) { bucket(S_FLASH_OK).add(msg); }
    protected void flashError(String msg) { bucket(S_FLASH_ERR).add(msg); }

    /** Đọc và xóa thông báo — gọi một lần trong layout. */
    public List<String> getFlashMessages() {
        List<String> l = new ArrayList<>(bucket(S_FLASH_OK));
        l.addAll(getActionMessages());
        session().removeAttribute(S_FLASH_OK);
        return l;
    }

    public List<String> getFlashErrors() {
        List<String> l = new ArrayList<>(bucket(S_FLASH_ERR));
        l.addAll(getActionErrors());
        session().removeAttribute(S_FLASH_ERR);
        return l;
    }

    protected static String trim(String s) {
        if (s == null) return null;
        String t = s.trim();
        return t.isEmpty() ? null : t;
    }
}
