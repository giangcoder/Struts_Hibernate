<%@ include file="_header.jspf" %>
<s:set var="tab" value="'list'"/>
<%@ include file="_tabs.jspf" %>

<s:if test="batch">
  <div class="batch-bar">
    <span>Tạo hàng loạt (<s:property value="modeLabel"/>) · mục <b><s:property value="batchIndex"/></b> / <s:property value="batchTotal"/> còn cần xử lý</span>
    <span>
      <a class="btn sm" href="<s:url action='platformBatch'><s:param name='projectId' value='project.id'/><s:param name='afterId' value='platform.id'/><s:param name='mode' value='mode'/></s:url>">Bỏ qua lượt này →</a>
      <a class="btn sm" href="<s:url action='platforms'><s:param name='projectId' value='project.id'/></s:url>">Dừng</a>
    </span>
  </div>
</s:if>

<div class="create-layout">
  <!-- Cột trái: thông tin để điền -->
  <section class="panel">
    <div class="create-head">
      <div>
        <h2><s:property value="platform.name"/> <span class="tag"><s:property value="platform.groupName"/></span></h2>
        <span class="badge <s:property value='platform.statusCss'/>"><s:property value="platform.status"/></span>
      </div>
      <s:if test="platform.ownedProfile">
        <a class="btn primary" href="<s:property value='platform.existingProfileUrl'/>" target="_blank" rel="noopener noreferrer">Mở hồ sơ hiện có ↗</a>
      </s:if>
      <s:elseif test="platform.signupUrl != null">
        <a class="btn primary" href="<s:property value='platform.signupUrl'/>" target="_blank" rel="noopener noreferrer">Mở trang đăng ký ↗</a>
      </s:elseif>
    </div>
    <div class="autofill">
      <a class="btn bookmarklet" id="autofillLink" href="#" data-autofill="<s:property value='autofillJson'/>"
         title="Kéo nút này lên thanh dấu trang">⚡ Tự điền <s:property value="project.brandName"/></a>
      <div class="small muted">
        <b>Tự điền:</b> kéo nút bên trái lên thanh dấu trang (một lần cho mỗi dự án). Trên trang đăng ký, bấm dấu trang đó để điền
        email, username, tên, website, mô tả… Mật khẩu và CAPTCHA không được điền — bạn tự nhập, kiểm tra rồi bấm Gửi.
      </div>
    </div>
    <s:if test="platform.captchaManual"><div class="flash info">Nền tảng có CAPTCHA: tự điền, giải CAPTCHA thủ công, rồi đặt trạng thái hoặc ghi "bước cần làm".</div></s:if>
    <s:if test="platform.done"><div class="flash ok">Mục này đã hoàn tất — không cần tạo lại. Chỉ sửa khi thông tin thay đổi.</div></s:if>
    <s:if test="platform.ownedProfile"><div class="flash info">Đây là hồ sơ đang sở hữu (<s:property value="platform.adminStatus"/>). Cập nhật nội dung thay vì tạo mới.</div></s:if>
    <s:elseif test="platform.signupUrl == null"><div class="flash err">Thiếu URL đăng ký — bổ sung ở phần "Thông tin nền tảng" bên phải.</div></s:elseif>

    <h3>Username (thử theo thứ tự)</h3>
    <s:if test="project.usernames.isEmpty()"><p class="ferr">Dự án chưa có username — thêm ở tab Thông tin.</p></s:if>
    <div class="chips">
      <s:iterator value="project.usernames" status="st">
        <span class="chip"><small><s:property value="#st.count"/>.</small> <code><s:property/></code>
          <button type="button" class="link" data-copy="<s:property/>">Sao chép</button>
          <button type="button" class="link" data-fill="f.username" data-value="<s:property/>">Dùng</button></span>
      </s:iterator>
    </div>

    <h3>Nội dung hồ sơ</h3>
    <dl class="copy-list">
      <s:iterator value="contentRows">
                  <div class="copy-row">
            <dt><s:property value="top[0]"/></dt>
            <dd><span class="val"><s:property value="top[1]"/></span>
              <button type="button" class="link" data-copy="<s:property value='top[1]'/>">Sao chép</button></dd>
          </div>
      </s:iterator>
    </dl>

    <h3>Hình ảnh</h3>
    <div class="media">
      <s:if test="project.logoUrl != null">
        <figure><img src="<s:property value='project.logoUrl'/>" alt="Logo" class="prev-logo">
          <figcaption><a href="<s:property value='project.logoUrl'/>" target="_blank" rel="noopener noreferrer" download>Tải logo</a></figcaption></figure>
      </s:if>
      <s:if test="project.coverUrl != null">
        <figure><img src="<s:property value='project.coverUrl'/>" alt="Cover" class="prev-cover">
          <figcaption><a href="<s:property value='project.coverUrl'/>" target="_blank" rel="noopener noreferrer" download>Tải ảnh cover</a></figcaption></figure>
      </s:if>
      <s:if test="project.logoUrl == null && project.coverUrl == null"><p class="muted small">Chưa có logo / ảnh cover.</p></s:if>
    </div>

    <h3>Thông tin đăng ký &amp; xác minh</h3>
    <dl class="copy-list">
      <s:iterator value="signupRows">
                  <div class="copy-row"><dt><s:property value="top[0]"/></dt>
            <dd><span class="val"><s:property value="top[1]"/></span>
              <button type="button" class="link" data-copy="<s:property value='top[1]'/>">Sao chép</button></dd></div>
      </s:iterator>
    </dl>
  </section>

  <!-- Cột phải: ghi kết quả -->
  <section class="panel">
    <form method="post" action="<s:url action='platformSave'/>">
      <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
      <input type="hidden" name="id" value="<s:property value='platform.id'/>">
      <s:if test="batch"><input type="hidden" name="batch" value="true"><input type="hidden" name="mode" value="<s:property value='mode'/>"></s:if>

      <h3>Kết quả</h3>
      <label>Trạng thái
        <select name="f.status" id="statusSel">
          <s:iterator value="statuses"><option <s:if test="top == platform.status">selected</s:if>><s:property/></option></s:iterator>
        </select></label>
      <label class="check"><input type="checkbox" name="f.captchaManual" value="true" <s:if test="platform.captchaManual">checked</s:if>>
        Nền tảng này cần CAPTCHA — tôi xử lý thủ công</label>
      <label>Username thực tế<input name="f.username" value="<s:property value='platform.username'/>" maxlength="100"></label>
      <label>URL hồ sơ thực tế<input name="f.profileUrl" value="<s:property value='platform.profileUrl'/>" placeholder="https://" maxlength="1000">
        <s:fielderror fieldName="f.profileUrl" cssClass="ferr"/></label>
      <label>Bước cần làm (khi Chờ xác minh / Chờ CAPTCHA)
        <input name="f.pendingStep" value="<s:property value='platform.pendingStep'/>" placeholder="VD: nhập mã SMS gửi tới số …" maxlength="1000"></label>
      <label>Ghi chú<textarea name="f.note" rows="3" maxlength="2000"><s:property value="platform.note"/></textarea></label>

      <h3>Liên kết social</h3>
      <label>Nền tảng cho phép gắn liên kết social?
        <select name="f.socialSupport" id="socialSel">
          <s:iterator value="socialSupports"><option <s:if test="top == platform.socialSupport">selected</s:if>><s:property/></option></s:iterator>
        </select></label>
      <div id="socialBox" class="social-box">
        <s:if test="socialLinks.isEmpty()">
          <p class="muted small">Chưa có liên kết. Thêm ở bước <a href="<s:url action='projectEdit'><s:param name='projectId' value='project.id'/></s:url>">Hồ sơ → Liên kết</a>.</p>
        </s:if>
        <s:iterator value="socialLinks" var="sl">
          <label class="check"><input type="checkbox" name="linkIds" value="<s:property value='#sl.id'/>" <s:if test="isLinkAttached(#sl.id)">checked</s:if>>
            Đã gắn <b><s:property value="#sl.label"/></b> <span class="muted small ellipsis"><s:property value="#sl.url"/></span></label>
        </s:iterator>
      </div>

      <details class="meta" <s:if test="hasFieldErrors() || platform.signupUrl == null">open</s:if>>
        <summary>Thông tin nền tảng</summary>
        <label>Tên<input name="f.name" value="<s:property value='platform.name'/>" required maxlength="200">
          <s:fielderror fieldName="f.name" cssClass="ferr"/></label>
        <label>Nhóm
          <select name="f.groupName">
            <s:iterator value="groups"><option <s:if test="top == platform.groupName">selected</s:if>><s:property/></option></s:iterator>
          </select></label>
        <label>URL đăng ký<input name="f.signupUrl" value="<s:property value='platform.signupUrl'/>" maxlength="1000">
          <s:fielderror fieldName="f.signupUrl" cssClass="ferr"/></label>
        <label>Link hồ sơ hiện có<input name="f.existingProfileUrl" value="<s:property value='platform.existingProfileUrl'/>" maxlength="1000">
          <s:fielderror fieldName="f.existingProfileUrl" cssClass="ferr"/></label>
        <label>Quyền quản trị
          <select name="f.adminStatus">
            <s:iterator value="adminStatuses"><option <s:if test="top == platform.adminStatus">selected</s:if>><s:property/></option></s:iterator>
          </select></label>
      </details>

      <div class="actions">
        <s:if test="batch"><button class="btn primary" type="submit">Lưu &amp; mục tiếp theo →</button></s:if>
        <s:else><button class="btn primary" type="submit">Lưu</button></s:else>
        <a class="btn" href="<s:url action='platforms'><s:param name='projectId' value='project.id'/></s:url>">Quay lại</a>
      </div>
      <p class="small muted">Chỉ chọn <b>Hoàn tất</b> khi đã có URL hồ sơ thực tế.</p>
    </form>
    <form method="post" action="<s:url action='platformDelete'/>" data-confirm="Xóa nền tảng này khỏi danh sách?">
      <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
      <input type="hidden" name="id" value="<s:property value='platform.id'/>">
      <button class="btn danger sm" type="submit">Xóa nền tảng</button>
    </form>
  </section>
</div>
<%@ include file="_footer.jspf" %>
