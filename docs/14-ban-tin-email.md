# Bản tin & Email — Phase 2 (khảo sát câu 30–31, kèm 29)

Phiên 25/09/2026. Nhánh `phase2-ban-tin-email`.

| Loại thư | Khi nào | Gửi cho ai |
|---|---|---|
| **Bản tin định kỳ** (câu 30) | hằng tuần hoặc hằng tháng, giờ BQT chọn | thành viên có `nntm_nhan_ban_tin = '1'` |
| **Dịp đặc biệt** (câu 31) | đúng ngày lễ (âm hoặc dương lịch) | như trên |
| **Xác nhận Khóa Tu** (câu 29) | ngay sau khi đăng ký | người vừa đăng ký |
| Thư thử | BQT bấm "Gửi thử cho tôi" | chính BQT |

Đúng câu 30: **không** gửi mỗi khi có bài mới, **không** cho chọn chuyên mục theo dõi.

---

## 1. BQT dùng thế nào

wp-admin → **Bản tin & Email**

- **Cài đặt bản tin**: tần suất (mặc định **Tắt**), ngày/giờ gửi, loại nội dung,
  số bài, tiêu đề, lời mở đầu, người gửi, tốc độ. Nút **Xem trước**, **Gửi thử
  cho tôi**, **Gửi bản tin ngay**.
- **Dịp đặc biệt**: mỗi dịp một bài — tên, nội dung thư (trình soạn thảo cổ
  điển), ảnh đầu thư, ô bên phải chọn âm/dương lịch + ngày/tháng, hiện luôn
  **ngày gửi lần tới**. Chỉ dịp **Đã đăng** mới được gửi.
- **Nhật ký gửi**: từng lần gửi, số người nhận / đã gửi / lỗi, bấm tiêu đề xem
  đúng thư đã gửi, nút **Dừng** cho thư đang gửi.

## 2. Đường gửi — CHƯA GỬI THẬT cho tới khi cấu hình SMTP

Chưa khai `NNTM_SMTP_HOST` → **chế độ ghi log**: thư được dựng đủ, vào Nhật ký,
xem trước được, nhưng **không đi**. Màn cài đặt có cảnh báo vàng.

Lý do: khách chưa có tên miền (câu 38) → chưa xác thực domain với SES/SendGrid;
`mail()` của VPS gần như chắc chắn vào thư rác.

Khi có tên miền, thêm vào `wp-config.php` (**không** lưu mật khẩu trong DB):

```php
// Amazon SES (vùng Singapore)
define( 'NNTM_SMTP_HOST', 'email-smtp.ap-southeast-1.amazonaws.com' );
define( 'NNTM_SMTP_PORT', 587 );
define( 'NNTM_SMTP_USER', '...' );   // SMTP credentials của SES, không phải access key IAM
define( 'NNTM_SMTP_PASS', '...' );
define( 'NNTM_SMTP_SECURE', 'tls' );

// hoặc SendGrid: HOST 'smtp.sendgrid.net', USER 'apikey', PASS = API key
```

Việc cần làm cùng lúc: xác thực domain (SPF, DKIM, DMARC), đổi **Email người gửi**
trong màn cài đặt sang địa chỉ thuộc domain đó, bấm **Gửi thử cho tôi**.
Ép chế độ: `define( 'NNTM_MAIL_CHE_DO', 'gui_that' | 'ghi_log' );`.

SMTP áp dụng cho **mọi** thư của site (quên mật khẩu, form liên hệ cũng hưởng).

## 3. Cron — bắt buộc trên production

WP-Cron chỉ chạy khi có người vào site. Trên VPS:

```php
define( 'DISABLE_WP_CRON', true );   // wp-config.php
```
```bash
* * * * * curl -s https://<domain>/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

- `nntm_ban_tin_kiem_tra` — mỗi giờ: tới kỳ bản tin / ngày lễ chưa → dựng thư, xếp hàng.
- `nntm_mail_gui_lo` — mỗi phút khi còn hàng đợi: gửi *Số thư mỗi phút* thư (mặc định 40).

## 4. Quy tắc đã chốt trong code

- **Bài khu Hành Giả không bao giờ vào thư.** Người nhận là thành viên, nhưng thư
  bị chuyển tiếp là chuyện thường. Dùng chung `nntm_search_post_acl()` với tìm kiếm.
  Lọc tường minh vì cron chạy bằng CLI thì cổng quyền của theme **mở** (`PHP_SAPI`).
- Chỉ bài ngôn ngữ mặc định (Polylang).
- Kỳ không có bài mới → **không gửi**, ghi lý do ở "Kỳ xét gần nhất".
- **Chống gửi đôi**: mỗi kỳ/dịp một khoá duy nhất (`ban_tin:2026-W39`,
  `dip_le:<id>:<ngày>`), UNIQUE KEY trong DB.
- **Bật lần đầu không bắn ngay**: bật "hằng tuần" vào thứ Ba khi ngày gửi là thứ
  Hai → kỳ đầu là tuần sau. Giờ gửi kỳ này chưa tới thì gửi kỳ này.
- Người huỷ **sau** khi thư đã xếp hàng → không gửi nữa.
- Lỗi gửi → thử lại tối đa 3 lần rồi đánh "lỗi".
- **Huỷ nhận**: link có chữ ký HMAC (gắn với email). Mở link (GET) chỉ hiện nút
  xác nhận — trình quét link của Outlook/antivirus tự mở mọi link, huỷ bằng GET
  là huỷ nhầm hàng loạt. Có `List-Unsubscribe` + huỷ một chạm (RFC 8058), Gmail/Yahoo
  bắt buộc với thư hàng loạt. Trang huỷ có nút "nhận lại".
- Hàng đợi (chứa email) của thư đã xong **xoá sau 180 ngày**; số đếm giữ lại.
- Font thư: **không dùng Georgia** — thiếu glyph tiếng Việt dựng sẵn, dấu bị tách
  rời ("viế t"). Đã thấy thật trên ảnh chụp, đã sửa.

## 5. Âm lịch

`Am_Lich` — thuật toán Hồ Ngọc Đức, múi giờ +7. Đối chiếu:

| Ngày âm | Mong đợi | |
|---|---|---|
| Tết 2023 / 2024 / 2025 / 2026 | 22/01/2023 · 10/02/2024 · 29/01/2025 · 17/02/2026 | ✅ |
| Phật Đản 15/4 — 2024 / 2025 | 22/05/2024 · 12/05/2025 | ✅ |
| Vu Lan 15/7 — 2023 (nhuận tháng 2) / 2024 / 2025 (nhuận tháng 6) | 30/08/2023 · 18/08/2024 · 06/09/2025 | ✅ |
| 15/6 nhuận 2025 | 08/08/2025 | ✅ |
| Rằm tháng Giêng 2026 | 03/03/2026 | ✅ |

## 6. Dữ liệu mẫu

```bash
"C:/xampp8_2/php/php.exe" tools/seed-dip-le.php
```

8 dịp ở trạng thái **Nháp** (meta `_nntm_dip_mau = 1`): Tết, Rằm tháng Giêng, vía
Quán Âm đản sanh (19/2), Phật Đản (15/4), vía Quán Âm thành đạo (19/6), Vu Lan
(15/7), vía A Di Đà (17/11), Phật Thành Đạo (8/12). Nội dung là **lời mẫu**.

## 7. Đã kiểm

- 15 phép thử PHP (chế độ ghi log) + chế độ gửi thật với SMTP trỏ vào cổng chết
  (không thư nào ra ngoài): dựng bản tin, lọc bài Hành Giả (26 bài hạn chế, lọt 0),
  chống trùng, gửi lô, thử lại 3 lần, bỏ qua người đã huỷ, dịp hôm nay chỉ 1 thư dù
  cron chạy 2 lần, `<script>` trong nội dung dịp bị gỡ, lịch tuần khi bật lần đầu,
  header `From` / `List-Unsubscribe` / AltBody.
- HTTP: trang huỷ (GET không huỷ, POST huỷ, nhận lại, một chạm, chữ ký sai → 400).
- Đăng ký Khóa Tu thật qua AJAX → thư xác nhận vào hàng đợi.
- Ảnh chụp thư bản tin (máy tính + điện thoại 390px), thư Khóa Tu, trang huỷ.

⚠️ **Chưa kiểm bằng tay trong wp-admin** (bấm các nút, lưu form) — cần đăng nhập.
⚠️ **Chưa gửi thư thật lần nào** — chờ SMTP.

## 8. Chờ khách / chủ dự án

- **SES hay SendGrid**, ai đăng ký tài khoản; **tên miền** để xác thực.
- **Danh sách dịp thật** (câu 31) + nội dung từng thư — truyền thống của đạo tràng có
  thể khác danh sách mẫu.
- Bản tin **tuần hay tháng**, ngày giờ gửi.
- ✅ **Đã sửa (26/09/2026, nhánh `sua-cong-tu-huy-ban-tin`)**: form Cộng Tu ghi đè
  `nntm_nhan_ban_tin`. Nặng hơn mô tả cũ: ô tích bản tin đã bị gỡ khỏi form từ 21/08
  (commit `d0e5b31`) nên **mọi** lần cam kết đều ghi `'0'` — ai cam kết cũng bị huỷ nhận.
  Giờ `nntm_congtu_ghi_cam_ket()` chỉ **bật**, không bao giờ tắt; huỷ chỉ qua link trong thư.
  ⚠️ **Dữ liệu cũ không tự khôi phục được**: `'0'` do lỗi này và `'0'` do không tích ô lúc
  đăng ký trông như nhau. Trên production (nếu đã có thành viên cam kết trước bản sửa) cần
  chủ dự án quyết: bật lại cho những người có dòng trong `nntm_kpi_log`, hay để nguyên.
