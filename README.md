# Hồ sơ Web

Ứng dụng quản lý việc tạo hồ sơ **Social, Entity, Forum** và các nền tảng khác cho từng website / thương hiệu.
Nhập thông tin thương hiệu một lần, dán danh sách nền tảng, xử lý từng mục hoặc hàng loạt, rồi bàn giao URL hồ sơ thực tế,
trạng thái và CSV. Có gửi URL lập chỉ mục qua Sinbyte.

**Công nghệ:** Java 17+, Struts 7, Hibernate 6, H2 (mặc định), JSP. Dữ liệu lưu trên máy chủ theo **tài khoản**,
nên mở được cùng dự án từ mọi trình duyệt.

## Chạy

```bash
mvn jetty:run
```

Mở http://localhost:8080 → **Đăng ký** tài khoản → **Tạo dự án**.

Đóng gói WAR để chạy trên Tomcat 10.1+ / Jetty 12 (Jakarta EE 10):

```bash
mvn package    # → target/ho-so-web.war
```

Chạy với khóa Sinbyte thật (macOS / Linux):

```bash
SINBYTE_API_KEY='khóa-của-bạn' mvn jetty:run
```

Windows PowerShell:

```powershell
$env:SINBYTE_API_KEY='khóa-của-bạn'; mvn jetty:run
```

Không ghi khóa vào mã nguồn hay commit lên git.

### Biến môi trường

| Biến | Ý nghĩa |
|---|---|
| `HOSOWEB_DB_URL` | JDBC URL. Mặc định `jdbc:h2:file:./data/hosoweb` (thư mục `data/` cạnh nơi chạy). |
| `HOSOWEB_DB_USER`, `HOSOWEB_DB_PASSWORD` | Tài khoản CSDL (mặc định `sa` / rỗng). |
| `SINBYTE_API_KEY` | Khóa Sinbyte bí mật trên máy chủ. Khi có, để trống ô khóa trên form gửi. |
| `SINBYTE_API_URL` | Endpoint Sinbyte, mặc định `https://app.sinbyte.com/api/indexing/`. |
| `SINBYTE_MOCK=true` | Trả phản hồi mô phỏng, không gọi Sinbyte thật (dùng để thử). |

## Cách dùng

1. **Tạo dự án**: tên thương hiệu, website, logo, ảnh cover, tiêu đề, mô tả ngắn / đầy đủ, từ khóa SEO, hashtag,
   ba username theo thứ tự ưu tiên. Thị trường / ngôn ngữ mặc định **Việt Nam · Tiếng Việt**.
   Thông tin theo dự án: Gmail riêng và trạng thái kết nối, người quản lý, ngày sinh, số nhận SMS, người xử lý mã,
   ngành nghề, quy mô, loại hình; email / số điện thoại công khai, địa chỉ, giờ hoạt động, Google Maps.
2. **Danh sách → Dán danh sách**: mỗi dòng `Tên`, `URL` hoặc `Tên | Nhóm | URL` (Tab cũng được).
   Nhóm bỏ trống sẽ tự đoán theo tên miền. Dòng trùng bị bỏ qua. Tích **hồ sơ đang sở hữu** để lưu URL vào
   *Link hồ sơ hiện có* kèm trạng thái quyền quản trị. Mỗi dòng hiện lý do nếu thiếu dữ liệu (thiếu URL đăng ký, chưa có username, …).
3. **CAPTCHA (làm thủ công):** đánh dấu nền tảng cần CAPTCHA bằng `| captcha` cuối dòng khi dán, ô tích khi dán,
   thao tác hàng loạt trên bảng, hoặc ô tích trong màn hình Tạo. **Tạo hàng loạt** chỉ đi qua mục *không* CAPTCHA;
   **Hàng loạt mục CAPTCHA** đi qua các mục đã đánh dấu để bạn xử lý thủ công.
4. **⚡ Tự điền:** trên màn hình Tạo, kéo nút *Tự điền* lên thanh dấu trang (một lần cho mỗi dự án). Trên trang đăng ký của nền tảng,
   bấm dấu trang đó để điền email, username, họ tên, tên thương hiệu, website, điện thoại, mô tả… Một khung nhỏ cho đổi
   username ưu tiên 1/2/3. Script **không** điền mật khẩu, **không** đụng ô CAPTCHA / mã xác minh và **không** tự bấm gửi.
5. **Tạo** (từng dòng) hoặc **Tạo hàng loạt** (lần lượt các mục *Chưa tạo / Đang xử lý / Chờ xác minh / Chờ CAPTCHA*):
   màn hình hiển thị nội dung đã lưu với nút **Sao chép**, username theo thứ tự (nút **Dùng**), logo, cover, Gmail / SMS,
   và nút mở trang đăng ký. Người dùng tự điền, gửi và xác minh trên trang của nền tảng.
6. Lưu username thực tế, URL hồ sơ, trạng thái, bước cần làm, ghi chú. **Hoàn tất chỉ được chọn khi có URL hồ sơ thực tế**
   (kiểm tra cả ở máy chủ). Lưu lại một mục đã hoàn tất không đổi thời điểm hoàn tất.
7. **Liên kết social**: lưu các URL social; khi cập nhật nền tảng chọn **Có hỗ trợ** và đánh dấu link đã gắn → xuất trong CSV.
8. **Kết quả**: bảng hồ sơ hoàn tất, **Tải CSV kết quả** và **Tải CSV tiến độ** (UTF-8 có BOM, mở thẳng bằng Excel).
9. **Submit index**: xem trước URL hoàn tất chưa gửi (tối đa 100 / lượt), chọn và gửi. Kết quả ghi trạng thái
   *Đã gửi Sinbyte* / *Gửi lỗi* cùng mã tác vụ nếu Sinbyte trả về. Đổi URL hồ sơ sẽ đặt lại trạng thái gửi.

## Bảo mật

Mật khẩu băm bằng BCrypt; chống session fixation khi đăng nhập; mọi POST yêu cầu token CSRF; mọi truy vấn lọc theo chủ sở hữu;
chỉ nhận URL `http(s)`; HTML được escape; ô CSV bắt đầu bằng `= + - @` được vô hiệu hóa công thức.

## Kiểm thử

```bash
mvn test
```

## Trạng thái so với hồ sơ bàn giao

Đã làm: toàn bộ phần **"Cách dùng bản hiện tại"**, Submit index Sinbyte, lưu trữ theo tài khoản (thay cho lưu trong trình duyệt),
trạng thái *Chờ xác minh* / *Chờ CAPTCHA* kèm *bước cần làm*, chống tạo trùng.

Chưa làm:
- **Đăng ký tự động** (robot tự điền form trên nền tảng), **đọc OTP từ Gmail** theo từng dự án, khôi phục phiên trình duyệt đang chờ CAPTCHA.
  Các mục này cần trình điều khiển trình duyệt và adapter riêng cho từng nền tảng, OAuth Gmail (Google Cloud project, phạm vi `gmail.readonly`),
  và cần xem điều khoản của từng nền tảng: nhiều nơi cấm tạo tài khoản tự động.
- Nhập dữ liệu từ bản cũ lưu trong trình duyệt: cần định dạng dữ liệu (localStorage) của bản cũ để viết bộ chuyển.
- Định dạng request / response Sinbyte được viết theo tài liệu công khai nhưng **chưa thử với khóa thật**. Endpoint đổi được qua `SINBYTE_API_URL`.
