# MuaLikes.Net → WordPress

Bản dựng lại https://mualikes.net/ trên WordPress, gồm **theme `mualikes`** (giao diện) và **plugin `mualikes-core`** (tính năng).

```
mualikes-wordpress/
├── theme/mualikes/            → copy vào wp-content/themes/
├── plugin/mualikes-core/      → copy vào wp-content/plugins/
│   └── data/                  → dữ liệu trích xuất từ site gốc (dịch vụ, server, giá, blog…)
└── dist/                      → mualikes-theme.zip, mualikes-core.zip (upload qua wp-admin)
```

## 1. Cài đặt

1. WordPress ≥ 6.0, PHP ≥ 7.4, MySQL/MariaDB. **Cài WordPress ở gốc tên miền** (VD `https://domain.com/`), vì JS gốc gọi đường dẫn tuyệt đối `/server/graph-api`, `/auth/login`.
2. Upload `dist/mualikes-core.zip` (Plugins → Add New → Upload) và kích hoạt.
3. Upload `dist/mualikes-theme.zip` (Appearance → Themes → Upload) và kích hoạt.
4. Nhập dữ liệu:
   - WP-CLI (khuyên dùng): `wp mualikes import --images` (`--images` tải ảnh từ mualikes.net về `uploads/mualikes/`).
   - Hoặc wp-admin → **MuaLikes → Nhập dữ liệu**.
5. Settings → Permalinks → bấm **Save** một lần (importer đã đặt `/blog/%postname%/`).

Sau khi nhập: 9 nhóm + nhóm VIP, **63 dịch vụ**, **9 dịch vụ VIP**, **437 server** (đúng ID, tên, ghi chú, min/max, trạng thái, **giá 3 cấp** Thành viên/CTV/Đại lý), 15 thông báo, 10 câu hỏi thường gặp, 4 trang tĩnh, **272 bài blog** (chuyên mục, thẻ, ảnh đại diện, SEO title/description).

## 2. Đường dẫn giữ nguyên như site gốc

| URL | Nội dung |
|---|---|
| `/` | Trang chủ (lưới dịch vụ, cấp bậc, thông báo, đơn hàng mới) |
| `/tang-like-bai-viet-facebook/` … | 63 trang dịch vụ (form tạo đơn + bài viết SEO + mục lục) |
| `/other-service/facebook/vip-like/` … | 9 trang VIP theo tháng |
| `/bang-gia-dich-vu/` | Bảng giá & cấp bậc |
| `/blog/`, `/blog/{bai-viet}/`, `/category/{x}/`, `/tag/{x}/` | Blog |
| `/gioi-thieu/`, `/lien-he/`, `/chinh-sach-bao-mat/`, `/dieu-khoan-dich-vu/` | Trang tĩnh |
| `/auth/login/`, `/auth/register/`, `/auth/forgot-password/` | Đăng nhập / đăng ký / quên mật khẩu |
| `/auth/profile/`, `/auth/payment/`, `/auth/order-list/`, `/auth/transactions/`, `/auth/api/`, `/auth/affiliate/` | Trang thành viên |
| `/docs/document/` | Tài liệu API |
| `/server/graph-api?action=…`, `/server/orders`, `/server/order-list` … | Endpoint AJAX (tương thích JS gốc) |
| `/api/v2` | API đại lý chuẩn SMM Panel v2 |
| `/server/webhook/sepay`, `/server/webhook/casso` | Webhook nạp tiền tự động |

## 3. Tính năng

- **Giao diện**: dùng nguyên AdminLTE 3 + `app.css` + JS gốc (`common.js`, `custom.js`, `service.js`, `order.js`, `custom-service.js`), toàn bộ icon/logo. Menu, băng "Cảnh báo vi phạm", nút Hỗ trợ nổi, Google Translate, mobile.
- **Tạo đơn**: chọn server, cảm xúc (1 hoặc nhiều), bình luận theo dòng, mua nhiều link (≤50), tuỳ chỉnh số lượng từng link (`link | số lượng`), hẹn giờ mua nhiều lần, loại video (hệ số giá), thời gian duy trì mắt livestream, đánh giá Google Maps.
- **VIP theo tháng**: gói, số ngày, số bài/ngày (×2, ×3, ×4), gia hạn, hủy (hoàn tiền ngày còn lại trừ 7 ngày phí).
- **Ví & cấp bậc**: số dư trừ/cộng nguyên tử (không trừ trùng), tự lên cấp theo tổng nạp, giá riêng từng cấp, admin có thể gán cấp cố định / khoá tài khoản.
- **Nạp tiền**: QR VietQR + nội dung `mualikes {ID}`; webhook **SePay**/**Casso** tự cộng tiền, chống cộng trùng; **khuyến mãi % theo khoảng ngày**; thẻ cào (admin duyệt); USDT (thủ công).
- **Nhà cung cấp**: kết nối bất kỳ panel chuẩn SMM v2 (`add`, `status`, `refill`, `cancel`, `balance`), gắn từng server với mã dịch vụ bên NCC. Cron 5 phút đồng bộ trạng thái, **tự hoàn tiền** khi Hủy / Hoàn một phần, hết hạn VIP, chạy các đơn hẹn giờ. Server không gắn NCC → admin xử lý tay.
- **Bảo hành**: nút Bảo hành trong lịch sử (gọi `refill` bên NCC), giới hạn số lần bị từ chối.
- **API đại lý** `/api/v2`: `services`, `add`, `status` (1 hoặc nhiều đơn), `refill`, `balance`.
- **Admin** (menu **MuaLikes**): tổng quan doanh thu, đơn hàng (đổi trạng thái, gửi lại NCC, ghi chú), thành viên (cộng/trừ tiền), nạp tiền (duyệt), giao dịch, nhà cung cấp, cài đặt (logo, màu, ngân hàng, khuyến mãi, FAQ, liên hệ, cấp bậc, băng cảnh báo…). Mỗi dịch vụ có bảng server sửa trực tiếp và HTML form/JS riêng.
- **Bảo mật**: nonce chống CSRF cho mọi thao tác tiền/đơn, giới hạn tần suất đăng nhập/đăng ký, thành viên không vào được wp-admin.

## 4. Cấu hình sau khi cài

1. **MuaLikes → Cài đặt → Nạp tiền**: ngân hàng (mã VietQR: `MB`, `VCB`, `ACB`…), số TK, chủ TK, khuyến mãi.
2. Webhook: SePay → URL `https://domain/server/webhook/sepay`, kiểu xác thực **API Key** = giá trị "SePay API Key". Casso → `https://domain/server/webhook/casso` + Secure Token.
3. **MuaLikes → Nhà cung cấp**: thêm API URL + key, bấm "Kiểm tra số dư"; rồi vào **Dịch vụ SMM → sửa dịch vụ** để gắn NCC + mã dịch vụ cho từng server.
4. Cron thật (khuyên dùng): thêm `define('DISABLE_WP_CRON', true);` vào wp-config.php và
   ```
   */5 * * * * wget -q -O - https://domain/wp-cron.php?doing_wp_cron >/dev/null 2>&1
   ```
5. WP-CLI hữu ích: `wp mualikes credit <user> 100000 --deposit`, `wp mualikes cron`.

## 5. Khác biệt cần biết

- Site gốc chỉ xem được phần công khai. **Các trang sau đăng nhập** (hồ sơ, nạp tiền, lịch sử, API) và **logic máy chủ** được viết mới theo cùng phong cách AdminLTE và đúng định dạng dữ liệu mà JS gốc gửi/nhận, không phải bản sao mã PHP gốc.
- Trang `/docs/document/` của site gốc là bản Postman nhúng đang lỗi; bản này là trang tài liệu API viết lại.
- "Lấy UID Facebook" từ link rút gọn (`fb.watch`, `/share/`) chỉ cố gắng theo redirect; nếu Facebook chặn, đơn dùng nguyên link.
- Giá, server, bài viết là ảnh chụp tại thời điểm trích xuất (19/11/2026). Ảnh trong bài viết lấy từ mualikes.net cho đến khi chạy import với `--images`.
