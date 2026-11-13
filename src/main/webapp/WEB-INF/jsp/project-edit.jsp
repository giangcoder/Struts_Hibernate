<%@ include file="_header.jspf" %>
<s:set var="tab" value="'info'"/>
<s:if test="project != null"><%@ include file="_tabs.jspf" %></s:if>
<s:else><div class="page-head"><h1>Tạo dự án</h1></div></s:else>

<form method="post" action="<s:url action='projectSave'/>" class="form-grid">
  <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
  <s:if test="project != null"><input type="hidden" name="projectId" value="<s:property value='project.id'/>"></s:if>

  <section class="panel">
    <h2>Thông tin thương hiệu</h2>
    <p class="muted small">Nhập một lần, dùng lại cho mọi nền tảng.</p>
    <div class="grid2">
      <label>Tên thương hiệu *<input name="form.brandName" value="<s:property value='form.brandName'/>" required maxlength="200">
        <s:fielderror fieldName="form.brandName" cssClass="ferr"/></label>
      <label>Website<input name="form.website" value="<s:property value='form.website'/>" placeholder="https://" maxlength="500">
        <s:fielderror fieldName="form.website" cssClass="ferr"/></label>
      <label>Logo (URL ảnh)<input name="form.logoUrl" value="<s:property value='form.logoUrl'/>" placeholder="https://…/logo.png" maxlength="1000" data-preview="logoPrev">
        <s:fielderror fieldName="form.logoUrl" cssClass="ferr"/></label>
      <label>Ảnh cover (URL ảnh)<input name="form.coverUrl" value="<s:property value='form.coverUrl'/>" placeholder="https://…/cover.jpg" maxlength="1000" data-preview="coverPrev">
        <s:fielderror fieldName="form.coverUrl" cssClass="ferr"/></label>
    </div>
    <div class="previews">
      <img id="logoPrev" class="prev-logo" alt="" <s:if test="form.logoUrl != null">src="<s:property value='form.logoUrl'/>"</s:if>>
      <img id="coverPrev" class="prev-cover" alt="" <s:if test="form.coverUrl != null">src="<s:property value='form.coverUrl'/>"</s:if>>
    </div>
    <label>Tiêu đề<input name="form.title" value="<s:property value='form.title'/>" maxlength="300"></label>
    <label>Mô tả ngắn <span class="counter" data-for="shortDesc"></span>
      <textarea id="shortDesc" name="form.shortDescription" rows="2" maxlength="1000"><s:property value="form.shortDescription"/></textarea></label>
    <label>Mô tả đầy đủ <span class="counter" data-for="fullDesc"></span>
      <textarea id="fullDesc" name="form.fullDescription" rows="6"><s:property value="form.fullDescription"/></textarea></label>
    <div class="grid2">
      <label>Từ khóa SEO<input name="form.seoKeywords" value="<s:property value='form.seoKeywords'/>" placeholder="phân tách bằng dấu phẩy" maxlength="1000"></label>
      <label>Hashtag<input name="form.hashtags" value="<s:property value='form.hashtags'/>" placeholder="#thuonghieu #dichvu" maxlength="1000"></label>
    </div>
    <div class="grid3">
      <label>Username ưu tiên 1<input name="form.username1" value="<s:property value='form.username1'/>" maxlength="100"></label>
      <label>Username ưu tiên 2<input name="form.username2" value="<s:property value='form.username2'/>" maxlength="100"></label>
      <label>Username ưu tiên 3<input name="form.username3" value="<s:property value='form.username3'/>" maxlength="100"></label>
    </div>
    <div class="grid2">
      <label>Thị trường<input name="form.market" value="<s:property value='form.market'/>" maxlength="100"></label>
      <label>Ngôn ngữ<input name="form.language" value="<s:property value='form.language'/>" maxlength="100"></label>
    </div>
  </section>

  <section class="panel">
    <h2>Thông tin theo dự án</h2>
    <p class="muted small">Dùng khi đăng ký và xác minh. Không hiển thị công khai.</p>
    <div class="grid2">
      <label>Gmail riêng<input type="email" name="form.gmail" value="<s:property value='form.gmail'/>" maxlength="200"></label>
      <label>Trạng thái kết nối Gmail
        <select name="form.gmailStatus">
          <s:iterator value="gmailStatuses"><option <s:if test="top == form.gmailStatus">selected</s:if>><s:property/></option></s:iterator>
        </select></label>
      <label>Người quản lý<input name="form.manager" value="<s:property value='form.manager'/>" maxlength="200"></label>
      <label>Ngày sinh thật<input type="date" name="form.birthDate" value="<s:property value='form.birthDate'/>">
        <s:fielderror fieldName="form.birthDate" cssClass="ferr"/></label>
      <label>Số nhận SMS<input name="form.smsPhone" value="<s:property value='form.smsPhone'/>" maxlength="50"></label>
      <label>Người xử lý mã (OTP)<input name="form.otpHandler" value="<s:property value='form.otpHandler'/>" maxlength="200"></label>
      <label>Ngành nghề<input name="form.industry" value="<s:property value='form.industry'/>" maxlength="200"></label>
      <label>Quy mô
        <select name="form.companySize">
          <s:iterator value="companySizes"><option value="<s:property/>" <s:if test="top == form.companySize">selected</s:if>><s:property/></option></s:iterator>
        </select></label>
      <label>Loại hình doanh nghiệp
        <select name="form.businessType">
          <s:iterator value="businessTypes"><option value="<s:property/>" <s:if test="top == form.businessType">selected</s:if>><s:property/></option></s:iterator>
        </select></label>
    </div>
    <h3>Liên hệ công khai</h3>
    <div class="grid2">
      <label>Email công khai<input type="email" name="form.publicEmail" value="<s:property value='form.publicEmail'/>" maxlength="200"></label>
      <label>Số điện thoại công khai<input name="form.publicPhone" value="<s:property value='form.publicPhone'/>" maxlength="50"></label>
      <label>Địa chỉ<input name="form.address" value="<s:property value='form.address'/>" maxlength="500"></label>
      <label>Giờ hoạt động<input name="form.openingHours" value="<s:property value='form.openingHours'/>" placeholder="T2–T7, 8:00–17:30" maxlength="300"></label>
      <label>Google Maps<input name="form.googleMapsUrl" value="<s:property value='form.googleMapsUrl'/>" placeholder="https://maps.app.goo.gl/…" maxlength="1000">
        <s:fielderror fieldName="form.googleMapsUrl" cssClass="ferr"/></label>
    </div>
  </section>

  <div class="actions sticky">
    <button class="btn primary" type="submit">Lưu dự án</button>
    <a class="btn" href="<s:url action='projects'/>">Hủy</a>
  </div>
</form>

<s:if test="project != null">
  <form method="post" action="<s:url action='projectDelete'/>" class="danger-zone" data-confirm="Xóa dự án và toàn bộ nền tảng, kết quả?">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
    <button class="btn danger" type="submit">Xóa dự án</button>
  </form>
</s:if>
<%@ include file="_footer.jspf" %>
