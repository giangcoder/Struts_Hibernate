package vn.hosoweb.web;

import jakarta.servlet.http.HttpSession;
import org.apache.struts2.ActionInvocation;
import org.apache.struts2.ServletActionContext;
import org.apache.struts2.interceptor.AbstractInterceptor;

/** Yêu cầu đăng nhập cho mọi action trong package "app". */
public class AuthInterceptor extends AbstractInterceptor {
    @Override
    public String intercept(ActionInvocation invocation) throws Exception {
        HttpSession s = ServletActionContext.getRequest().getSession(false);
        if (s == null || s.getAttribute(BaseAction.S_UID) == null) return "login";
        return invocation.invoke();
    }
}
