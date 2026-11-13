<%@ include file="_header.jspf" %>
<s:set var="tab" value="'social'"/>
<%@ include file="_tabs.jspf" %>
<section class="panel">
  <h2>Liên kết social</h2>
  <p class="muted small">Lưu các URL social của thương hiệu. Khi cập nhật một nền tảng, chọn <b>Có hỗ trợ</b> rồi đánh dấu link đã gắn để đưa vào CSV.
    Hiện có <b><s:property value="supportCount"/></b> nền tảng được đánh dấu có hỗ trợ.</p>
  <form method="post" action="<s:url action='socialLinkAdd'/>" class="row-form">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
    <input name="label" placeholder="Nhãn (VD: Facebook)" maxlength="100">
    <input name="url" placeholder="https://…" required maxlength="1000" class="grow">
    <button class="btn primary" type="submit">Thêm</button>
  </form>
  <s:if test="links.isEmpty()"><p class="muted">Chưa có liên kết nào.</p></s:if>
  <s:else>
    <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th>Nhãn</th><th>URL</th><th>Đã gắn tại</th><th></th></tr></thead>
      <tbody>
      <s:iterator value="links" var="l">
        <tr>
          <td><b><s:property value="#l.label"/></b></td>
          <td class="ellipsis"><a href="<s:property value='#l.url'/>" target="_blank" rel="noopener noreferrer"><s:property value="#l.url"/></a></td>
          <td><s:property value="usage[#l.id] == null ? 0 : usage[#l.id]"/> nền tảng</td>
          <td class="right">
            <form method="post" action="<s:url action='socialLinkDelete'/>" class="inline" data-confirm="Xóa liên kết này?">
              <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
              <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
              <input type="hidden" name="id" value="<s:property value='#l.id'/>">
              <button class="btn sm danger" type="submit">Xóa</button>
            </form>
          </td>
        </tr>
      </s:iterator>
      </tbody>
    </table>
    </div>
  </s:else>
</section>
<%@ include file="_footer.jspf" %>
