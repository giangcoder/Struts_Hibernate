package vn.hosoweb.web;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import org.apache.struts2.ActionInvocation;
import org.apache.struts2.ServletActionContext;
import org.apache.struts2.interceptor.AbstractInterceptor;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;

/** Mọi yêu cầu POST phải mang tham số csrf khớp với phiên. */
public class CsrfInterceptor extends AbstractInterceptor {
    @Override
    public String intercept(ActionInvocation invocation) throws Exception {
        HttpServletRequest req = ServletActionContext.getRequest();
        if ("POST".equalsIgnoreCase(req.getMethod())) {
            HttpSession s = req.getSession(false);
            Object expected = s == null ? null : s.getAttribute(BaseAction.S_CSRF);
            String got = req.getParameter("csrf");
            if (expected == null || got == null || !MessageDigest.isEqual(
                    expected.toString().getBytes(StandardCharsets.UTF_8), got.getBytes(StandardCharsets.UTF_8))) {
                ServletActionContext.getResponse().setStatus(403);
                return "csrf";
            }
        }
        return invocation.invoke();
    }
}
