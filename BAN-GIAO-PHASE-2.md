# Bàn giao Phase 2 — NNTM (25/09/2026)

> Dán file này (hoặc đường dẫn tới nó) vào chat mới mở tại **`C:\xampp8_2\htdocs\NNTM`**.
> Tài liệu chi tiết từng mục: `docs/13` → `docs/18`. File này KHÔNG commit (chỉ để chuyển chat).

## 1. Bối cảnh

- Dự án: website Phật giáo "Nẵng Nhân Tịch Mặc", WordPress 7.0.3, local `http://nntm.com`, DB `nntm_dev`, PHP CLI `C:/xampp8_2/php/php.exe`.
- Repo: `https://github.com/Assist-phung-van-dung/MMNM`, nhánh nền **`main-go-chu-thich`**.
- Quy tắc: dữ liệu/nghiệp vụ ở plugin `nntm-core` / `nntm-search`, hiển thị ở theme `nntm`; chỉ dùng token màu trong `tokens.css`; không composer/npm; tên hàm tiếng Việt không dấu.
- Hạng mục Phase 2 (báo giá): tìm bằng ảnh (câu 15–16), tìm trong PDF + OCR (11–12), Cộng Tu KPI/BXH/dashboard (26–28), đăng ký Khóa Tu (29), bản tin & email dịp lễ (30–31), từ khoá động (33–34).

## 2. Đã làm — 6 nhánh ĐÃ PUSH, CHƯA TẠO PR (anh tự tạo, base `main-go-chu-thich`)

| # | Nhánh | Commit | Nền | Nội dung | Tài liệu |
|---|---|---|---|---|---|
| 1 | `phase2-tu-khoa-dong` | `f2a7037` | main-go-chu-thich | CPT từ khoá + hình, ô bật theo trang, hiệu ứng rê chuột/chạm | docs/13 |
| 2 | `phase2-ban-tin-email` | `4f09783` | main-go-chu-thich | Bản tin tuần/tháng, dịp lễ âm/dương lịch, xác nhận Khóa Tu, hàng đợi gửi, huỷ nhận HMAC + one-click, nhật ký | docs/14 |
| 3 | `phase2-ocr-pdf` | `82a135d` | main-go-chu-thich | OCR Tesseract `vie` cho trang PDF scan (dịch vụ Python + hàng đợi WP-Cron), trang OCR khớp bỏ dấu | docs/15 |
| 4 | `phase2-chi-muc` | `bc1b4c1` | **phase2-ocr-pdf** | Lập chỉ mục nền ảnh/PDF, **gắn lại bài chứa** khi lưu bài, Công cụ → Chỉ mục tìm kiếm, CLI | docs/16 |
| 5 | `phase2-trang-tim-anh` | `7948bf4` | **phase2-chi-muc** | Trang kết quả tìm bằng ảnh đầy đủ (mã phiên, không lưu ảnh), phân trang `nntm_trang` | docs/17 |
| 6 | `phase2-dashboard-cong-tu` | `703951c` + `d924317` | main-go-chu-thich | Trang `/cong-tu-cua-toi/`: hôm nay, tuần, nhịp, vòng cam kết, biểu đồ 14 ngày/tuần, nhật ký; đã thiết kế lại | docs/18 |

- Thứ tự merge bắt buộc: **3 → 4 → 5**. 1, 2, 6 độc lập.
- Đã thử merge cả 6 với `origin/main-go-chu-thich` mới nhất (`3472b41`): **không xung đột**.
- Đăng ký Khóa Tu (câu 29) **đã có từ trước** (form + admin + CSV); Phase 2 chỉ thêm thư xác nhận (nhánh 2).

## 3. Còn thiếu / chưa kiểm

### Chưa kiểm bằng tay (cần đăng nhập trình duyệt)
- Màn wp-admin của: Từ khoá động (meta box, ô tích thanh bên), Bản tin & Email (cài đặt, dịp lễ, nhật ký), Công cụ → Chỉ mục tìm kiếm.
- Bấm "Khai báo hôm nay" trên `/cong-tu-cua-toi/` → modal → trang tải lại.
- Nút "Tìm bằng ảnh khác" trên trang kết quả tìm ảnh.

### Chưa chạy được ở máy local
- **Dịch vụ Python (`tools/embed-service`) chưa từng được dựng ở máy này** (thiếu fastembed/onnxruntime/model CLIP ~350MB) → 0/53 ảnh, 0/7 PDF có chỉ mục; tìm bằng ảnh + tìm trong PDF ở local chưa có dữ liệu. Endpoint `/pdf/ocr`, `/ocr/khoe` trong `main.py` mới biên dịch, chưa chạy qua FastAPI (phần `ocr.py` đã chạy thật: 1,2 s/trang, 96–99 %).
- `.env` của `nntm-search` local đang `NNTM_SEARCH_IMAGE_ENABLED=false`, `NNTM_SEARCH_PDF_ENABLED=false`.
- Chưa gửi thư thật lần nào (chưa có SMTP → chế độ ghi log).

### Chưa làm
- Thiết kế Figma cho mọi màn mới (tất cả là "màn tự dựng" bằng token).
- Ô nhập meta chương trình Cộng Tu trong wp-admin (ngày bắt đầu/kết thúc, đơn vị, mục tiêu — hiện chỉ đặt bằng seed).
- Thanh tìm kiếm cho thành viên đã đăng nhập (header nhánh đăng nhập không có ô tìm — chờ chủ dự án).

## 4. Chờ chủ dự án / khách quyết

1. **Múi giờ site đang UTC** → "hôm nay" đổi ngày lúc 7:00 sáng giờ VN (ảnh hưởng khai báo Cộng Tu có sẵn + dashboard). Đề xuất đổi `Asia/Ho_Chi_Minh` trước ra mắt; kiểm cả production.
2. **SES hay SendGrid** + tên miền để xác thực (SPF/DKIM/DMARC); email người gửi.
3. **Danh sách dịp lễ thật** + nội dung thư (8 dịp mẫu đang ở Nháp).
4. **Danh sách từ khoá động + hình** (4 từ khoá mẫu, meta `_nntm_tkd_mau=1`, xoá khi có thật); trang nào bật.
5. **Trang OCR khớp bỏ dấu** (tìm "rừng" có thể ra trang scan chứa "rụng") — đã làm, cần xác nhận; tắt bằng filter `nntm_search_ocr_khop_bo_dau`.
6. **Lỗi có sẵn chưa sửa:** form Cộng Tu (`themes/nntm/inc/cong-tu.php` ~dòng 398) ghi đè `nntm_nhan_ban_tin` → thành viên đã đăng ký nhận bản tin, vào Cộng Tu không tích ô là bị huỷ nhận âm thầm.
7. Xác nhận thương mại: tìm bằng ảnh và BXH Cộng Tu từng làm sớm hơn báo giá (docs/07, docs/10).

## 5. Triển khai lên VPS (khi merge xong)

```bash
apt install tesseract-ocr tesseract-ocr-vie
# venv của tools/embed-service:
pip install fastembed fastapi "uvicorn[standard]" pypdf python-multipart pypdfium2 pytesseract
```
- wp-config: `define('DISABLE_WP_CRON', true);` + cron hệ thống mỗi phút gọi `wp-cron.php`.
- wp-config: `NNTM_SMTP_HOST/PORT/USER/PASS/SECURE` (docs/14).
- Chạy một lần: `tools/lap-chi-muc.php --thieu --chay`, `tools/ocr-pdf.php --chay`, `tools/seed-cong-tu.php` (tạo trang `cong-tu-cua-toi`), tuỳ chọn `tools/seed-dip-le.php`, `tools/seed-tu-khoa-dong.php`.
- Schema `nntm-core` lên **1.2.0** (thêm bảng `nntm_mail_campaign`, `nntm_mail_queue`) — tự nâng cấp khi tải trang.

## 6. Trạng thái máy local

- Checkout hiện tại: `phase2-dashboard-cong-tu`.
- Chưa commit (không nên commit): `wp-content/uploads/2026/09/` (3 ảnh mẫu do seed chép), file này.
- Đã cài: **Tesseract 5.4.0** (`C:\Program Files\Tesseract-OCR`, gỡ: `winget uninstall UB-Mannheim.TesseractOCR`). Gói `vie.traineddata` + venv thử OCR nằm trong thư mục tạm của phiên chat cũ (sẽ mất) — tải lại từ `github.com/tesseract-ocr/tessdata_best`.
- Dữ liệu mẫu trong DB: 4 từ khoá động (bật trên bài `hoa-sen-no-giua-bun-nho`), 8 dịp lễ Nháp, trang `cong-tu-cua-toi` (ID 580). Bảng mail rỗng.
- MySQL từng tự tắt giữa phiên (không có log shutdown) — nếu site báo "Lỗi kết nối CSDL" thì bật lại trong XAMPP Control Panel.

## 7. Bẫy mới gặp trong Phase 2 (bổ sung docs/07 mục 9)

- `base.css` có `ul[class] { padding: 0 }` — nặng hơn một lớp đơn, dùng hai lớp để đè.
- Trang tìm kiếm: phân trang bằng `paged` bị 404 khi danh sách tự gộp dài hơn truy vấn chính của WP → dùng tham số riêng.
- Georgia thiếu glyph tiếng Việt dựng sẵn → dấu tách rời trong thư; dùng Times New Roman / Noto Serif.
- pytesseract trên Windows giữ nguyên dấu ngoặc kép trong `--tessdata-dir` → dùng biến `TESSDATA_PREFIX`.
- `get_post_meta()` trả `''` khi chưa có meta, `(array) ''` = `['']`.
- Ẩn phần tử trong lưới bằng `display:none` làm sụp hàng lưới → dùng `visibility:hidden`.
- Trình duyệt tích hợp của app không nạp được CSS/JS của `nntm.com` → kiểm giao diện bằng Chrome headless qua CDP.
- Cổng quyền khu hạn chế MỞ khi chạy CLI (`PHP_SAPI === 'cli'`) — mọi việc chạy từ cron CLI (bản tin, chỉ mục) phải lọc quyền tường minh.
