# Hồ sơ Web

Ứng dụng quản lý việc tạo hồ sơ **Social, Entity, Forum** và các nền tảng khác cho từng website / thương hiệu.
Nhập thông tin thương hiệu một lần, dán danh sách nền tảng, xử lý từng mục hoặc hàng loạt, rồi bàn giao URL hồ sơ thực tế,
trạng thái và CSV. Có gửi URL lập chỉ mục qua Sinbyte.

**Công nghệ:** Java 17+, Struts 7, Hibernate 6, H2 (mặc định), JSP. Dữ liệu lưu trên máy chủ theo **tài khoản**,
nên mở được cùng dự án từ mọi trình duyệt.

## Chạy

Chỉ cần **Java 17+** (không cần cài Maven — `./mvnw` tự tải). Kiểm tra: `java -version`.
macOS chưa có Java: `brew install openjdk@21` hoặc tải bản Temurin tại adoptium.net.

```bash
./mvnw jetty:run
```

Mở http://localhost:8080 → **Đăng ký** tài khoản → **Tạo dự án**.

Đóng gói WAR để chạy trên Tomcat 10.1+ / Jetty 12 (Jakarta EE 10):

```bash
./mvnw package    # → target/ho-so-web.war
```

Chạy với khóa Sinbyte thật (macOS / Linux):

```bash
SINBYTE_API_KEY='khóa-của-bạn' ./mvnw jetty:run
```

Windows PowerShell:

```powershell
$env:SINBYTE_API_KEY='khóa-của-bạn'; .\mvnw.cmd jetty:run
```

Không ghi khóa vào mã nguồn hay commit lên git.

### Biến môi trường

| Biến | Ý nghĩa |
|---|---|
| `HOSOWEB_DB_URL` | JDBC URL. Mặc định `jdbc:h2:file:./data/hosoweb` (thư mục `data/` cạnh nơi chạy). |
| `HOSOWEB_DB_USER`, `HOSOWEB_DB_PASSWORD` | Tài khoản CSDL (mặc định `sa` / rỗng). |
| `SINBYTE_API_KEY` | Khóa Sinbyte bí mật trên máy chủ. Khi có, để trống ô khóa trên form gửi. |
| `SINBYTE_API_URL` | Endpoint Sinbyte, mặc định `https://app.sinbyte.com/api/indexing/`. |
| `HOSOWEB_BROWSER_PATH` | Đường dẫn trình duyệt cho bộ chạy (mặc định: Google Chrome đã cài, nếu không có thì Chromium). |
| `HOSOWEB_BROWSER_PROFILE` | Thư mục hồ sơ trình duyệt của bộ chạy, mặc định `data/browser-profile`. |
| `SINBYTE_MOCK=true` | Trả phản hồi mô phỏng, không gọi Sinbyte thật (dùng để thử). |

## Cách dùng — 4 bước

1. **Dự án**: chỉ nhập **Tên** và **Website**. Ứng dụng tự gợi ý 3 username từ tên miền / tên thương hiệu.
2. **Hồ sơ**: logo, banner, tiêu đề, mô tả ngắn / đầy đủ, từ khóa, hashtag, username, **liên kết** (mỗi dòng một URL),
   liên hệ. Phần *Thông tin đăng ký* (Gmail đăng ký, SMS, ngày sinh, ngành nghề…) nằm trong mục thu gọn.
3. **Tạo profile**: dán danh sách nền tảng (mỗi dòng `URL đăng ký` hoặc `Tên | Nhóm | URL`, thêm `| captcha` nếu có CAPTCHA),
   rồi bấm **▶ Chạy**. Ứng dụng mở Chrome trên máy, lần lượt vào từng trang đăng ký và tự điền thông tin. Khung điều khiển
   góc phải trên: **✅ Đây là trang hồ sơ** (lưu URL + username, Hoàn tất, sang trang kế tiếp), **⚡ Tự điền lại**,
   **⏳ Chờ xác minh**, **🧩 Chờ CAPTCHA**, **⏭ Bỏ qua** (hoặc đóng tab), **⛔ Dừng**. Bạn chỉ nhập mật khẩu, giải CAPTCHA,
   bấm Gửi, xác minh email. Hồ sơ trình duyệt lưu ở `data/browser-profile` nên các đăng nhập được giữ.
   Nút **Làm thủ công** cho phép xử lý từng mục không qua trình duyệt tự động (sao chép nội dung, nút ⚡ Tự điền dạng dấu trang).
4. **Kết quả**: danh sách profile hoàn thành (URL, username, thời điểm), **Tải CSV kết quả / tiến độ**, **Submit index** qua Sinbyte
   (tối đa 100 URL / lượt, ghi trạng thái và mã tác vụ).

Chỉ đánh dấu Hoàn tất khi có URL hồ sơ thực tế; chạy lại không tạo trùng các mục đã hoàn tất.

## Bảo mật

Mật khẩu băm bằng BCrypt; chống session fixation khi đăng nhập; mọi POST yêu cầu token CSRF; mọi truy vấn lọc theo chủ sở hữu;
chỉ nhận URL `http(s)`; HTML được escape; ô CSV bắt đầu bằng `= + - @` được vô hiệu hóa công thức.

## Kiểm thử

```bash
./mvnw test
```

## Trạng thái so với hồ sơ bàn giao

Đã làm: toàn bộ phần **"Cách dùng bản hiện tại"**, Submit index Sinbyte, lưu trữ theo tài khoản (thay cho lưu trong trình duyệt),
trạng thái *Chờ xác minh* / *Chờ CAPTCHA* kèm *bước cần làm*, chống tạo trùng.

Chưa làm:
- **Tự bấm gửi / tự xác minh**: bộ chạy tự mở trang và tự điền, nhưng việc gửi form, CAPTCHA và xác minh email do người dùng làm.
  **Đọc OTP từ Gmail** theo từng dự án chưa có.
  Các mục này cần trình điều khiển trình duyệt và adapter riêng cho từng nền tảng, OAuth Gmail (Google Cloud project, phạm vi `gmail.readonly`),
  và cần xem điều khoản của từng nền tảng: nhiều nơi cấm tạo tài khoản tự động.
- Nhập dữ liệu từ bản cũ lưu trong trình duyệt: cần định dạng dữ liệu (localStorage) của bản cũ để viết bộ chuyển.
- Định dạng request / response Sinbyte được viết theo tài liệu công khai nhưng **chưa thử với khóa thật**. Endpoint đổi được qua `SINBYTE_API_URL`.
