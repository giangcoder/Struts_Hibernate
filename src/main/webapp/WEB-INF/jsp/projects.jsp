<%@ include file="_header.jspf" %>
<div class="page-head">
  <h1>Dự án</h1>
  <a class="btn primary" href="<s:url action='projectEdit'/>">+ Tạo dự án</a>
</div>
<s:if test="projects.isEmpty()">
  <div class="empty">
    <h2>Chưa có dự án</h2>
    <p class="muted">Tạo dự án, nhập thông tin thương hiệu một lần, rồi dán danh sách nền tảng cần tạo hồ sơ.</p>
    <a class="btn primary" href="<s:url action='projectEdit'/>">Tạo dự án đầu tiên</a>
  </div>
</s:if>
<s:else>
  <div class="cards">
    <s:iterator value="projects" var="p">
      <s:set var="pg" value="progress[#p.id]"/>
      <a class="card project-card" href="<s:url action='platforms'><s:param name='projectId' value='#p.id'/></s:url>">
        <div class="pc-cover"><s:if test="#p.coverUrl != null"><img src="<s:property value='#p.coverUrl'/>" alt=""></s:if></div>
        <div class="pc-body">
          <div class="pc-title">
            <s:if test="#p.logoUrl != null"><img src="<s:property value='#p.logoUrl'/>" alt=""></s:if>
            <b><s:property value="#p.brandName"/></b>
          </div>
          <div class="muted small"><s:property value="#p.website"/></div>
          <div class="small"><s:property value="#pg[0]"/> / <s:property value="#pg[1]"/> nền tảng hoàn tất</div>
          <div class="bar"><span style="width: <s:property value='#pg[1] == 0 ? 0 : (#pg[0] * 100 / #pg[1])'/>%"></span></div>
        </div>
      </a>
    </s:iterator>
  </div>
</s:else>
<%@ include file="_footer.jspf" %>
