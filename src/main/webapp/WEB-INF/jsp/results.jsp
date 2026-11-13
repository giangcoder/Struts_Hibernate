<%@ include file="_header.jspf" %>
<s:set var="tab" value="'results'"/>
<%@ include file="_tabs.jspf" %>
<div class="toolbar">
  <h2 class="m0">Hồ sơ hoàn tất (<s:property value="platforms.size()"/>)</h2>
  <div class="tb-actions">
    <a class="btn" href="<s:url action='csvProgress'><s:param name='projectId' value='project.id'/></s:url>">Tải CSV tiến độ</a>
    <a class="btn" href="<s:url action='csvResults'><s:param name='projectId' value='project.id'/></s:url>">Tải CSV kết quả</a>
    <a class="btn primary" href="<s:url action='sinbyte'><s:param name='projectId' value='project.id'/></s:url>">Submit index</a>
  </div>
</div>
<s:if test="platforms.isEmpty()">
  <div class="empty"><h2>Chưa có hồ sơ hoàn tất</h2>
    <p class="muted">Một mục chỉ được ghi Hoàn tất khi có URL hồ sơ thực tế.</p></div>
</s:if>
<s:else>
  <div class="table-wrap">
  <table class="tbl">
    <thead><tr><th>Nền tảng</th><th>Username</th><th>URL hồ sơ</th><th>Hoàn tất</th><th>Social đã gắn</th><th>Sinbyte</th></tr></thead>
    <tbody>
    <s:iterator value="platforms" var="pl">
      <tr>
        <td><a href="<s:url action='platformCreate'><s:param name='id' value='#pl.id'/></s:url>"><b><s:property value="#pl.name"/></b></a>
          <div><span class="tag"><s:property value="#pl.groupName"/></span></div></td>
        <td><s:property value="#pl.username"/></td>
        <td class="ellipsis"><a href="<s:property value='#pl.profileUrl'/>" target="_blank" rel="noopener noreferrer"><s:property value="#pl.profileUrl"/></a>
          <s:if test="#pl.note != null"><div class="small muted"><s:property value="#pl.note"/></div></s:if></td>
        <td class="small"><s:property value="#pl.completedAtText"/></td>
        <td class="small"><s:if test="#pl.socialSupport == 'Có hỗ trợ'"><s:property value="#pl.attachedLinkIdSet.size()"/> link</s:if><s:else><span class="muted"><s:property value="#pl.socialSupport"/></span></s:else></td>
        <td class="small">
          <s:if test="#pl.sinbyteStatus != null">
            <span class="badge <s:if test="#pl.sinbyteStatus == 'Đã gửi Sinbyte'">ok</s:if><s:else>err</s:else>"><s:property value="#pl.sinbyteStatus"/></span>
            <s:if test="#pl.sinbyteTaskId != null"><div class="muted">Mã: <s:property value="#pl.sinbyteTaskId"/></div></s:if>
          </s:if><s:else><span class="muted">Chưa gửi</span></s:else>
        </td>
      </tr>
    </s:iterator>
    </tbody>
  </table>
  </div>
</s:else>
<%@ include file="_footer.jspf" %>
