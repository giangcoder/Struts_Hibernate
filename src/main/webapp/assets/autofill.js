function (D) {
  /* Hồ sơ Web — tự điền form đăng ký. Không điền mật khẩu, không đụng CAPTCHA, không tự bấm gửi. */
  var U = D.usernames || [];
  var ui = 0;
  var filled = [];
  function low(s) { return (s || '').toString().toLowerCase(); }
  function labelOf(el) {
    var t = '';
    if (el.id) { var l = document.querySelector('label[for="' + (window.CSS && CSS.escape ? CSS.escape(el.id) : el.id) + '"]'); if (l) t += ' ' + l.textContent; }
    var p = el.closest('label'); if (p) t += ' ' + p.textContent;
    var lb = el.getAttribute('aria-labelledby');
    if (lb) lb.split(/\s+/).forEach(function (i) { var x = document.getElementById(i); if (x) t += ' ' + x.textContent; });
    return t;
  }
  function desc(el) {
    return low([el.name, el.id, el.placeholder, el.getAttribute('aria-label'), el.getAttribute('autocomplete'),
      el.getAttribute('data-testid'), labelOf(el)].join(' '));
  }
  function visible(el) {
    var r = el.getBoundingClientRect(); var cs = getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
  }
  function setVal(el, v) {
    var proto = el.tagName === 'TEXTAREA' ? HTMLTextAreaElement.prototype : HTMLInputElement.prototype;
    var setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
    setter.call(el, v);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    el.style.outline = '2px solid #2457d6'; el.style.outlineOffset = '1px';
  }
  var nameParts = (D.manager || '').trim().split(/\s+/);
  var given = nameParts.length > 1 ? nameParts[nameParts.length - 1] : (D.manager || '');
  var family = nameParts.length > 1 ? nameParts.slice(0, -1).join(' ') : '';
  /* thứ tự quan trọng: quy tắc cụ thể trước */
  var rules = [
    [/captcha|recaptcha|hcaptcha|otp|verification.?code|mã xác/, null],
    [/pass(word)?|mật khẩu|pwd/, null],
    [/e-?mail|thư điện tử/, 'email'],
    [/user.?name|login|handle|nick|screen.?name|tên đăng nhập|tên người dùng|username/, 'username'],
    [/first.?name|given.?name|fname|tên riêng/, 'given'],
    [/last.?name|family.?name|surname|lname/, 'family'],
    [/birth|dob|bday|ngày sinh/, 'birthDate'],
    [/phone|tel|mobile|số điện thoại|điện thoại|sđt/, 'phone'],
    [/web.?site|homepage|url|site|link|trang web/, 'website'],
    [/company|organi[sz]ation|business|brand|công ty|doanh nghiệp|thương hiệu|page.?name|display.?name|full.?name|your.?name|^\s*name\b|\bname\b|họ tên|tên/, 'brand'],
    [/headline|tagline|slogan|title|tiêu đề/, 'title'],
    [/bio|about|description|summary|intro|mô tả|giới thiệu/, 'desc'],
    [/keyword|tags?\b|từ khóa/, 'keywords'],
    [/hashtag/, 'hashtags'],
    [/address|street|địa chỉ/, 'address'],
    [/industry|category|ngành/, 'industry'],
    [/hours|giờ/, 'hours']
  ];
  function valueFor(key, el) {
    switch (key) {
      case 'username': return U[ui] || '';
      case 'given': return given;
      case 'family': return family;
      case 'desc': return el.tagName === 'TEXTAREA' ? (D.full || D.short || '') : (D.short || D.full || '');
      case 'birthDate': return D.birthDate || '';
      default: return D[key] || '';
    }
  }
  function run() {
    filled = [];
    window.__hosowebUser = U[ui] || '';
    var els = document.querySelectorAll('input, textarea');
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      var type = low(el.type);
      if (el.disabled || el.readOnly || !visible(el)) continue;
      if (/^(hidden|submit|button|checkbox|radio|file|image|reset|password|range|color)$/.test(type)) continue;
      var d = desc(el), key = null;
      if (type === 'email') key = 'email';
      else if (type === 'tel') key = 'phone';
      else if (type === 'url') key = 'website';
      else if (type === 'date') key = /birth|dob|sinh/.test(d) ? 'birthDate' : null;
      if (!key) {
        for (var r = 0; r < rules.length; r++) {
          if (rules[r][0].test(d)) { key = rules[r][1]; if (key === null) key = '__skip'; break; }
        }
      }
      if (!key || key === '__skip') continue;
      if (key === 'birthDate' && type !== 'date' && D.birthDate) {
        var b = D.birthDate.split('-'); /* yyyy-mm-dd → dd/mm/yyyy cho ô văn bản */
        if (b.length === 3) { setVal(el, b[2] + '/' + b[1] + '/' + b[0]); filled.push(key); continue; }
      }
      var v = valueFor(key, el);
      if (!v) continue;
      if (el.value && el.value !== '' && key !== 'username') continue; /* không ghi đè dữ liệu đã có */
      setVal(el, v); filled.push(key);
    }
    panel();
  }
  function panel() {
    var p = document.getElementById('__hosoweb_panel');
    if (!p) {
      p = document.createElement('div'); p.id = '__hosoweb_panel';
      p.style.cssText = 'position:fixed;z-index:2147483647;right:16px;bottom:16px;max-width:320px;background:#17202b;color:#fff;font:13px/1.45 system-ui,sans-serif;padding:12px 14px;border-radius:10px;box-shadow:0 6px 24px rgba(0,0,0,.3)';
      document.body.appendChild(p);
    }
    p.textContent = '';
    var h = document.createElement('div'); h.style.fontWeight = '600';
    h.textContent = 'Hồ sơ Web · đã điền ' + filled.length + ' ô'; p.appendChild(h);
    var t = document.createElement('div'); t.style.margin = '4px 0 8px'; t.style.opacity = '.85';
    t.textContent = 'Tự nhập mật khẩu, giải CAPTCHA, kiểm tra lại rồi bấm Gửi. Username đang dùng: ' + (U[ui] || '—'); p.appendChild(t);
    if (U.length > 1) {
      U.forEach(function (u, i) {
        var bt = document.createElement('button'); bt.type = 'button'; bt.textContent = (i + 1) + '. ' + u;
        bt.style.cssText = 'margin:0 6px 6px 0;padding:3px 8px;border-radius:6px;border:1px solid #6d95ff;background:' + (i === ui ? '#6d95ff' : 'transparent') + ';color:#fff;cursor:pointer;font:inherit';
        bt.onclick = function () { ui = i; (window.__hosowebFill || run)(); };
        p.appendChild(bt);
      });
    }
    var x = document.createElement('button'); x.type = 'button'; x.textContent = 'Đóng';
    x.style.cssText = 'display:block;margin-top:4px;background:none;border:0;color:#9fb6ff;cursor:pointer;padding:0;font:inherit';
    x.onclick = function () { p.remove(); };
    p.appendChild(x);
  }
  window.__hosowebFill = run;
  run();
}
