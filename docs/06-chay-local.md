# Chạy dự án ở máy local

## Khởi động

**1. MySQL** — cần chạy trước:
```bash
"C:/xampp8_2/mysql/bin/mysqld.exe" --defaults-file="C:/xampp8_2/mysql/bin/my.ini" --standalone
```
Hoặc bật MySQL trong bảng điều khiển XAMPP.

**2. Web server** — chọn một trong hai:

*Cách A — PHP server riêng (đang dùng, không đụng cấu hình XAMPP):*
```bash
"C:/xampp8_2/php/php.exe" -S localhost:8080 -t "C:/xampp8_2/htdocs/NNTM"
```
→ http://localhost:8080

*Cách B — Apache của XAMPP:* hiện `DocumentRoot` trong `httpd.conf` đang trỏ tới `C:/xampp8_2/htdocs/MA` (thư mục không tồn tại) nên `http://localhost/NNTM/` trả về 404. Đây là cấu hình sẵn có của máy, **chưa sửa** vì có thể thuộc dự án khác. Muốn dùng Apache thì đổi `DocumentRoot` về `C:/xampp8_2/htdocs` hoặc tạo virtual host riêng cho NNTM.

`wp-config.php` tự suy ra địa chỉ từ request nên chạy được cả hai cách, không phải sửa database.

## Tài khoản quản trị (chỉ dùng ở local)

| | |
|---|---|
| Địa chỉ | http://localhost:8080/wp-admin |
| Tài khoản | `nntm_admin` |
| Mật khẩu | `NntmDev!2026` |
| Email | uy@local.test |

⚠️ Đây là tài khoản máy local, **không dùng lại trên staging hay production**.

## Cơ sở dữ liệu

| | |
|---|---|
| Tên | `nntm_dev` |
| Người dùng | `root`, không mật khẩu |
| Tiền tố bảng | `wp_` |
| Bảng riêng | `wp_nntm_reading_progress`, `wp_nntm_notes`, `wp_nntm_favorites`, `wp_nntm_retreat_signup`, `wp_nntm_kpi_log` |

## Kéo lại design token khi Figma có thay đổi

```bash
node tools/figma-sync.mjs
```

## Đã kiểm chứng ngày 06/08/2026

| Hạng mục | Kết quả |
|---|---|
| WordPress 7.0.2 tiếng Việt | cài xong |
| Theme `nntm` | kích hoạt, trang chủ HTTP 200, không lỗi PHP |
| Plugin `nntm-core` | kích hoạt không lỗi |
| 7 Custom Post Type | đủ, `show_in_rest` bật (sửa được bằng trình soạn thảo block) |
| 3 Taxonomy | đủ |
| 6 phân mục | tạo sẵn đúng slug không dấu |
| 2 vai trò thành viên | đủ capability |
| 5 bảng dữ liệu riêng | tạo đủ |
| Bảng màu theme.json | 15 màu, đã khóa không cho khách tự chọn màu ngoài |
| Font | 5 họ nạp đúng, đủ dấu tiếng Việt |
| Lỗi console trình duyệt | không có |
| `debug.log` | trống |

## Chưa làm

- PHP local là **8.1**, báo giá cam kết **8.3** → nâng XAMPP trước khi lên staging
- Chưa có Gutenberg block nào (task 4)
- Chưa có trang phân mục nào (task 5)
