# Chỉ mục tìm kiếm: hàng đợi nền + gắn lại bài chứa — Phase 2

Phiên 25/09/2026. Nhánh `phase2-chi-muc` (nền là `phase2-ocr-pdf`).
Bổ sung `10-ban-giao-tim-kiem.md` mục 10 việc 3 ("Action Scheduler — hiện lập chỉ
mục chạy đồng bộ") và điều kiện của hạng mục Phase 2 "lập chỉ mục toàn bộ kho nội
dung đã nhập ở Phase 1".

---

## 1. Ba việc

**a) Tải lên không còn lập chỉ mục ngay.** Ảnh/PDF mới chỉ được xếp hàng (meta
`_nntm_cm_cho`); WP-Cron mỗi phút xử lý trong ~40 giây. Trước đây mỗi lần tải lên
chờ dịch vụ Python — PDF tới 120 giây, tải hàng nghìn ảnh một lượt là treo.
Đổi lại: file mới **tìm được sau khoảng 1 phút**, không phải ngay.

**b) Gắn lại bài chứa — lỗi âm thầm có thật.** Ảnh gần như luôn được tải lên
TRƯỚC rồi mới chèn vào bài → lúc lập chỉ mục `post_id = 0`. Tìm bằng ảnh **bỏ qua
mọi vector `post_id = 0`** (`nntm_search_group_by_post`) → ảnh đó **không bao giờ**
dẫn tới bài của nó, dù chỉ mục đầy đủ. PDF gắn vào Ấn phẩm sau khi tải lên thì kết
quả hiện tên file thay vì tên ấn phẩm.
Giờ: mỗi lần lưu bài (`wp_after_insert_post` — chạy SAU khi REST gán chuyên mục),
ảnh/PDF bài đó dùng được cập nhật `post_id` + quyền + ngôn ngữ. Chỉ là `UPDATE`,
không gọi dịch vụ. Bài có > 10 file thì đẩy vào hàng đợi, không bắt người soạn chờ.
Bài bị xoá / vào thùng rác → file của nó tìm chủ mới hoặc về mồ côi.
**Quyền theo bài:** đưa bài vào khu Hành Giả → ảnh của nó thành `member` ngay.

**c) Công cụ → Chỉ mục tìm kiếm** (+ `tools/lap-chi-muc.php`): tình trạng dịch vụ
Python và OCR, số ảnh/PDF có chỉ mục, số file chưa gắn bài, hàng đợi, file lỗi
kèm lý do. Nút: *Lập chỉ mục phần còn thiếu* · *Gắn lại bài chứa & quyền xem* ·
*Xử lý hàng đợi ngay* (khi WP-Cron không chạy) · *Lập chỉ mục lại toàn bộ* ·
*Dừng & xoá hàng đợi*.

## 2. Vì sao WP-Cron chứ không Action Scheduler

Action Scheduler phải nhúng thư viện ngoài (dự án không chạy composer/npm). Hàng
đợi ở đây chỉ cần: một meta trên file, khoá chống chạy chồng, tạm dừng khi dịch vụ
hỏng, thử lại có giới hạn — cùng mẫu với hàng đợi OCR (`ocr.php`) và thư
(`nntm-core/.../class-gui-thu.php`). Đủ cho vài chục nghìn file.

## 3. Quy tắc xử lý lỗi

| Tình huống | Làm gì |
|---|---|
| Dịch vụ không phản hồi / trả 5xx | **Tạm dừng cả hàng 5 phút**, file giữ chỗ, không tính lượt thử |
| Dịch vụ trả 4xx (ảnh/PDF hỏng) | Thử tối đa **3 lần** rồi đánh lỗi, ghi lý do, đi tiếp |
| File mất trên đĩa, PDF không có trang | Lỗi ngay |
| Tính năng bị tắt, file không thuộc diện (SVG, AVIF) | Bỏ khỏi hàng, không tính lỗi |
| Cùng file quay lại trong một lượt | Dừng lượt đó, không quay vòng |

Để phân biệt được hai dòng đầu, `nntm_search_index_pdf()` giờ đi qua
`nntm_search_post_file()` (có mã HTTP trong lỗi, có request id + log thời gian như
mọi lời gọi khác) thay vì tự dựng multipart.

## 4. Chạy cho kho có sẵn

```bash
"C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php                  # chỉ in tình trạng
"C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --thieu --chay   # nên chạy một lần sau khi dựng dịch vụ
"C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --quyen --chay   # sau mỗi đợt nhập nội dung lớn
"C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --chay               # OCR trang scan (hàng đợi riêng)
```

`--tat-ca` làm lại toàn bộ — cần khi đổi model CLIP (vector cũ không so được với
vector mới; "thiếu" đã tính theo model hiện tại nên đổi model thì ảnh cũ tự thành thiếu).

⚠️ **Máy dev hiện tại: 0/53 ảnh, 0/7 PDF có chỉ mục** — dịch vụ Python chưa từng
được dựng ở máy này, tìm bằng ảnh và tìm trong PDF ở local chưa bao giờ có dữ liệu.

## 5. Đã kiểm

27 phép thử với dịch vụ giả lập (không chạm ảnh thật của site):
tải lên chỉ xếp hàng (không gọi dịch vụ trong request) · chạy lô · ảnh tải lên
trước có `post_id = 0` · lưu bài → ảnh gắn đúng bài · lưu ấn phẩm có `_nntm_pdf_file`
→ trang PDF gắn đúng ấn phẩm · xoá bài → ảnh chuyển sang bài khác còn dùng · đưa bài
vào/ra khu Hành Giả → ảnh `member`/`public` · thùng rác → mồ côi · 503 → tạm dừng,
không gọi dồn · 400 → 3 lượt rồi lỗi · xếp lại xoá lỗi cũ · "quyen" không ghi đè
"day_du" · dừng hàng (cache meta sạch) · SVG bị bỏ · màn quản trị render đúng ·
xoá file → sạch chỉ mục.

⚠️ Chưa bấm thử màn quản trị trong trình duyệt (cần đăng nhập) — mới render bằng PHP.
⚠️ Chưa chạy với dịch vụ Python thật (CLIP) — máy này chưa có.

## 6. Còn lại

- Ảnh bị **gỡ khỏi bài** mà bài không lưu lại (sửa thẳng DB) thì `post_id` cũ còn đó
  tới lần "Gắn lại bài chứa" kế tiếp. Lớp kiểm quyền thứ hai
  (`nntm_search_can_view`) vẫn chặn bài không xem được, nên không rò.
- Trang kết quả riêng cho tìm bằng ảnh (Phase 2 mục 5) — chưa làm.
