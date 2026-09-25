# Trang kết quả tìm bằng hình ảnh — Phase 2 (khảo sát câu 15–16)

Phiên 25/09/2026. Nhánh `phase2-trang-tim-anh` (nền là `phase2-chi-muc`).
Bổ sung `10-ban-giao-tim-kiem.md` mục 10 việc 4.

---

## 1. Trước và sau

**Trước:** thả ảnh vào ô tìm → bảng gợi ý 6 kết quả. "Xem tất cả" chỉ tìm chữ theo
**từ khoá đầu tiên** — mất các từ khoá khác và toàn bộ phần "ảnh trông giống".

**Giờ:** "Xem tất cả" mở trang riêng `/?s=<từ khoá>&nntm_anh=<mã phiên>`:
- ảnh vừa tải lên (xem trước) + các từ khoá đọc được kèm %, bấm từ khoá = tìm riêng từ đó;
- **"Bài viết nhắc tới những gì trong ảnh"** — gộp kết quả của MỌI từ khoá, bài khớp
  nhiều từ khoá lên trước, mỗi dòng ghi *khớp “thiền”, “hoa sen”*; phân trang;
- **"Nội dung có ảnh trông giống"** — lưới ảnh theo vector (trang 1), bỏ bài đã có ở trên;
- nút **"Tìm bằng ảnh khác"** ngay trên trang (thành viên đã đăng nhập hiện không có ô
  tìm ở header — xem mục 4).

## 2. Thiết kế

| | Cách làm | Vì sao |
|---|---|---|
| Ảnh qua URL | Không. `/image` trả **mã phiên** 24 ký tự | Ảnh không nhét vào URL được |
| Máy chủ nhớ gì | Transient 30 phút: **từ khoá + vector**. KHÔNG lưu ảnh | Không giữ ảnh người dùng |
| Ảnh xem trước | `sessionStorage` của trình duyệt, bản thu 480px (~4–30 KB), giữ 3 ảnh gần nhất | Máy chủ không cần biết |
| Kết quả | **Tính lại cho người đang mở trang** | Gửi link cho khách → khách chỉ thấy phần công khai |
| Trang | Dùng lại trang tìm kiếm (`search.php` rẽ nhánh sang `template-parts/search/anh.php`) | Header, CSS, thanh tìm, tiêu đề chạy như tìm chữ |
| Phân trang | Tham số riêng `nntm_trang`, KHÔNG dùng `paged` | Xem mục 5 |
| Máy tìm kiếm | `noindex` | Trang theo phiên, hết hạn sau 30 phút |

`/image` giờ **luôn** tính vector (thêm vài chục ms) để trang có phần "ảnh trông
giống" kể cả khi từ khoá đã ra bài. Vector lỗi mà từ khoá vẫn có → đi tiếp không có
phần đó. Trả thêm `token` và `trang` (URL trang kết quả, luôn có kể cả khi rỗng);
các trường cũ giữ nguyên.

Mã phiên hết hạn / sai → trang báo "đã hết hạn, chọn lại ảnh". Mã sai định dạng →
rơi về trang tìm chữ bình thường.

## 3. Tệp

| Tệp | |
|---|---|
| `plugins/nntm-search/includes/image-page.php` | phiên, dữ liệu trang, noindex, nạp JS |
| `plugins/nntm-search/includes/image.php` | `/image`: vector luôn tính, tạo phiên, `see_all` → trang mới |
| `plugins/nntm-search/assets/js/search-bar.js` | lưu ảnh xem trước (`window.nntmAnhXemTruoc`) |
| `plugins/nntm-search/assets/js/image-page.js` | hiện ảnh xem trước, "Tìm bằng ảnh khác" |
| `themes/nntm/template-parts/search/anh.php` | **màn tự dựng** — Figma chưa có |
| `themes/nntm/assets/css/pages/search.css` | mục "Tìm bằng hình ảnh", chỉ dùng token |

## 4. Chờ chủ dự án

- **Thanh tìm cho thành viên** (`10-ban-giao` mục 10 việc 5, vẫn còn): header nhánh
  đã đăng nhập không có ô tìm. Trang kết quả có nút "Tìm bằng ảnh khác" nên thành viên
  vẫn tìm tiếp được từ đây, nhưng không có chỗ bắt đầu.
- Thiết kế Figma cho trang này.

## 5. Bẫy

- **Trang 2 trả 404 khi dùng `paged`.** Truy vấn chính của WordPress tìm chuỗi `s`
  (các từ khoá ghép) thường ra ít bài hơn danh sách đã gộp, và WordPress 404 khi
  `paged` vượt số trang của NÓ. Đã thấy thật → dùng `nntm_trang`.
- Kiểm "bài khu hạn chế có lọt không" bằng grep tên bài phải nhớ WordPress đổi `-`
  thành `&#8211;` trong tiêu đề — grep "Bài 1" với dấu gạch thường luôn ra 0.
  Và chỉ `kim-cuong-hanh-gia` là khu hạn chế; `dai-si-hanh-gia` là công khai.

## 6. Đã kiểm

- Dữ liệu trang (CLI): 3 từ khoá → 14 bài gộp, nhãn "khớp …", bài khớp nhiều từ lên
  trước, trang 1: 10 + ảnh giống, trang 2: 4 và không có ảnh giống; mã sai/hết hạn → ok=false.
- HTTP với khách: trang 1/2/3 đều 200 (trước khi sửa: trang 2 → 404), 0 bài Kim Cương
  Hành Giả (bài chứa "thiền" nhưng thuộc khu hạn chế), `noindex`, chỉ vector → báo
  "không đọc được từ khoá" + lưới ảnh, mã hết hạn → thông báo.
- Chrome headless: máy tính 1366px + điện thoại 390px, ảnh xem trước hiện từ
  sessionStorage, không lỗi JS; hàm lưu ảnh: thu về 480px JPEG, giữ đúng 3 ảnh mới nhất.
- Vector thử nghiệm gắn tạm cho 3 ảnh thật để có phần "ảnh trông giống" — đã xoá sau khi thử.

⚠️ **Chưa kiểm:** gửi ảnh thật qua `/image` (local tắt tìm bằng ảnh + chưa có dịch vụ
CLIP; REST nhận file không giả lập được bằng dòng lệnh vì `is_uploaded_file()`), và
nút "Tìm bằng ảnh khác" chạy thật. Kiểm lại trên staging.
