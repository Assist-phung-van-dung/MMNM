# Chạy dự án ở máy local — KHÔNG dùng Docker

Dành cho máy chưa cài Docker, hoặc muốn chạy PHP/MySQL/Python trực tiếp trên máy
(kiểu XAMPP). Bổ sung cho `06-chay-local.md` (đã cũ, PHP 8.1) và `10-ban-giao-tim-kiem.md`
(giả định sẵn máy đã có XAMPP ở `C:\xampp`, user `Admin`) — file này viết lại từ
đầu, không giả định máy đã có gì sẵn ngoài hệ điều hành.

Không đụng tới bất kỳ container Docker nào. Chạy song song thoải mái với môi
trường Docker đã có (khác cổng là được).

---

## 0. Cần cài trước

| Phần mềm | Bản tối thiểu | Vì sao |
|---|---|---|
| PHP | **8.3** | Mức cam kết trong báo giá — máy XAMPP cũ (`06-chay-local.md`) mới có 8.1 |
| MariaDB | **10.11+** (hoặc MySQL 8.0+) | Cần hỗ trợ `innodb_ft_server_stopword_table` |
| Python | 3.10+ | Chạy dịch vụ đọc ảnh/PDF (`tools/embed-service`) |

Không bắt buộc dùng XAMPP — tải PHP zip (windows.php.net) + MariaDB zip (mariadb.org)
giải nén ra, chạy trực tiếp, không cần cài đặt kiểu installer.

---

## 1. Database

### 1.1 Chạy MariaDB

```bash
"C:/duong-dan-den-mariadb/bin/mysqld.exe" --defaults-file="C:/duong-dan-den-mariadb/my.ini" --standalone
```

(Nếu dùng XAMPP: `"C:/xampp/mysql/bin/mysqld.exe" --defaults-file="C:/xampp/mysql/bin/my.ini" --standalone`)

### 1.2 Tạo database và nạp dữ liệu

```bash
mysql -u root -e "CREATE DATABASE nntm_dev DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root --init-command="SET SESSION sql_mode=''" nntm_dev < db/nntm_dev.sql
```

**Bản dump nằm trong repo: `db/nntm_dev.sql`** (21 bảng, ~2,3 MB — kể cả ba bảng
riêng của dự án `wp_nntm_pdf_pages`, `wp_nntm_image_vectors`, và bảng stopword
rỗng `nntm_ft_stopword` mà FULLTEXT ở mục 1.3 cần).

`--init-command="SET SESSION sql_mode=''"` là bắt buộc, đừng bỏ: MariaDB mặc
định bật `NO_ZERO_DATE`, mà WordPress để `comment_date` mặc định là
`0000-00-00 00:00:00` — không tắt sql_mode thì nhập tới bảng `wp_comments` là
đứng với lỗi 1067 *Invalid default value*.

Không muốn nhập dump thì bỏ qua bước này và dùng `tools/bootstrap-demo.php` ở
mục 5 để tự sinh dữ liệu demo từ đầu.

### 1.3 ⚠️ BẮT BUỘC cho tìm kiếm PDF — cấu hình FULLTEXT tiếng Việt

Không làm bước này thì tìm cả câu trong PDF trả về **rỗng** dù từng từ tìm riêng
vẫn ra (lý do kỹ thuật đầy đủ ở `10-ban-giao-tim-kiem.md` mục 4 — tóm tắt:
`innodb_ft_min_token_size` mặc định 3 làm rớt các âm tiết tiếng Việt bỏ dấu chỉ
còn 2 ký tự như `tu`, `de`, `la`; đồng thời stopword tiếng Anh mặc định trùng
âm tiết Việt phổ biến).

**Trong `my.ini`, mục `[mysqld]`, thêm hai dòng rồi khởi động lại MariaDB:**

```ini
innodb_ft_min_token_size=2
innodb_ft_server_stopword_table=nntm_dev/nntm_ft_stopword
```

**Tạo bảng stopword RỖNG (bắt buộc, kể cả để trống) trước khi restart:**

```sql
CREATE TABLE nntm_dev.nntm_ft_stopword (value VARCHAR(30)) ENGINE=INNODB;
```

Khởi động lại `mysqld.exe` (bước 1.1) để hai dòng trên có hiệu lực — đây là tham
số khởi động, không đổi được khi server đang chạy (`SET GLOBAL` không tác dụng).

**Xác nhận đã áp dụng đúng:**

```sql
SHOW VARIABLES LIKE 'innodb_ft_min_token_size';        -- phải ra 2
SHOW VARIABLES LIKE 'innodb_ft_server_stopword_table';  -- phải ra nntm_dev/nntm_ft_stopword
```

Nếu bảng `wp_nntm_pdf_pages` đã tồn tại **trước khi** chỉnh hai dòng trên (import
từ `nntm_dev.sql` có sẵn dữ liệu), phải dựng lại chỉ mục FULLTEXT để nó đọc theo
cấu hình mới:

```sql
ALTER TABLE nntm_dev.wp_nntm_pdf_pages DROP INDEX ft_folded;
ALTER TABLE nntm_dev.wp_nntm_pdf_pages ADD FULLTEXT KEY ft_folded (folded);
```

Nếu bảng được tạo **sau khi** đã chỉnh (ví dụ qua bước 3 kích hoạt plugin, hoặc
qua `bootstrap-demo.php`) thì không cần bước `ALTER TABLE` này.

---

## 2. WordPress

### 2.1 Chép mã nguồn

```bash
xcopy /E /I nntm-toan-bo-site C:\đường-dẫn-web-root\NNTM
```

Hoặc trỏ thẳng document root của web server vào thư mục `nntm-toan-bo-site/`.

### 2.2 `wp-config.php`

Dùng nguyên file `wp-config.php` đã có trong `nntm-toan-bo-site/` (không dùng
`docker/wp-config.docker.php` — file đó chỉ dành cho container). Đổi 4 dòng kết
nối database cho khớp máy:

```php
define( 'DB_NAME', 'nntm_dev' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );      // rong neu MariaDB local khong dat mat khau
define( 'DB_HOST', 'localhost' );
```

**KHÔNG** cần thêm hằng số `NNTM_SEARCH_SERVICE_URL` — đó là mẹo riêng cho môi
trường Docker (PHP chạy trong container, `127.0.0.1` trỏ nhầm vào chính nó).
Chạy PHP trực tiếp trên máy thì mặc định `http://127.0.0.1:8765` của plugin
(`wp-content/plugins/nntm-search/includes/embed.php`) đã đúng, không phải sửa gì.

### 2.3 Chạy web server

```bash
php.exe -S localhost:8080 -t C:\đường-dẫn-web-root\NNTM
```

→ http://localhost:8080. `wp-config.php` gốc tự suy ra địa chỉ từ request, mở
bằng `localhost` hay `127.0.0.1` đều được, không cần sửa database.

### 2.4 Kích hoạt theme + 2 plugin

Qua **wp-admin → Giao diện / Plugin**, hoặc bằng WP-CLI nếu có:

```bash
wp theme activate nntm
wp plugin activate nntm-core nntm-search
```

Kích hoạt `nntm-search` sẽ tự tạo hai bảng `wp_nntm_pdf_pages` và
`wp_nntm_image_vectors` (hook `register_activation_hook`, xem
`wp-content/plugins/nntm-search/includes/schema.php`) — không cần chạy SQL tay.

---

## 3. Dịch vụ Python (đọc ảnh + trích chữ PDF)

### 3.1 Tạo môi trường riêng (khuyên dùng, tránh đụng Python hệ thống)

```bash
python -m venv nntm-embed-venv
nntm-embed-venv\Scripts\activate
```

### 3.2 Cài gói

`tools/embed-service/` **không có `requirements.txt`** (ghi chú trong
`10-ban-giao-tim-kiem.md` mục 7: *"Môi trường Python nằm ngoài repo... máy mới
phải dựng lại — repo chỉ có mã nguồn dịch vụ"*) — cài đúng 6 gói sau (`10-ban-giao`
chỉ liệt kê 5, thiếu `python-multipart` — không có gói này thì `uvicorn` báo lỗi
ngay lúc khởi động, chưa kịp nghe cổng nào cả):

```bash
pip install fastembed fastapi "uvicorn[standard]" pypdf reportlab python-multipart
```

Model CLIP ViT-B/32 (bản ONNX, ~350MB) tự tải về lần chạy đầu tiên, cần mạng.
Từ lần sau chạy offline được vì đã nằm trong cache.

### 3.3 Chạy dịch vụ

```bash
cd tools\embed-service
python -m uvicorn main:app --host 127.0.0.1 --port 8765
```

⚠️ **Nếu PHP/WordPress cũng chạy trực tiếp trên máy**, chỉ bind `127.0.0.1` —
đừng đổi thành `0.0.0.0`. Dịch vụ không có xác thực, mở ra mạng là ai cũng gọi
được, tốn CPU của khách và không có gì chặn.

> **Bẫy khi WordPress vẫn chạy trong Docker:** container không truy cập được
> dịch vụ chỉ bind vào loopback `127.0.0.1` của Windows. Dấu hiệu là endpoint
> `/wp-json/nntm-search/v1/image` trả HTTP 503 `nntm_service_failed`, còn
> `wp-content/debug.log` ghi `cURL error 28: Connection timed out`, trong khi gọi
> `http://127.0.0.1:8765/khoe` trên Windows vẫn thành công. Trường hợp chạy lai
> này phải khởi động dịch vụ bằng:
>
> ```bash
> python -m uvicorn main:app --host 0.0.0.0 --port 8765
> ```
>
> và đặt `NNTM_SEARCH_SERVICE_URL=http://host.docker.internal:8765` cho
> WordPress trong container. Chỉ cho phép cổng 8765 từ máy local/mạng Docker
> bằng Windows Firewall; không NAT hoặc mở cổng này ra Internet.

Kiểm tra dịch vụ sống:

```bash
curl http://127.0.0.1:8765/khoe
```

Phải trả về `{"ok":true}`. Chưa chạy dịch vụ thì tìm bằng ảnh và lập chỉ mục PDF
**im lặng không hoạt động** — WordPress không báo lỗi, chỉ đơn giản là không ra
kết quả nào từ ảnh/PDF.

RAM khi chạy: ~477MB (chỉ model ảnh) đến ~740MB (thêm model chữ).

---

## 4. Nạp dữ liệu demo để test

Nếu bước 1.2 không có sẵn `nntm_dev.sql`, hoặc muốn có dữ liệu test đã đo sẵn từ
khoá (để biết "đúng" là ra kết quả gì thay vì đoán):

```bash
php tools\bootstrap-demo.php
```

Script tự sinh: 10 trang, 11 bài viết, 3 ấn phẩm kèm PDF, 5 ảnh, 1 tài khoản
thành viên — lập chỉ mục PDF và ảnh luôn trong lúc chạy (nếu dịch vụ Python ở
mục 3 đã bật). Chạy lại nhiều lần được, chỉ **thêm** phần còn thiếu, không tạo
trùng. Thêm tham số `xoa` để dọn sạch dữ liệu demo:

```bash
php tools\bootstrap-demo.php xoa
```

Ảnh mẫu và PDF mẫu nằm sẵn trong repo, không phụ thuộc máy nào:

```
tools/test-assets/anh/   5 ảnh JPG, từ khoá đã đo sẵn
tools/test-assets/pdf/   3 file PDF tiếng Việt có chữ thật
```

---

## 5. Kiểm tra đã chạy đúng chưa

| Việc | Cách kiểm |
|---|---|
| Site sống | mở `http://localhost:8080/`, không lỗi PHP, `debug.log` trống |
| Dịch vụ Python sống | `curl http://127.0.0.1:8765/khoe` → `{"ok":true}` |
| FULLTEXT đúng cấu hình | `SHOW VARIABLES LIKE 'innodb_ft%'` — xem mục 1.3 |
| Tìm chữ thường | gõ vào ô tìm kiếm một từ có trong bài demo |
| Tìm trong PDF | tìm một câu dài (không phải 1 từ) — nếu ra rỗng, quay lại mục 1.3 |
| Tìm bằng ảnh | thả `tools/test-assets/anh/02-tuong-phat.jpg` vào ô tìm ảnh → phải ra từ khoá `tượng Phật` |
| Cổng quyền | test theo kịch bản "hai cửa sổ" ở `10-ban-giao-tim-kiem.md` mục 9 (khách 1 kết quả, thành viên 6 kết quả cho cùng một ảnh) |

77 test case đầy đủ: `08-test-case-tim-kiem.md`. 10 kịch bản làm tay ~25 phút:
`09-kich-ban-test-tay.md`.

---

## 6. Sự khác biệt với môi trường Docker

| | Docker (`docker/`) | Không Docker (file này) |
|---|---|---|
| PHP chạy ở đâu | trong container `nntm-web` | trực tiếp trên máy |
| `NNTM_SEARCH_SERVICE_URL` | phải đặt `http://host.docker.internal:8765` (`docker/wp-config.docker.php`); nếu Python chạy trên host thì phải bind địa chỉ container truy cập được, xem bẫy ở mục 3.3 | không cần đặt, mặc định `127.0.0.1:8765` đã đúng |
| Cấu hình FULLTEXT | sửa `command:` của service `db` trong `docker/docker-compose.yml`, chạy `docker compose up -d db` để áp dụng | sửa `my.ini`, khởi động lại `mysqld.exe` |
| `wp-config.php` dùng | `docker/wp-config.docker.php` (bind mount, không đụng file gốc) | file `wp-config.php` gốc trong `nntm-toan-bo-site/` |
