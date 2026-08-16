# Kịch bản test tay — Tìm kiếm

Làm theo đúng thứ tự dưới đây, mỗi bước có **kết quả đúng phải thấy** và **dấu
hiệu sai**. Toàn bộ số liệu trong tài liệu này là kết quả chạy thật ngày
16/08/2026, không phải phỏng đoán.

Cần khoảng **25 phút**.

---

## Bước 0 — Chuẩn bị

### 0.1. Bật ba thứ, theo đúng thứ tự

```bash
C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
```

```bash
C:\xampp\php\php.exe -S localhost:8080 -t C:\Users\Admin\Downloads\MMNM <duong-dan>\router.php
```

```bash
C:\Users\Admin\nntm-embed\venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8765
```

Lệnh thứ ba chạy trong thư mục `tools/embed-service`.

### 0.2. Kiểm dịch vụ ảnh trước khi test

Mở **http://127.0.0.1:8765/khoe** — phải thấy `{"ok":true,...}`.

> ⚠️ Nếu bước này không xong thì **kịch bản 6, 7, 8 sẽ trượt hết**, mà triệu
> chứng nhìn y hệt lỗi code. Kiểm trước cho đỡ mất công.

### 0.3. Dựng dữ liệu test

```bash
C:\xampp\php\php.exe tools/test-data-tim-kiem.php
```

Script tạo 5 bài `[TEST]`, một tài khoản thành viên, và chép 7 ảnh mẫu vào
**`C:\Users\Admin\Downloads\nntm-test-anh`**. Chạy lại nhiều lần được.

### 0.4. Hai tài khoản, hai cửa sổ

| | Tài khoản | Dùng cửa sổ |
|---|---|---|
| Khách | *(không đăng nhập)* | **cửa sổ ẩn danh** |
| Thành viên | `nntm_test` / `TestNntm!2026` | cửa sổ thường |

> Rất nhiều lỗi phân quyền chỉ lộ ra khi so hai cửa sổ cạnh nhau. Đừng test
> bằng cách đăng xuất rồi đăng nhập lại trên cùng một cửa sổ — dễ nhầm vì cache.

### 0.5. Ảnh mẫu và từ khoá máy sẽ đọc ra

Thư mục `C:\Users\Admin\Downloads\nntm-test-anh`:

| File | Máy phải đọc ra | Dùng cho |
|---|---|---|
| `01-rung-thong.jpg` | rừng thông 40% · rừng 26% · sương mù 25% | kịch bản 6 |
| `02-tuong-phat.jpg` | **tượng Phật 88%** | kịch bản 6, 7 |
| `03-ngoi-chua.webp` | ngôi chùa 34% · chư tăng 18% | kịch bản 6 |
| `04-kinh-sach.png` | sách 59% · kinh sách 33% | kịch bản 6 |
| `05-nui.jpg` | núi 34% · ngoài trời 28% | dự phòng |
| `06-anh-qua-lon.jpg` | *(5,3 MB — cố tình quá cỡ)* | kịch bản 9 |
| `07-file-gia-mao.jpg` | *(file HTML đổi đuôi)* | kịch bản 9 |

Sai lệch vài phần trăm là bình thường. Sai hẳn từ khoá mới là lỗi.

---

## Kịch bản 1 — Gõ không dấu ra chữ có dấu

1. Mở http://localhost:8080 (**cửa sổ ẩn danh**).
2. Bấm vào ô tìm ở góc phải header, gõ `nguyen thuy`.

**Đúng:** sau khoảng 0,3 giây hiện bảng gợi ý, các bài "Nguyên Thuỷ", chữ khớp
được **tô nền kem**. Dòng cuối "Xem tất cả kết quả (6)".

**Sai:** không ra gì · ra nhưng không tô sáng · tô sai chỗ.

3. Xoá đi, gõ lại `Nguyên Thuỷ` (có dấu) rồi `NGUYEN THUY` (in hoa).

**Đúng:** cả ba lần ra **cùng một tập kết quả**.

4. Gõ `duong`.

**Đúng:** ra nội dung chứa chữ "đường". Đây là chỗ hay hỏng nhất của tiếng
Việt — chữ `đ` không tự bỏ dấu như các nguyên âm.

---

## Kịch bản 2 — Các trạng thái của bảng gợi ý

1. Gõ `a` (một ký tự).
   **Đúng:** không hiện gì. Ngưỡng là 2 ký tự.
2. Gõ tiếp thành `an`, nhìn thật nhanh.
   **Đúng:** thoáng thấy vòng xoay + "Đang tìm…".
3. Gõ `xyzkhongcogi`.
   **Đúng:** "Không tìm thấy nội dung nào."
4. Bấm `Esc`.
   **Đúng:** bảng đóng lại.
5. Bấm ra vùng trống của trang.
   **Đúng:** bảng đóng lại.

---

## Kịch bản 3 — Phím tắt và bàn phím

1. Bấm chuột ra vùng trống (để con trỏ không nằm trong ô nhập nào), bấm phím `/`.
   **Đúng:** ô tìm được chọn, con trỏ nhấp nháy trong đó.
2. Bấm `Ctrl` + `K`.
   **Đúng:** như trên, và nội dung cũ được bôi đen sẵn để gõ đè.
3. Gõ `thien`, đợi kết quả, bấm `↓` `↓` `↑`.
   **Đúng:** mục đang chọn đổi nền theo từng lần bấm.
4. Bấm `Enter`.
   **Đúng:** mở đúng bài đang được chọn, không phải bài đầu tiên.

---

## Kịch bản 4 — Trang kết quả đầy đủ

1. Mở thẳng **http://localhost:8080/?s=mat+troi**

**Đúng, đối chiếu từng con số:**

| Chỗ nhìn | Phải thấy |
|---|---|
| Ba tab | Tất cả **7** · Bài viết **6** · Tài liệu PDF **1** |
| Dòng dưới tiêu đề | "Tìm thấy **7** nội dung." |
| Đếm số hàng trên trang | đúng **7** hàng |

> Ba con số này phải bằng nhau. Lệch nhau là lỗi — đây chính là lỗi đã sửa hôm
> 15/08, nên nếu thấy lệch lại thì là hồi quy.

2. Nhìn cách sắp xếp: ảnh và chữ **đảo bên luân phiên** từng hàng.
3. Bấm tab **"Tài liệu PDF"**.
   **Đúng:** còn 1 hàng, URL có thêm `&group=pdf`.

---

## Kịch bản 5 — Tìm nội dung bên trong file PDF

1. Mở **http://localhost:8080/?s=chuoi+hat**

**Đúng:**
- Có một hàng mang nhãn **"PDF · trang 2"**.
- Đoạn trích là *"…nến, dâng một chén nước trong. Chuông nhỏ đặt bên tay phải.
  **Chuỗi hạt** để bên tay trái."*
- Nút chính ghi **"Mở đúng trang"**.

2. Bấm "Mở đúng trang".
   **Đúng:** sang trang ấn phẩm, URL kết thúc bằng **`?trang=2`**.

3. Quay lại kết quả, bấm nút phụ **"Tải xuống"**.
   **Đúng:** trình duyệt tải về file `nghi-quy-tung-niem-hang-ngay.pdf` (~30 KB),
   mở lên đọc được bình thường. Link nút này là
   `/wp-json/nntm-search/v1/pdf/<id>` chứ **không phải** URL thẳng tới file.

4. Thử tiếp `mat troi` → phải ra **"PDF · trang 3"**.

5. Dán nguyên một câu dài lấy từ trong PDF:
   `Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên thuyết tại`
   **Đúng:** ra **"PDF · trang 1"**. Đây là lỗi vừa sửa 16/08 — câu có chữ
   `là`, `Tứ`, `Đế` từng làm cả câu không ra kết quả nào.

> Chú ý: bạn gõ **không dấu**, chữ trong file PDF thì **có dấu**. Ra được là
> đúng. Đây là điểm khác biệt so với tìm kiếm mặc định của WordPress.

### 5b. Tự tải một PDF mới lên

1. Vào **Bảng tin → Media → Add New**, tải lên một file PDF **có chữ** (không
   phải bản scan).
2. Ra ngoài site, tìm một cụm từ nằm ở giữa file đó.

**Đúng:** ra kết quả kèm đúng số trang.
**Nếu không ra:** kiểm lại bước 0.2 — dịch vụ đọc PDF có đang chạy không.

---

## Kịch bản 6 — Tìm bằng hình ảnh

1. Mở File Explorer tới `C:\Users\Admin\Downloads\nntm-test-anh`.
2. **Kéo** `03-ngoi-chua.webp` **thả vào ô tìm kiếm** trên header.

**Đúng:**
- Lúc kéo qua ô tìm, viền ô đổi thành **nét đứt**.
- Thả ra: hiện "Đang xem ảnh…", rồi:

```
Ảnh này có:  [ ngôi chùa 34% ]  [ chư tăng 18% ]  [ sương mù 11% ]

→ Biểu Tượng và Hoa Văn Mật Tông
→ [TEST] Ngôi chùa trên đỉnh núi        ← bài mẫu phải có mặt
→ Diệu Thượng
```

3. Bấm vào chip **"ngôi chùa"**.
   **Đúng:** ô tìm tự điền "ngôi chùa" và chạy tìm bằng chữ như bình thường.

4. Bấm **nút camera** cạnh kính lúp, chọn `04-kinh-sach.png`.
   **Đúng:** từ khoá `sách 59% · kinh sách 33%`, trong kết quả có
   **[TEST] Giữ gìn kinh sách**.

5. Chụp màn hình bất kỳ (`PrtSc`), click vào ô tìm, bấm `Ctrl` + `V`.
   **Đúng:** cũng chạy tìm bằng ảnh, không cần lưu file ra đĩa trước.

---

## Kịch bản 7 — Phân quyền, làm trên HAI cửa sổ

Đây là kịch bản quan trọng nhất. Rò ở đây là rò dữ liệu thật.

### 7a. Tìm bằng chữ

| Cửa sổ | Gõ `tuong phat` | Phải thấy |
|---|---|---|
| **Ẩn danh** | | **đúng 1 kết quả**: `[TEST] Tượng Phật trong chánh điện` |
| **Đã đăng nhập** | | **6 kết quả**, trong đó có `[TEST] Tượng Phật — bài dành cho thành viên` và các bài `Kim Cương Hành Giả – Bài 1…4` |

> **Dấu hiệu rò:** cửa sổ ẩn danh nhìn thấy bài có chữ *"dành cho thành viên"*
> hoặc bất kỳ bài `Kim Cương Hành Giả – Bài N` nào. Thấy là dừng lại và báo.

### 7b. Tìm bằng ảnh

Thả `02-tuong-phat.jpg` vào ô tìm ở **cả hai cửa sổ**.

| Cửa sổ | Từ khoá | Số kết quả |
|---|---|---|
| Ẩn danh | tượng Phật 88% | **1** |
| Đã đăng nhập | tượng Phật 88% | **6** |

Từ khoá đọc ra **giống hệt nhau** ở hai cửa sổ — máy nhìn ảnh thì ai cũng như
ai. Chỉ **danh sách kết quả** mới khác. Nếu từ khoá cũng khác nhau thì có gì đó
sai ở tầng đọc ảnh.

### 7c. Vào thẳng URL

Ở cửa sổ ẩn danh, gõ thẳng vào thanh địa chỉ:

| URL | Phải bị |
|---|---|
| `/dai-si-hanh-gia/` | đá về `/dang-nhap/` |
| `/kim-cuong-hanh-gia/` | đá về `/dang-nhap/` |
| `/tham-gia-chuoi-tri/` | đá về `/dang-nhap/` |
| `/nghi-quy/` | **vào được** — trang này công khai |

---

## Kịch bản 8 — Ảnh không tìm ra chữ nào khớp

1. Cửa sổ **ẩn danh**, thả `01-rung-thong.jpg`.

**Đúng:** từ khoá `rừng thông 40% · rừng 26% · sương mù 25%`, có kết quả.

2. Tìm một ảnh trong máy bạn có nội dung **không liên quan gì** tới site (ảnh
   hoá đơn, ảnh chụp màn hình phần mềm…), thả vào.

**Đúng — một trong hai câu, tuỳ trường hợp:**

> *"Đọc được từ khoá nhưng không bài nào nhắc tới — hiện nội dung có ảnh trông
> giống nhất:"* — máy nhận ra vật thể nhưng site không có bài nào về nó.

> *"Không đọc được từ khoá nào — hiện nội dung có ảnh trông giống nhất:"* —
> máy không nhận ra gì.

**Sai:** hiện "không đọc được từ khoá" mà bên dưới vẫn liệt kê chip từ khoá.
Hai câu này phải khớp với thực tế.

---

## Kịch bản 9 — Chặn file xấu

1. Bấm nút camera, chọn `06-anh-qua-lon.jpg` (5,3 MB).
   **Đúng:** "Ảnh quá lớn, tối đa 5MB." — và **không có** yêu cầu nào gửi lên
   máy chủ (chặn ngay ở trình duyệt).

2. Bấm nút camera, chọn `07-file-gia-mao.jpg`.
   **Đúng:** "Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF."

> File này thực chất là HTML, chỉ đổi đuôi thành `.jpg`. Máy chủ kiểm **nội dung
> thật** chứ không tin đuôi file. Nếu nó xử lý bình thường thì đó là lỗ hổng.

3. Thả liên tiếp **hơn 10 ảnh trong vòng 1 phút**.
   **Đúng:** đến lượt thứ 11 hiện "Bạn tìm hơi nhanh, thử lại sau một chút."
   Đợi 1 phút là dùng lại được.

4. Gõ vào ô tìm: `<script>alert(1)</script>`
   **Đúng:** hiện ra dạng chữ trong bảng kết quả. **Không được** bật hộp thoại
   cảnh báo nào.

---

## Kịch bản 10 — Màn hình nhỏ

1. Bấm `F12`, bật chế độ thiết bị, chọn **iPhone** (375px).
2. Tải lại trang, mở `/?s=thien`.

**Đúng:**
- Mỗi hàng kết quả **xếp dọc**: ảnh trên, chữ dưới.
- **Không** có hàng nào bị đảo ngược thành chữ trên ảnh dưới.
- Ba tab lọc **cuộn ngang** được, không tràn vỡ.
- Bảng gợi ý bám sát mép, không lòi ra ngoài màn hình.

3. Đổi sang **iPad** (768px), kiểm lại.

---

## Dọn dẹp sau khi test xong

```bash
C:\xampp\php\php.exe tools/test-data-tim-kiem.php xoa
```

Xoá 5 bài `[TEST]`, tài khoản `nntm_test` và thư mục ảnh mẫu trong Downloads.
Ba file PDF demo trong Thư viện thì **không** bị xoá — muốn bỏ thì xoá tay
trong Media.

---

## Chưa làm, đừng test

| Việc | Tình trạng |
|---|---|
| OCR file PDF dạng scan | chưa cắm Tesseract. PDF scan hiện bị bỏ qua, không báo lỗi |
| Trang kết quả riêng cho tìm bằng ảnh | mới có bảng gợi ý, chưa có trang đầy đủ |
| Hàng đợi nền khi tải nhiều file | đang xử lý đồng bộ; tải 20 file một lúc sẽ chậm |
| Search engine ngoài (Meilisearch) | chưa; nội dung bài vẫn qua `WP_Query` |
| Cấu hình FULLTEXT trên staging/production | Local đã đặt `innodb_ft_min_token_size=2` và bảng stopword rỗng. **Máy chủ mới phải làm lại**, xem `08-test-case-tim-kiem.md` ghi chú D-11 |
