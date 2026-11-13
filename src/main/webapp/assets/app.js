(function () {
  function toast(msg) {
    var t = document.querySelector('.toast');
    if (!t) { t = document.createElement('div'); t.className = 'toast'; document.body.appendChild(t); }
    t.textContent = msg; t.classList.add('show');
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, 1400);
  }

  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-copy]');
    if (c) {
      var text = c.getAttribute('data-copy');
      (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject())
        .then(function () { toast('Đã sao chép'); })
        .catch(function () {
          var ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta);
          ta.select(); try { document.execCommand('copy'); toast('Đã sao chép'); } catch (x) {} ta.remove();
        });
      return;
    }
    var f = e.target.closest('[data-fill]');
    if (f) {
      var input = document.querySelector('[name="' + f.getAttribute('data-fill') + '"]');
      if (input) { input.value = f.getAttribute('data-value'); input.focus(); toast('Đã điền username'); }
    }
  });

  // Xác nhận trước khi gửi form nguy hiểm
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var msg = form.getAttribute('data-confirm');
    if (!msg) {
      var b = form.querySelector('[data-confirm-if-delete]');
      var sel = form.querySelector('[name="bulkStatus"]');
      if (b && sel && sel.value === '__delete') msg = b.getAttribute('data-confirm-if-delete');
    }
    if (msg && !confirm(msg)) e.preventDefault();
  });

  // Chọn tất cả
  document.querySelectorAll('[data-check-all]').forEach(function (all) {
    all.addEventListener('change', function () {
      var form = all.closest('form');
      form.querySelectorAll('input[type=checkbox][name="' + all.getAttribute('data-check-all') + '"]')
        .forEach(function (cb) { cb.checked = all.checked; });
    });
  });

  // Xem trước logo / cover
  document.querySelectorAll('[data-preview]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var img = document.getElementById(inp.getAttribute('data-preview'));
      var v = inp.value.trim();
      if (/^https?:\/\//i.test(v)) img.src = v; else img.removeAttribute('src');
    });
  });

  // Đếm ký tự
  document.querySelectorAll('.counter[data-for]').forEach(function (c) {
    var ta = document.getElementById(c.getAttribute('data-for'));
    function upd() { c.textContent = ta.value.length + ' ký tự'; }
    ta.addEventListener('input', upd); upd();
  });

  // Ẩn/hiện danh sách liên kết social theo lựa chọn "Có hỗ trợ"
  var sel = document.getElementById('socialSel'), box = document.getElementById('socialBox');
  if (sel && box) {
    var upd = function () { box.style.display = sel.value === 'Có hỗ trợ' ? '' : 'none'; };
    sel.addEventListener('change', upd); upd();
  }
})();

// Tạo nút "Tự điền" (bookmarklet) từ dữ liệu dự án
(function () {
  var a = document.getElementById('autofillLink');
  if (!a) return;
  var data = a.getAttribute('data-autofill');
  fetch(a.ownerDocument.querySelector('script[src*="app.js"]').src.replace('app.js', 'autofill.js'))
    .then(function (r) { return r.text(); })
    .then(function (code) {
      a.href = 'javascript:' + encodeURIComponent('(' + code.trim() + ')(' + data + ');void 0');
    });
  a.addEventListener('click', function (e) {
    e.preventDefault();
    alert('Kéo nút này lên thanh dấu trang, rồi bấm nó trên trang đăng ký của nền tảng.');
  });
})();
