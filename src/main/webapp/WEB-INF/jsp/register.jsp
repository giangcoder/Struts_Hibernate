<%@ include file="_header.jspf" %>
<div class="auth-card">
  <h1>Tạo tài khoản</h1>
  <form method="post" action="<s:url action='doRegister'/>">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <label>Tên hiển thị<input name="displayName" value="<s:property value='displayName'/>" maxlength="120"></label>
    <label>Email<input type="email" name="email" value="<s:property value='email'/>" required>
      <s:fielderror fieldName="email" cssClass="ferr"/></label>
    <label>Mật khẩu (tối thiểu 8 ký tự)<input type="password" name="password" required minlength="8">
      <s:fielderror fieldName="password" cssClass="ferr"/></label>
    <label>Nhập lại mật khẩu<input type="password" name="password2" required>
      <s:fielderror fieldName="password2" cssClass="ferr"/></label>
    <button class="btn primary wide" type="submit">Đăng ký</button>
  </form>
  <p class="small">Đã có tài khoản? <a href="<s:url action='login'/>">Đăng nhập</a></p>
</div>
<%@ include file="_footer.jspf" %>
