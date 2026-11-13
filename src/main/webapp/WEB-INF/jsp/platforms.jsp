<%@ include file="_header.jspf" %>
<s:set var="tab" value="'list'"/>
<%@ include file="_tabs.jspf" %>

<s:if test="total > 0">
<section class="panel runner" id="runner" data-status-url="<s:url action='runnerStatus'><s:param name='projectId' value='project.id'/></s:url>">
  <div class="runner-idle">
    <div>
      <h2 class="m0">▶ Tạo profile tự động</h2>
      <p class="small muted m0">Ứng dụng mở Chrome trên máy này, lần lượt vào trang đăng ký và tự điền thông tin dự án.
        Bạn chỉ nhập mật khẩu, giải CAPTCHA, bấm Gửi, xác minh email, rồi bấm <b>"Đây là trang hồ sơ"</b> — URL được lưu và chuyển sang nền tảng kế tiếp.</p>
    </div>
    <form method="post" action="<s:url action='runnerStart'/>" class="runner-form">
      <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
      <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
      <select name="mode">
        <option value="">Mọi mục chưa xong</option>
        <option value="auto">Chỉ mục không CAPTCHA</option>
        <option value="captcha">Chỉ mục cần CAPTCHA</option>
      </select>
      <button class="btn primary" type="submit">▶ Chạy</button>
    </form>
  </div>
  <div class="runner-live" hidden>
    <div class="runner-top">
      <div><b>Đang chạy:</b> <span data-k="current">…</span> (<span data-k="index">0</span>/<span data-k="total">0</span>)
        · ✅ <span data-k="done">0</span> · ⏳ <span data-k="waiting">0</span> · ⏭ <span data-k="skipped">0</span></div>
      <form method="post" action="<s:url action='runnerStop'/>" class="inline">
        <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
        <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
        <button class="btn sm danger" type="submit">Dừng</button>
      </form>
    </div>
    <pre class="runner-log" data-k="log"></pre>
  </div>
  <div class="runner-busy small flash info" hidden>Một lượt chạy khác đang chạy trên máy này. Dừng lượt đó trước khi chạy dự án này.</div>
</section>
</s:if>

<div class="toolbar">
  <form method="get" action="<s:url action='platforms'/>" class="filters">
    <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
    <select name="filterStatus" onchange="this.form.submit()">
      <option value="">Mọi trạng thái</option>
      <s:iterator value="statuses"><option <s:if test="top == filterStatus">selected</s:if>><s:property/></option></s:iterator>
    </select>
    <select name="filterGroup" onchange="this.form.submit()">
      <option value="">Mọi nhóm</option>
      <s:iterator value="groups"><option <s:if test="top == filterGroup">selected</s:if>><s:property/></option></s:iterator>
    </select>
  </form>
  <div class="tb-actions">
    <a class="btn" href="<s:url action='platformPaste'><s:param name='projectId' value='project.id'/></s:url>">+ Thêm nền tảng</a>
    <a class="btn" href="<s:url action='platformBatch'><s:param name='projectId' value='project.id'/></s:url>" title="Làm thủ công từng mục, không mở trình duyệt tự động">Làm thủ công</a>
  </div>
</div>

<s:if test="platforms.isEmpty()">
  <s:if test="total == 0">
  <section class="panel">
    <h2>Dán danh sách nền tảng cần tạo profile</h2>
    <p class="muted small">Mỗi dòng một nền tảng: <code>URL đăng ký</code> hoặc <code>Tên | Nhóm | URL</code> (Nhóm: Social, Entity, Forum, Khác — bỏ trống sẽ tự đoán). Thêm <code>| captcha</code> nếu trang có CAPTCHA.</p>
    <form method="post" action="<s:url action='platformPasteSave'/>">
      <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
      <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
      <textarea name="lines" rows="10" class="mono" placeholder="https://www.pinterest.com/business/create/
Crunchbase | Entity | https://www.crunchbase.com/register
Reddit | Forum | https://www.reddit.com/register | captcha"></textarea>
      <div class="actions"><button class="btn primary" type="submit">Thêm vào danh sách</button></div>
    </form>
  </section>
  </s:if>
  <s:else><div class="empty"><h2>Không có mục khớp bộ lọc</h2></div></s:else>
</s:if>
<s:else>
<form method="post" action="<s:url action='platformBulk'/>" id="bulkForm">
  <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
  <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr>
      <th class="w-check"><input type="checkbox" data-check-all="ids"></th>
      <th>Nền tảng</th><th>Nhóm</th><th>Trạng thái</th><th>Username / URL hồ sơ</th><th></th>
    </tr></thead>
    <tbody>
    <s:iterator value="platforms" var="pl">
      <tr>
        <td class="w-check"><input type="checkbox" name="ids" value="<s:property value='#pl.id'/>"></td>
        <td>
          <b><s:property value="#pl.name"/></b>
          <s:if test="#pl.captchaManual"><span class="badge wait" title="Cần CAPTCHA — làm thủ công">CAPTCHA</span></s:if>
          <div class="small muted ellipsis">
            <s:if test="#pl.ownedProfile">Hồ sơ hiện có · <s:property value="#pl.adminStatus"/></s:if>
            <s:elseif test="#pl.signupUrl != null"><a href="<s:property value='#pl.signupUrl'/>" target="_blank" rel="noopener noreferrer"><s:property value="#pl.signupUrl"/></a></s:elseif>
          </div>
          <s:if test="issues[#pl.id] != null">
            <ul class="issues"><s:iterator value="issues[#pl.id]"><li><s:property/></li></s:iterator></ul>
          </s:if>
        </td>
        <td><span class="tag"><s:property value="#pl.groupName"/></span></td>
        <td><span class="badge <s:property value='#pl.statusCss'/>"><s:property value="#pl.status"/></span>
          <s:if test="#pl.done"><div class="small muted"><s:property value="#pl.completedAtText"/></div></s:if></td>
        <td class="ellipsis">
          <s:if test="#pl.username != null"><div>@<s:property value="#pl.username"/></div></s:if>
          <s:if test="#pl.profileUrl != null"><a class="small" href="<s:property value='#pl.profileUrl'/>" target="_blank" rel="noopener noreferrer"><s:property value="#pl.profileUrl"/></a></s:if>
        </td>
        <td class="right">
          <a class="btn sm <s:if test='!#pl.done'>primary</s:if>" href="<s:url action='platformCreate'><s:param name='id' value='#pl.id'/></s:url>"><s:if test="#pl.done">Sửa</s:if><s:else>Tạo</s:else></a>
        </td>
      </tr>
    </s:iterator>
    </tbody>
  </table>
  </div>
  <div class="bulk-bar">
    <span class="small muted">Với các dòng đã chọn:</span>
    <select name="bulkStatus">
      <s:iterator value="statuses"><option><s:property/></option></s:iterator>
      <option value="__captcha_on">Đánh dấu cần CAPTCHA (thủ công)</option>
      <option value="__captcha_off">Bỏ đánh dấu CAPTCHA</option>
      <option value="__delete">— Xóa khỏi danh sách —</option>
    </select>
    <button class="btn sm" type="submit" data-confirm-if-delete="Xóa các nền tảng đã chọn?">Áp dụng</button>
  </div>
</form>
</s:else>
<%@ include file="_footer.jspf" %>
