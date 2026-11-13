<%@ include file="_header.jspf" %>
<nav class="steps">
  <span class="step on"><span class="n">1</span>Dự án</span>
  <span class="step"><span class="n">2</span>Hồ sơ</span>
  <span class="step"><span class="n">3</span>Tạo profile</span>
  <span class="step"><span class="n">4</span>Kết quả</span>
</nav>
<div class="auth-card wide-card">
  <h1>Tạo dự án</h1>
  <p class="muted">Chỉ cần tên và website. Thông tin hồ sơ điền ở bước sau.</p>
  <form method="post" action="<s:url action='projectCreate'/>">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <label>Tên dự án / thương hiệu *<input name="form.brandName" value="<s:property value='form.brandName'/>" required maxlength="200" autofocus>
      <s:fielderror fieldName="form.brandName" cssClass="ferr"/></label>
    <label>Website<input name="form.website" value="<s:property value='form.website'/>" placeholder="vd: caphesang.vn" maxlength="500">
      <s:fielderror fieldName="form.website" cssClass="ferr"/></label>
    <button class="btn primary wide" type="submit">Tạo dự án →</button>
  </form>
</div>
<%@ include file="_footer.jspf" %>
