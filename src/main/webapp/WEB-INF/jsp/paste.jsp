<%@ include file="_header.jspf" %>
<s:set var="tab" value="'list'"/>
<%@ include file="_tabs.jspf" %>
<section class="panel">
  <h2>Dán danh sách</h2>
  <p class="muted small">Mỗi nền tảng một dòng: <code>Tên</code>, <code>URL</code> hoặc <code>Tên | Nhóm | URL</code>.
    Nhóm: Social, Entity, Forum, Khác (bỏ trống sẽ tự đoán). Thêm <code>| captcha</code> cuối dòng để đánh dấu nền tảng cần CAPTCHA (làm thủ công). Nên dùng URL trang đăng ký để mở đúng trang. Dòng trùng sẽ được bỏ qua.</p>
  <form method="post" action="<s:url action='platformPasteSave'/>">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
    <textarea name="lines" rows="14" class="mono" placeholder="Facebook | Social | https://www.facebook.com/pages/create
Crunchbase | Entity | https://www.crunchbase.com/register
https://www.reddit.com/register
Diễn đàn ABC | Forum | https://forum.abc.vn/register | captcha"><s:property value="lines"/></textarea>
    <label class="check"><input type="checkbox" name="asOwned" value="true" <s:if test="asOwned">checked</s:if>>
      Các URL này là <b>hồ sơ đang sở hữu</b> (lưu vào "Link hồ sơ hiện có" thay vì URL đăng ký)</label>
    <label class="check"><input type="checkbox" name="markCaptcha" value="true">
      Đánh dấu <b>tất cả</b> dòng trong lượt dán này là <b>cần CAPTCHA</b> (làm thủ công)</label>
    <label class="inline-label">Trạng thái quyền quản trị cho hồ sơ hiện có
      <select name="ownedAdminStatus">
        <s:iterator value="adminStatuses"><option <s:if test="top == ownedAdminStatus">selected</s:if>><s:property/></option></s:iterator>
      </select></label>
    <div class="actions">
      <button class="btn primary" type="submit">Thêm vào danh sách</button>
      <a class="btn" href="<s:url action='platforms'><s:param name='projectId' value='project.id'/></s:url>">Hủy</a>
    </div>
  </form>
</section>
<%@ include file="_footer.jspf" %>
