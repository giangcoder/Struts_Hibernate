<%@ include file="_header.jspf" %>
<s:set var="tab" value="'sinbyte'"/>
<%@ include file="_tabs.jspf" %>
<section class="panel">
  <h2>Submit index qua Sinbyte</h2>
  <ul class="small muted">
    <li>Xem trước các URL đã hoàn tất và chưa gửi. Mỗi lượt tối đa <s:property value="maxUrls"/> URL.</li>
    <li>Sinbyte có thể trừ credit khi tiếp nhận tác vụ. <b>Đã gửi Sinbyte</b> nghĩa là đã gửi yêu cầu — xem kết quả lập chỉ mục trong lịch sử Sinbyte.</li>
  </ul>
  <s:if test="sinbyteMock"><div class="flash info">Đang chạy chế độ mô phỏng (SINBYTE_MOCK=true) — không gọi Sinbyte thật.</div></s:if>
  <s:elseif test="serverKeyConfigured"><div class="flash ok">Khóa Sinbyte đã được cấu hình trên máy chủ. Để trống ô khóa khi gửi.</div></s:elseif>
  <s:else><div class="flash err">Máy chủ chưa cấu hình khóa Sinbyte (biến môi trường SINBYTE_API_KEY). Bạn có thể nhập khóa cho riêng lượt gửi này.</div></s:else>

  <s:if test="platforms.isEmpty()">
    <p class="muted">Không có URL hoàn tất nào chưa gửi.</p>
  </s:if>
  <s:else>
  <form method="post" action="<s:url action='sinbyteSubmit'/>" data-confirm="Gửi các URL đã chọn tới Sinbyte? Sinbyte có thể trừ credit.">
    <input type="hidden" name="csrf" value="<s:property value='csrf'/>">
    <input type="hidden" name="projectId" value="<s:property value='project.id'/>">
    <div class="table-wrap">
    <table class="tbl">
      <thead><tr><th class="w-check"><input type="checkbox" data-check-all="ids" checked></th><th>Nền tảng</th><th>URL</th><th>Lần gửi trước</th></tr></thead>
      <tbody>
      <s:iterator value="platforms" var="pl">
        <tr>
          <td class="w-check"><input type="checkbox" name="ids" value="<s:property value='#pl.id'/>" checked></td>
          <td><s:property value="#pl.name"/></td>
          <td class="ellipsis"><s:property value="#pl.profileUrl"/></td>
          <td class="small"><s:if test="#pl.sinbyteStatus != null"><span class="badge err"><s:property value="#pl.sinbyteStatus"/></span> <s:property value="#pl.sinbyteSentAtText"/></s:if></td>
        </tr>
      </s:iterator>
      </tbody>
    </table>
    </div>
    <label>Khóa API Sinbyte (để trống để dùng khóa trên máy chủ)
      <input type="password" name="apiKey" autocomplete="off"></label>
    <div class="actions"><button class="btn primary" type="submit">Gửi <s:property value="platforms.size()"/> URL</button></div>
  </form>
  </s:else>
</section>
<%@ include file="_footer.jspf" %>
