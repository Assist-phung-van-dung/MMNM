# Bàn giao — Hệ thống Tìm kiếm

Phiên 15–16/08/2026. Đọc file này trước, rồi mới mở code.

Bổ sung cho `07-ban-giao.md`, không thay thế. Mọi ràng buộc ở `04-kien-truc.md`
vẫn giữ nguyên trừ ba chỗ ghi rõ ở mục 6 bên dưới.

---

## 1. Làm được gì

| | |
|---|---|
| Tìm chữ không dấu, không phân biệt hoa thường | ✅ |
| Bảng gợi ý tức thì dưới thanh tìm kiếm | ✅ |
| Trang kết quả có tab lọc, phân trang, hàng so le | ✅ |
| Tìm **nội dung bên trong file PDF**, trả về đúng số trang | ✅ |
| Tải file PDF về từ kết quả tìm | ✅ |
| **Tìm bằng hình ảnh** — đọc ảnh ra từ khoá rồi tìm | ✅ |
| Cổng quyền: khách không thấy nội dung khu Hành Giả | ✅ |
| OCR file PDF dạng scan | ❌ chưa |
| Trang kết quả riêng cho tìm bằng ảnh | ❌ chưa |
| Hàng đợi nền (Action Scheduler) | ❌ chưa, đang chạy đồng bộ |
| Search engine ngoài (Meilisearch) | ❌ chưa |

---

## 2. Kiến trúc

```
Trình duyệt
   │  gõ chữ / thả ảnh
   ▼
WordPress REST  /wp-json/nntm-search/v1/
   ├── GET  /suggest        gợi ý tức thì
   ├── POST /image          đọc ảnh → từ khoá → tìm
   └── GET  /pdf/{id}       tải file PDF, có kiểm quyền
   │
   ├──► wp_posts        (WP_Query — bài viết, trang, ấn phẩm)
   ├──► wp_nntm_pdf_pages   (FULLTEXT — text từng trang PDF)
   ├──► wp_nntm_image_vectors (vector ảnh, quét cosine trong PHP)
   └──► 127.0.0.1:8765  dịch vụ Python: đọc ảnh, trích text PDF
```

### Chia tầng

| Ở đâu | Việc gì |
|---|---|
| `plugins/nntm-search/` | toàn bộ nghiệp vụ: quyền, truy vấn, PDF, ảnh, REST |
| `themes/nntm/inc/search.php` | chỉ vẽ: dựng hàng kết quả, nạp CSS |
| `themes/nntm/search.php` | template trang kết quả |
| `tools/embed-service/` | dịch vụ Python, chạy tại chỗ |

Đổi theme không mất tìm kiếm. Tắt plugin thì trang kết quả tự lui về `WP_Query`
thuần, không trắng trang.

### Điểm cắm để thay ruột

| Filter | Đổi được gì |
|---|---|
| `nntm_search_engine_results` | thay cả tầng truy vấn — chỗ cắm Meilisearch sau này |
| `nntm_search_post_acl` | mức quyền của một bài khi lập chỉ mục |
| `nntm_search_groups` | các tab lọc |
| `nntm_search_model` | đổi model đọc ảnh |
| `nntm_an_pham_can_access` | cổng quyền ấn phẩm (của theme, dùng lại) |

---

## 3. Quyền — chỗ dễ vỡ nhất

Cổng quyền của site nằm ở `pre_get_posts` (`theme/inc/hanh-gia.php` tầng 2).
Tầng đó **chỉ che khi truy vấn đi qua `WP_Query`**. Khi cắm search engine ngoài,
truy vấn không qua `WP_Query` nữa và tầng đó mất tác dụng hoàn toàn.

Vì vậy quyền được đưa **vào chính chỉ mục**: mỗi document mang trường `acl`
(`public` / `member`), tính lúc lập chỉ mục bằng cách **gọi lại đúng hàm của
theme** — `nntm_term_khu_han_che()`, `nntm_trang_can_dang_nhap()`,
`nntm_an_pham_can_access` — chứ không chép luật sang. Hai bản luật là hai chỗ để
lệch, mà lệch ở đây là rò.

Lúc truy vấn, mức quyền lấy từ **phiên đăng nhập phía máy chủ**, không bao giờ
nhận từ request. Thêm tham số `acl` vào URL không có tác dụng gì.

Lọc **hai lần**: lần một trong SQL, lần hai bằng PHP sau khi engine trả về —
phòng khe vài giây giữa lúc BQT đổi quyền một bài và lúc job chạy lại.

### Một lỗ đã bịt trong phiên này

Đề bài yêu cầu tìm cả **trang** (`page`). Vừa thêm post type đó vào phạm vi tìm
thì hai trang `dai-si-hanh-gia` và `kim-cuong-hanh-gia` **hiện ngay trong kết quả
của khách chưa đăng nhập**. Nguyên nhân: theme chặn Page ở tầng 1
(`template_redirect` → 302), nhưng tầng 2 chỉ loại `nntm_article` theo taxonomy,
**không đụng gì tới Page**. Bao lâu nay không ai thấy vì tìm kiếm chưa từng quét
`page`.

Đã bịt trong `includes/acl.php`. Đây là chỗ rò thứ hai cùng loại, sau vụ 10 bài
Đại Sĩ ngày 14/08 — đúng như mục 5 của `07-ban-giao.md` mô tả: *"nội dung khoá rò
ra chủ yếu ở những chỗ không ai để ý"*.

---

## 4. Tìm trong PDF

PDF là **file upload Media bình thường** (chủ dự án chốt 16/08), không cất ngoài
webroot.

Luồng: tải PDF lên → hook `add_attachment` → gửi file sang dịch vụ Python →
`pypdf` trích text từng trang → lưu **một dòng mỗi trang** vào
`wp_nntm_pdf_pages` kèm cột `folded` đã bỏ dấu.

Một dòng mỗi trang là toàn bộ lý do trả lời được *"nằm ở trang mấy"* và mở đúng
trang. Gộp cả cuốn thành một ô text là mất vĩnh viễn thông tin đó.

Không dùng `shell_exec()` và không cần cài `poppler`: trích text làm trong dịch
vụ Python đang có sẵn.

### ⚠️ Cấu hình FULLTEXT — phải làm lại trên staging và production

Đây là lỗi mất nhiều thời gian nhất phiên này, ghi kỹ để khỏi cắn lần hai.

**Triệu chứng:** tìm cả câu `Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên
thuyết tại` trả về **rỗng**, dù câu đó nằm nguyên văn ở trang 1 một file PDF.
Từng từ tìm riêng đều ra; ghép lại thì mất sạch.

**Hai nguyên nhân chồng lên nhau:**

1. `innodb_ft_min_token_size` mặc định là **3**. Bỏ dấu xong `Tứ`→`tu`,
   `Đế`→`de`, `là`→`la` chỉ còn 2 ký tự nên **không vào chỉ mục**.
2. Danh sách stopword mặc định của InnoDB là **tiếng Anh**, có sẵn `de`, `la`,
   `com`, `in`, `it`, `be`, `at`, `on`, `to` — trùng với âm tiết tiếng Việt cực
   phổ biến (`là`, `để`, `đế`, `cơm`).

Code bắt buộc mọi từ phải khớp, nên **một âm tiết không index được là cả câu
chết**. Không phải mất mấy từ ngắn — là hỏng gần như mọi câu dài.

**Đã xử lý ba tầng:**

```ini
# my.ini, mục [mysqld]
innodb_ft_min_token_size=2
innodb_ft_server_stopword_table=nntm_dev/nntm_ft_stopword
```

```sql
CREATE TABLE nntm_dev.nntm_ft_stopword (value VARCHAR(30)) ENGINE=INNODB;  -- để RỖNG
ALTER TABLE wp_nntm_pdf_pages DROP INDEX ft_folded;
ALTER TABLE wp_nntm_pdf_pages ADD FULLTEXT KEY ft_folded (folded);
```

Tầng thứ ba nằm trong code: chỉ **bắt buộc** những từ mà chỉ mục thật sự chứa
được; từ quá ngắn hoặc là stopword thì hạ xuống tuỳ chọn. Ngưỡng và danh sách
stopword **đọc từ máy chủ**, không đoán — nên hosting nào không cho sửa cấu hình
thì vẫn chạy, chỉ kém chính xác hơn.

**Việc phải làm khi lên máy chủ mới:** hai tầng đầu không tự theo code. Không làm
thì tìm vẫn ra nhưng độ chính xác thấp hơn local.

---

## 5. Tìm bằng hình ảnh

Không so ảnh với ảnh, mà **đọc ảnh ra từ khoá tiếng Việt rồi tìm bằng chữ** —
đúng yêu cầu chủ dự án 16/08. Ảnh mặt trời → "mặt trời" → tìm như người dùng tự
gõ. Từ khoá hiện ra trên giao diện thành **chip bấm được**, nên người dùng thấy
được vì sao ra kết quả đó và sửa được nếu máy đọc sai.

Không đọc ra từ khoá nào, hoặc đọc ra nhưng không bài nào nhắc tới → lui về so
vector ảnh, và **nói rõ đang ở nhánh nào** bằng hai câu khác nhau.

**CLIP hiểu tiếng Anh**, nên bảng từ khoá (`tools/embed-service/tu_khoa.py`) là
**song ngữ**: nhận diện bằng câu tiếng Anh, hiển thị và tìm bằng từ tiếng Việt.
61 mục, thiên về chùa chiền và thiên nhiên. Thêm từ khoá = thêm một dòng.

**Ảnh người dùng tải lên không được lưu lại** — không tạo attachment, không chép
vào `uploads`. Đọc thẳng từ thư mục tạm của PHP rồi thôi. Mọi file trong `uploads`
đều có URL công khai đoán được; để ảnh riêng tư của người ta nằm đó vĩnh viễn là
tự mình tạo ra rò rỉ.

Vector lưu trong `wp_nntm_image_vectors`, quét cosine bằng PHP. **Chưa dùng
Qdrant**: 2.000 ảnh × 512 chiều × 4 byte = 8MB, quét hết mất vài chục mili-giây,
nhanh hơn một lượt gọi mạng. Vượt khoảng 20.000–50.000 ảnh mới cần tính lại.

---

## 6. Ba chỗ khác với `04-kien-truc.md` — cần chốt lại với khách

| Đã chốt trong kiến trúc | Thực tế hiện nay | Vì sao |
|---|---|---|
| Mục 4: file PDF **không bao giờ lộ URL**, nằm ngoài webroot, trình đọc **gỡ hết nút tải/in**, có watermark | PDF là file Media bình thường, **có nút Tải xuống** | Chủ dự án yêu cầu 16/08. Hạng mục Thư Viện PDF **30tr** trong báo giá là để làm phần bảo vệ này — cần chốt lại phạm vi |
| Mục 5: Qdrant cho vector ảnh (Phase 2) | Chưa dùng, lưu trong MySQL | 8MB dữ liệu chưa đáng một dịch vụ riêng. Đổi sau không mất gì |
| Báo giá: image search thuộc **Phase 2** | Đã làm xong | Chủ dự án yêu cầu kéo về sớm. **Cần xác nhận thương mại** — giống tình trạng khối BXH ở mục 8 `07-ban-giao.md` |

Thêm một việc kỹ thuật còn treo: nút Tải xuống đi qua endpoint có kiểm quyền,
nhưng **file vẫn nằm công khai trong `uploads`** nên ai đoán ra URL vẫn tải thẳng
được. Muốn chặn thật thì phải chuyển file ra ngoài webroot (hoặc chặn bằng
`.htaccess`) **và** bật cổng quyền ở `nntm_an_pham_can_access`.

---

## 7. Chạy ở máy local

Ba tiến trình, đúng thứ tự:

```bash
C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
```
```bash
C:\xampp\php\php.exe -S localhost:8080 -t C:\Users\Admin\Downloads\MMNM <router.php>
```
```bash
cd tools\embed-service
C:\Users\Admin\nntm-embed\venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8765
```

Kiểm dịch vụ: `http://127.0.0.1:8765/khoe` phải trả `{"ok":true}`. Không chạy thì
tìm bằng ảnh và lập chỉ mục PDF **im lặng không hoạt động**.

> ⚠️ Dịch vụ **chỉ được nghe trên `127.0.0.1`**. Đổi sang `0.0.0.0` là ai trong
> mạng cũng gọi được; lên VPS thì thành phơi ra Internet, người ngoài dùng CPU
> của khách miễn phí và không có gì chặn.

**Môi trường Python nằm ngoài repo:** `C:\Users\Admin\nntm-embed\venv`
(`fastembed`, `onnxruntime`, `fastapi`, `pypdf`, `reportlab`). Máy mới phải dựng
lại — repo chỉ có mã nguồn dịch vụ.

Model CLIP ViT-B/32 bản ONNX tự tải về lần chạy đầu (~350MB), sau đó nằm trong
bộ nhớ đệm của máy. RAM khi chạy: **477MB** nếu chỉ nạp model ảnh, **740MB** nếu
nạp cả model chữ.

---

## 8. Số đo thật

| | |
|---|---|
| Lập chỉ mục ảnh | 87 ms/ảnh (29 ảnh trong 2,5 s) |
| Trích text PDF | 24 ms cho 6 trang |
| Tìm bằng ảnh, cả vòng | 1,7 s cho ảnh 1,2 MB |
| Kích thước một vector | 2.048 byte → 2.000 ảnh chỉ 4 MB |
| Hạn mức | 30 lượt/phút cho `/suggest`, 10 lượt/phút cho `/image` |

---

## 9. Test, và dựng trên máy khác

| File | Dùng khi nào |
|---|---|
| `08-test-case-tim-kiem.md` | 77 test case + ma trận truy vết yêu cầu → case |
| `09-kich-ban-test-tay.md` | 10 kịch bản làm tay theo thứ tự, ~25 phút |
| `tools/bootstrap-demo.php` | dựng toàn bộ dữ liệu demo; thêm `xoa` để dọn sạch |

### Máy khác kéo repo về thì làm gì

Repo **không có dump CSDL** (`.gitignore` cố tình loại), nên máy mới sẽ trắng
nội dung. Dữ liệu demo vì vậy được **sinh hoàn toàn từ repo**, không phụ thuộc
máy nào:

```
tools/test-assets/anh/   5 ảnh JPG đã thu nhỏ (288 KB cả bộ), từ khoá đã đo sẵn
tools/test-assets/pdf/   3 file PDF tiếng Việt có chữ thật, ~90 KB
```

Bốn bước trên máy mới:

```bash
mysql -u root -e "CREATE DATABASE nntm_dev DEFAULT CHARACTER SET utf8mb4;"
```
```bash
php -r "require 'wp-load.php';"   # hoặc mở /wp-admin/install.php cài WordPress
```
```bash
php tools/bootstrap-demo.php
```
```bash
cd tools/embed-service && python -m uvicorn main:app --host 127.0.0.1 --port 8765
```

`bootstrap-demo.php` tự bật theme + hai plugin, tạo bảng, dựng **10 trang · 11
bài viết · 3 ấn phẩm kèm PDF · 5 ảnh · 1 tài khoản thành viên**, lập chỉ mục
PDF và ảnh, rồi chép ảnh mẫu vào `Downloads/nntm-test-anh` để kéo thả. Chạy lại
nhiều lần được và **chỉ thêm** — máy nào đã có nội dung thật của khách thì nó chỉ
bù phần còn thiếu.

Dịch vụ Python chưa chạy thì script **vẫn chạy hết**, chỉ bỏ qua bước lập chỉ mục
và nói rõ trong log. Bật dịch vụ rồi chạy lại là xong.

Đừng quên phần cấu hình FULLTEXT ở mục 4 — không tự theo code.

Từ khoá của từng ảnh mẫu đã **đo trước và ghi trong script**, nên người test biết
được kết quả đúng phải là gì thay vì đoán.

Phép thử đáng giá nhất: thả `02-tuong-phat.jpg` ở **hai cửa sổ** (ẩn danh và đã
đăng nhập). Từ khoá phải **giống hệt nhau** (`tượng Phật 85%`), nhưng khách ra
**1** kết quả còn thành viên ra **6**. Khách thấy bài có chữ *"dành cho thành
viên"* là rò thật.

---

## 10. Việc tiếp theo, theo thứ tự nên làm

1. **Chốt thương mại** ba điểm ở mục 6 — chặn cả việc kỹ thuật lẫn nghiệm thu.
2. **OCR trang scan** — Tesseract chạy tại chỗ. Chủ dự án đã chốt **không dùng
   Google Vision**, nên không có phương án thuê ngoài. Chỗ cắm đã chừa sẵn trong
   `includes/pdf.php`.
3. **Action Scheduler** — hiện lập chỉ mục chạy đồng bộ; ổn với vài chục file,
   tải hàng nghìn ảnh một lượt sẽ nghẽn.
4. **Trang kết quả riêng cho tìm bằng ảnh** — mới có bảng gợi ý.
5. **Thanh tìm kiếm cho thành viên** — `header.php` hiện chỉ có ô tìm ở nhánh
   **chưa đăng nhập**. Thành viên đăng nhập vào thì không có chỗ nào để tìm, mà
   họ mới là người xem được nhiều nội dung nhất. Chưa tự thêm vì Figma có
   component riêng `LOGGED IN 6613:10626`, có thể thiết kế cố ý như vậy —
   **cần hỏi chủ dự án**.
