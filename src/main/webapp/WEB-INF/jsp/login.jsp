<%@ include file="_header.jspf" %>
<div class="auth-card">
  <h1>Đăng nhập</h1>
  <p class="muted">Dữ liệu dự án được lưu theo tài khoản, mở được từ mọi trình duyệt.</p>
  <form method="post" action="<s:url action='doLogin'/>">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <label>Email<input type="email" name="email" value="<s:property value='email'/>" required autofocus></label>
    <label>Mật khẩu<input type="password" name="password" required></label>
    <button class="btn primary wide" type="submit">Đăng nhập</button>
  </form>
  <p class="small">Chưa có tài khoản? <a href="<s:url action='register'/>">Đăng ký</a></p>
</div>
<%@ include file="_footer.jspf" %>
