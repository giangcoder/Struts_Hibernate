<%@ include file="_header.jspf" %>
<s:set var="tab" value="'list'"/>
<%@ include file="_tabs.jspf" %>

<div class="stat-row">
  <s:iterator value="stats">
    <a class="stat" href="<s:url action='platforms'><s:param name='projectId' value='project.id'/><s:param name='filterStatus' value='key'/></s:url>">
      <span class="num"><s:property value="value"/></span><span class="lbl"><s:property value="key"/></span></a>
  </s:iterator>
</div>

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
    <a class="btn" href="<s:url action='platformPaste'><s:param name='projectId' value='project.id'/></s:url>">Dán danh sách</a>
    <a class="btn primary" href="<s:url action='platformBatch'><s:param name='projectId' value='project.id'/></s:url>">Tạo hàng loạt</a>
  </div>
</div>

<s:if test="platforms.isEmpty()">
  <div class="empty">
    <h2><s:if test="total == 0">Chưa có nền tảng</s:if><s:else>Không có mục khớp bộ lọc</s:else></h2>
    <s:if test="total == 0">
      <p class="muted">Dán danh sách nền tảng (Social, Entity, Forum, Khác) để bắt đầu.</p>
      <a class="btn primary" href="<s:url action='platformPaste'><s:param name='projectId' value='project.id'/></s:url>">Dán danh sách</a>
    </s:if>
  </div>
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
      <option value="__delete">— Xóa khỏi danh sách —</option>
    </select>
    <button class="btn sm" type="submit" data-confirm-if-delete="Xóa các nền tảng đã chọn?">Áp dụng</button>
  </div>
</form>
</s:else>
<%@ include file="_footer.jspf" %>
