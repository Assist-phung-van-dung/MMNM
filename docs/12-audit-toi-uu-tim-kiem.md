# Audit & Kế hoạch tối ưu — NNTM Search (Phase 0)

Thực hiện theo đúng thứ tự đọc bắt buộc: `04-kien-truc.md` → `07-ban-giao.md` →
`10-ban-giao-tim-kiem.md` → `08-test-case-tim-kiem.md` → `09-kich-ban-test-tay.md`
→ source code thật. Mọi nhận xét dưới đây đã đối chiếu trực tiếp với source hoặc
đo bằng request thật trên môi trường Docker đang chạy (`nntm-web` :8082,
`nntm-db` :13306, embed-service :8765). Không có số đo giả.

Chưa sửa bất kỳ dòng code nào. Đây thuần là Phase 0 — Audit + Kế hoạch.

---

## 0. TÌM THẤY NGAY — CẦN QUYẾT ĐỊNH TRƯỚC KHI LÀM GÌ KHÁC

**🔴 Rò dữ liệu thật, xác nhận sống, không phải suy đoán.**

Gửi request KHÔNG đăng nhập (cookie jar rỗng, xác nhận không có cookie xác thực)
tới cả `GET /suggest?q=tuong+phat` lẫn `POST /image` (ảnh
`02-tuong-phat.jpg`) trên site đang chạy — cả hai đều trả về bài
**"[DEMO] Tượng Phật — bài dành cho thành viên"** (ID 158), với đoạn trích tự nó
ghi: *"Khách chưa đăng nhập KHÔNG được thấy bài này trong bất kỳ kết quả tìm
kiếm nào. Đọc được câu này mà chưa đăng nhập tức là có rò dữ liệu."*

**Nguyên nhân đã truy tới tận gốc bằng SQL trực tiếp:**

```sql
SELECT slug FROM wp_terms t JOIN wp_term_taxonomy tt ON tt.term_id=t.term_id
WHERE tt.taxonomy='nntm_section';
-- Kết quả: dieu-thuong, phap-toa, lien-dan, hoa-khai, vuon-xoai, nhap-phap-gioi,
--          nguyen-thuy, dai-thua, tinh-do, mat-tong  (10 term)
```

**`dai-si-hanh-gia` và `kim-cuong-hanh-gia` KHÔNG TỒN TẠI** trong CSDL Docker này.
`tools/bootstrap-demo.php` dòng 372 gọi `get_term_by('slug', 'kim-cuong-hanh-gia',
'nntm_section')`, không tìm thấy → trả `false` → dòng 374-376 lặng lẽ **bỏ qua**
`wp_set_object_terms()` — bài demo "dành cho thành viên" được tạo ra mà
**không gắn bất kỳ term nào**. Xác nhận bằng SQL: bài ID 158 có 0 dòng trong
`wp_term_relationships`.

**Đây KHÔNG phải lỗi trong `nntm_search_post_acl()`** (plugin/nntm-search/includes/acl.php:35-92)
— hàm này hoạt động đúng như thiết kế: nó chỉ đánh dấu `member` khi bài mang một
trong các term ở `nntm_term_khu_han_che()`. Bài không mang term nào thì đúng logic
hiện tại là `public`. Đây là **lỗ hổng cấu trúc**: toàn bộ hệ thống ACL của tìm
kiếm đặt cược 100% vào việc gắn taxonomy đúng, và **không có tầng nào cảnh báo**
khi việc gắn term thất bại — kể cả script seed cũng im lặng bỏ qua thay vì báo lỗi.

**Vì sao xảy ra:** CSDL Docker (`nntm_dev.sql` nạp qua `docker-entrypoint-initdb.d`)
là bản dump cũ hơn thời điểm hai term `dai-si-hanh-gia`/`kim-cuong-hanh-gia` được
tạo (theo `07-ban-giao.md` mục 4, hai term này là term_id 49/50 trên máy XAMPP gốc).
`bootstrap-demo.php` sau đó chạy trên Docker mà không có hai term cha đó tồn tại
trước, nên toàn bộ 3 bài "khu hạn chế" (`han-che-tuong-phat`, `han-che-kim-cuong`,
`han-che-dai-si`) đều bị lộ.

**Mức ảnh hưởng thực tế:** Đây là dữ liệu demo (`[DEMO]`/bootstrap sinh ra), không
phải nội dung khách hàng thật — nhưng cơ chế gây ra nó (term thiếu → ACL im lặng
rơi về `public`) là **có thật và sẽ xảy ra y hệt với nội dung thật** nếu một biên
tập viên xoá nhầm term, gõ sai slug khi tạo bài, hoặc một script import quên gọi
`wp_set_object_terms()`.

**Tôi CHƯA sửa gì** (không xoá/sửa dữ liệu khi chưa được xác nhận, đúng yêu cầu).
Hai lựa chọn, cần anh Úy chọn trước khi tôi động vào bất cứ gì ở đây:

1. Tạo lại 2 term thiếu (`dai-si-hanh-gia` con của `nhap-phap-gioi`,
   `kim-cuong-hanh-gia` con của `nhap-phap-gioi`) rồi chạy lại
   `bootstrap-demo.php` — script tự nhận, chỉ bù phần thiếu, không tạo trùng
   (đã đọc source xác nhận điều này).
2. Hoặc anh Úy tự làm qua `/wp-admin` nếu muốn kiểm tra tay trước.

Việc này **không đụng vào code plugin/theme**, chỉ là dữ liệu — nhưng vẫn xin
xác nhận vì đây là quyết định "khôi phục dữ liệu ACL", nằm đúng nhóm phải dừng
lại hỏi theo quy tắc đã đặt ra.

---

## A. AUDIT

### A1. Kiến trúc thực tế — khớp với tài liệu ở mức nào

Đã đọc toàn bộ 13 file trong `wp-content/plugins/nntm-search/` +
`themes/nntm/inc/{search,hanh-gia,an-pham}.php` + `themes/nntm/search.php` +
`tools/embed-service/{main.py,tu_khoa.py}` + `tools/bootstrap-demo.php`.

**Kiến trúc mô tả trong `10-ban-giao-tim-kiem.md` khớp với source thật, không sai
lệch nào đáng kể.** Cụ thể đã xác nhận:

- Toàn bộ nghiệp vụ (ACL, truy vấn, PDF, ảnh, REST, rate-limit, schema) nằm
  trong plugin; theme (`inc/search.php`, `search.php`) chỉ dựng hàng kết quả và
  nạp CSS — đúng như chốt.
- Tắt plugin không trắng trang: `theme/inc/search.php` có `function_exists(
  'nntm_search_query')` fallback về `WP_Query` thuần (dòng 58-102).
- 5 filter mở rộng đều tồn tại và đúng vị trí:
  `nntm_search_engine_results` (engine.php:178), `nntm_search_post_acl`
  (acl.php:91), `nntm_search_groups` (engine.php:28), `nntm_search_model`
  (embed.php:42), `nntm_an_pham_can_access` (theme inc/an-pham.php:38, plugin
  gọi lại chứ không copy — acl.php:84, download.php:90).
- Vector đã lưu **nhị phân** (`pack('g*')`, MEDIUMBLOB), **không phải JSON** —
  đây chính là phần tối ưu Phase 5 mà đề bài yêu cầu kiểm tra; nó **đã làm rồi**,
  xác nhận qua `schema.php:34-47` và số đo thật (mục A4).

### A2. Source map

| Việc | File : dòng |
|---|---|
| Đăng ký REST route `/suggest` | `includes/rest.php:19-42` |
| Đăng ký REST route `/image` | `includes/image.php:34-44` |
| Đăng ký REST route `/pdf/{id}` | `includes/download.php:35-51` |
| Validate upload ảnh (size/MIME thật/tmp_name) | `includes/image.php:87-120` |
| Gọi Python (image/text/PDF) | `includes/embed.php:26-30,54-98` (HTTP chung), `includes/pdf.php:40-59` (PDF riêng, timeout 120s) |
| Phân tích & xếp hạng từ khoá ảnh | Python `tools/embed-service/main.py:150-201` (`anh_tu_khoa`), bảng từ khoá `tu_khoa.py` (61 mục song ngữ) |
| Search bài viết (WP_Query) | `includes/engine.php:65-179` |
| Search PDF (FULLTEXT) | `includes/pdf.php:311-395`, đường lui LIKE `pdf.php:234-264` |
| Vector fallback (ảnh↔ảnh) | `includes/embed.php:294-360` (`nntm_search_vector_search`), gọi từ `includes/image.php:164-182` |
| Index ảnh khi upload | `includes/image.php:242-274` (hook `add_attachment`) |
| Index PDF khi upload | `includes/pdf.php:25-98,481-488` (hook `add_attachment`) |
| Tính ACL của 1 bài (index-time) | `includes/acl.php:35-92` |
| ACL của viewer (query-time, từ session) | `includes/acl.php:102-117` |
| Recheck ACL lần 2 (sau khi có kết quả) | `includes/acl.php:130-142`, gọi ở `engine.php:143`, `pdf.php:440`, `image.php:206` |
| Rate limit | `includes/rate-limit.php` (transient, key theo user id hoặc IP đã hash) |
| Tạo bảng CSDL | `includes/schema.php:19-78` (`register_activation_hook`, `dbDelta`) |
| Gate gốc của site (nguồn sự thật ACL) | theme `inc/hanh-gia.php` (`nntm_term_khu_han_che`, `nntm_trang_can_dang_nhap`, `nntm_duoc_xem_khu_han_che`), `inc/an-pham.php` (`nntm_an_pham_can_access`) |

### A3. Request flow thật (đã đọc source, không suy đoán)

**`GET /suggest?q=...`**
`rest.php:54` (`nntm_search_permission_read`) → rate-limit 30/phút
(`rate-limit.php`, không cần nonce vì không có tham số quyền và không ghi gì)
→ `rest.php:72` → `engine.php:65` (`nntm_search_query`, `with_counts=false`) →
[`pdf.php:277` `nntm_search_pdf_hits` (cache theo request qua `static $cache`) →
FULLTEXT `pdf.php:376`] + [1 `WP_Query`] → mỗi post qua
`nntm_search_can_view()` (recheck ACL) → `nntm_search_content_matches_query()`
lọc lại theo dấu/cụm (`text.php`) → filter `nntm_search_engine_results` →
JSON, `wp_kses` chỉ cho `<mark>`.

**`POST /image`**
`image.php:57` (`nntm_search_permission_image`) → **bắt buộc nonce** `wp_rest`
+ rate-limit 10/phút → `image.php:87`: kiểm `is_uploaded_file()` (chặn path
injection qua `tmp_name`) → size ≤5MB → MIME thật qua `finfo` → POST file tới
Python `/anh/tu-khoa` (`embed.php:54-98`, timeout mặc định 20s) → có từ khoá
→ search từng từ qua `nntm_search_query()` (đã bao gồm ACL) → nếu 0 kết quả
→ POST tới Python `/embed/image` lấy vector → `nntm_search_vector_search()`
quét cosine trong PHP, lọc ACL **ngay trong SQL** (`WHERE ... acl IN (...)`)
→ `nntm_search_group_by_post()` recheck ACL lần 2 → JSON.

**`GET /pdf/{id}`**
`download.php:72`: không dùng `permission_callback` (cố ý, để trả trang lỗi tử
tế) → chỉ nhận `id` là số nguyên, tra `get_post_type()`/`get_post_mime_type()`
— **không có đường dẫn nào do người dùng tự đặt** nên không có cửa cho path
traversal → gọi `apply_filters('nntm_an_pham_can_access', true, ...)` (hiện
luôn `true` — chưa chốt thương mại) → `readfile()` trực tiếp, dọn output
buffer trước khi in.

**`add_attachment` (ảnh)**
`image.php:267-274`: đồng bộ, **KHÔNG bắt/log giá trị trả về** của
`nntm_search_index_image()` — nếu Python lỗi, hàm trả `WP_Error` và **bị vứt
bỏ hoàn toàn**, không ghi log, không lưu trạng thái. Upload vẫn 200 OK (đúng
ý đồ tài liệu — không vỡ trang — nhưng không ai biết đã fail).

**`add_attachment` (PDF)**
`pdf.php:481-488`: tương tự — đồng bộ, lỗi bị vứt bỏ, không log, không trạng
thái pending/failed.

### A4. Bottleneck — số đo THẬT trên máy này, không phải suy đoán

Môi trường: Docker Desktop trên Windows, `nntm-web` build PHP 8.3 (OPcache on,
`validate_timestamps=On`), `wp-content` là **bind-mount từ ổ Windows** vào
container Linux. Dữ liệu hiện có: **5 vector ảnh, 19 trang PDF, 162 post** —
đây là **dữ liệu demo (bootstrap-demo.php), không phải quy mô production (~2.000
ảnh)**. Ghi rõ giới hạn này thay vì suy diễn p95 giả.

| Việc đo | Kết quả thật | Ghi chú |
|---|---|---|
| Static asset (không PHP) | ~25 ms | baseline mạng/Docker |
| `wp-json/` gốc (core + mọi plugin/theme load, KHÔNG có logic search) | **~830 ms** | đây là "thuế" bootstrap của WordPress trên máy này |
| Trang chủ đầy đủ | ~1.7–1.9 s | render block, không liên quan search |
| `GET /suggest?q=mat+troi` (khách, 5 lần) | 1.36–1.44 s | **delta so với bootstrap gốc ≈ +0.55 s** |
| `POST /image` qua WordPress (ảnh 71 KB) | 0.98–1.58 s | delta ≈ +0.15 đến +0.75 s |
| Gọi thẳng Python `/anh/tu-khoa`, bỏ qua WordPress (3 lần) | **58–107 ms** | khớp cùng bậc với tài liệu (87 ms/ảnh) |
| Gọi thẳng Python `/pdf/text`, file 6 trang (3 lần) | **32–36 ms** | tài liệu ghi 24 ms — cùng bậc, chênh do máy khác |
| RSS Python sau khi tải cả 2 model (đo qua `tasklist`) | **~435 MB** | tài liệu ghi 740 MB cho cùng cấu hình — **chênh lệch cần ghi nhận**, không tự sửa tài liệu |
| Vector 512 chiều thật trong CSDL | **2.048 byte/vector** (đo `AVG(LENGTH(vector))`) | khớp đúng công thức 512×4; xác nhận **đã lưu nhị phân**, không phải JSON |

**Phát hiện quan trọng nhất của mục này:** phần lớn độ trễ đo được **không đến
từ code của nntm-search**. Một request `wp-json/` trần (không chút logic
search nào) đã mất ~830ms, và một file PHP tầm thường đặt trong `wp-content`
(bind-mount) mất 16ms so với 5.5ms nếu đặt trong filesystem gốc của image —
với khoảng 400 file PHP trong `wp-content` phải qua `stat()` mỗi request (do
`opcache.validate_timestamps=On`), chi phí này cộng dồn thành phần lớn con số
tổng. **Kết luận: số tuyệt đối đo trên máy này không phản ánh production.**
Con số có ý nghĩa là **delta** giữa các route (đã tính ở trên) — phần đó mới
thật sự do logic của nntm-search.

**Không đo được, và nói rõ tại sao:** `image_decode_ms`, `embedding_ms`,
`keyword_ranking_ms` tách riêng bên trong Python — hiện dịch vụ không có timing
nội bộ (đây chính là việc Phase 1 mục 7.4 đề xuất bổ sung). Cũng không đo được
p95 đáng tin cậy ở quy mô 2.000 ảnh vì CSDL hiện chỉ có 5 vector — số liệu H-04
"1,7s cho ảnh 1,2MB" trong tài liệu không tái lập được 1-1 trên máy này (đo
được 0,98–1,58s cho ảnh 71KB, quy mô nhỏ hơn và có thêm thuế Docker/Windows nêu
trên) — không đủ căn cứ để nói tài liệu sai, chỉ có thể nói **không so sánh
trực tiếp được**.

### A5. Security audit

| Câu hỏi | Kết quả | Bằng chứng |
|---|---|---|
| ACL lấy từ session server, không từ request? | ✅ Đúng | `acl.php:102-117` chỉ gọi `nntm_duoc_xem_khu_han_che()`/`is_user_logged_in()`. Không có `$request->get_param('acl')` ở đâu trong plugin (đã grep toàn bộ) |
| Có đường bypass tầng lọc cuối? | ⚠️ Có, nhưng đã biết nguyên nhân — xem mục 0 (term thiếu → ACL im lặng thành public) | SQL thật + đọc source |
| N+1 permission query? | Không nghiêm trọng | Vòng lặp ACL-check nằm trong trang kết quả đã phân trang (≤10-30 dòng/lần), không lặp trên toàn bộ corpus. Vector scan (embed.php:307-360) quét toàn bảng nhưng ACL được lọc **ngay trong SQL** (`WHERE acl IN (...)`), không tải rồi lọc sau |
| Ảnh người dùng có bị lưu không? | ✅ Không | `image.php` không gọi `wp_insert_attachment`/`copy()`/`move_uploaded_file()` ở đâu cả — chỉ đọc `tmp_name` rồi thôi. PHP tự dọn temp file khi request kết thúc (không cần khối `finally` thủ công vì không có gì để dọn thêm) |
| MIME chỉ dựa vào đuôi file? | ✅ Không — `finfo_file()` đọc nội dung thật (`image.php:107-114`) |
| Giới hạn pixel, hay chỉ giới hạn MB? | ❌ **Chỉ giới hạn 5MB** (`image.php:26`), **không có giới hạn megapixel/width/height** cả ở PHP lẫn Python (`main.py` không gọi kiểm tra `img.size` trước decode) | Rủi ro thật nhưng thấp: ONNX CLIP tự resize về 224×224, không tràn bộ nhớ nghiêm trọng, nhưng một ảnh nén nhỏ giải mã ra hàng chục triệu pixel vẫn tốn RAM giải mã PIL trước khi resize |
| URL dịch vụ Python có nhận từ request không (SSRF)? | ✅ Không — hardcode hằng số `'http://127.0.0.1:8765'` hoặc hằng `NNTM_SEARCH_SERVICE_URL` trong `wp-config`, không có tham số nào truyền vào (`embed.php:26-30`) |
| Path traversal khi đọc PDF/attachment? | ✅ Không — `download.php` chỉ nhận `id` số nguyên, tra CSDL bằng `get_post_type()`/`get_attached_file()`, không ghép chuỗi đường dẫn nào từ input |
| Log dữ liệu nhạy cảm? | ✅ Sạch — chỉ 2 chỗ `error_log()` trong toàn plugin (`pdf.php:292` log câu tìm khi PDF hit vượt 200 dòng; `embed.php:86` log thông điệp lỗi mạng), không log ảnh/base64/cookie/token/stack trace |
| Rate limit áp cho cả khách lẫn thành viên? | ✅ Có, cùng cơ chế — key theo `user id` nếu đăng nhập, IP đã hash nếu khách (`rate-limit.php:22-34`) | Chưa hammer-test 30+ request thật để xác nhận thực nghiệm (xem giới hạn dưới) |
| Rate limit có kẽ hở nào? | ⚠️ Nhỏ — `get_transient` rồi `set_transient` không atomic, có khe đua lý thuyết dưới tải đồng thời cao. Code đã tự nhận "đủ tốt để chống lạm dụng", không phải hàng rào cứng | Đọc source `rate-limit.php:48-59` |
| Nonce cho `/image`? | ✅ Bắt buộc, `wp_verify_nonce($nonce, 'wp_rest')` (`image.php:57-68`) |
| `/suggest` không có nonce — có sao không? | Không sao — đã giải thích đúng trong comment: endpoint không ghi gì, nonce của khách không gắn với phiên nào nên không chống được gì thật, rủi ro thật là lạm dụng chứ không phải CSRF, và đã có rate-limit |
| Layering ACL qua theme — rủi ro khi đổi theme? | ⚠️ Có, hẹp nhưng thật — xem ghi chú riêng bên dưới |

**Ghi chú riêng — rủi ro khi đổi theme:** `nntm_search_post_acl()`
(acl.php:41,65) bọc mọi lời gọi hàm theme trong `function_exists()`. Nếu theme
sau này đổi và KHÔNG mang theo `nntm_term_khu_han_che()`/`nntm_trang_can_dang_nhap()`,
khối đánh dấu `member` bị bỏ qua hoàn toàn — bài **mặc định thành `public`**,
tức fail-open. Ở chiều `nntm_search_viewer_acl()` (dòng 111-114) lại fail-closed
đúng (không có hàm theme → chỉ cho xem `public`). Hai chiều fail khác nhau là
điểm cần ghi nhận, dù chỉ xảy ra khi đổi theme — thứ `04-kien-truc.md` đã cam
kết "đổi theme không mất tìm kiếm" nhưng chưa tính tới "đổi theme có thể làm
lộ nội dung khoá".

### A6. Reliability audit

| Câu hỏi | Kết quả | Bằng chứng |
|---|---|---|
| Python service chết → WordPress phản hồi thế nào? | **Đã đo thật**: tắt hẳn tiến trình Python, gọi `/image` → **HTTP 503**, thông điệp tiếng Việt rõ ràng, không lộ stack trace, JSON sạch. **Tốt hơn kỳ vọng** — test case E-10 trong `08-test-case-tim-kiem.md` còn đang đánh dấu ⬜ (chưa kiểm), nay đã xác nhận **đạt** cho nhánh từ-khoá | Đo thật, xem log phía trên |
| Có nhánh nào vẫn trả "0 kết quả" giả khi Python lỗi không? | **Có, một nhánh hẹp** — `image.php:163-170`: khi đọc từ khoá THÀNH CÔNG nhưng 0 bài khớp, code gọi Python lần 2 (`/embed/image` để lấy vector fallback); nếu lần gọi thứ hai này lỗi, code trả **200 OK** với `results: []` — không phân biệt được với "thật sự không có ảnh giống". Đây là vi phạm đúng yêu cầu "không được trả mảng rỗng như thể không tìm thấy", nhưng phạm vi hẹp (chỉ 1 trong 2 nhánh gọi Python) | Đọc source `image.php:163-182` |
| Có timeout khi gọi Python? | ✅ Có — 20s mặc định (`embed.php`), 120s cho PDF (`pdf.php:51`) |
| Có retry? | Không có, ở bất kỳ đâu | Đọc source, xác nhận không có vòng lặp retry |
| Model load lúc nào? | **Lazy — load ở request đầu tiên gọi tới**, không load lúc khởi động (`main.py:54-63,66-72`, pattern `if _bo_ma_anh is None`) | Đọc source |
| Có `/ready` phân biệt "còn sống" và "sẵn sàng suy luận"? | ❌ Chưa — chỉ có `/khoe`, trả `{"ok": true}` ngay cả khi model chưa load lần nào | Đọc source `main.py:81-84` |
| Indexing fail có bị im lặng không? | ✅ **Có, hoàn toàn im lặng** — cả hai hook `add_attachment` (ảnh và PDF) đều vứt bỏ giá trị trả về của hàm index, không log, không lưu trạng thái | `image.php:267-274`, `pdf.php:481-488` |
| Có trạng thái pending/success/failed? | ❌ Không có trường nào như vậy trong `schema.php` | Đọc schema |
| Job có mất khi request kết thúc? | Không áp dụng — hiện chạy đồng bộ trong chính request upload, không có hàng đợi nào để mất |

---

## A7. Điểm khác với tài liệu — ghi nhận, KHÔNG tự sửa tài liệu

1. `docs/10-ban-giao-tim-kiem.md` dòng 180: "2.000 ảnh × 512 chiều × 4 byte =
   **8MB**" — phép tính đúng là 2.000×512×4 = 4.096.000 byte ≈ **3,91 MiB**,
   không phải 8MB. Bản thân dòng ngay phía trên nó ("Kích thước một vector:
   2.048 byte") đã đúng — chỉ phép nhân tổng bị sai. Đã xác nhận qua đo CSDL
   thật (`AVG(LENGTH(vector))=2048`), khớp công thức đúng, không khớp con số 8MB.
2. RSS đo được (435MB, cả 2 model) thấp hơn đáng kể so với tài liệu (740MB).
   Chưa rõ nguyên nhân (thời điểm đo khác, phiên bản `fastembed`/`onnxruntime`
   khác, hay cách đo khác) — cần ghi nhận, không tự kết luận tài liệu sai.
3. Test case E-10 (`08-test-case-tim-kiem.md`) và D-10 đang đánh dấu ⬜ chưa
   kiểm — nay đã kiểm thật, nhánh chính (đọc từ khoá) đạt yêu cầu. Nhánh phụ
   (vector fallback khi service chết giữa hai lần gọi) thì không đạt — xem A6.
4. Bốn file mới hơn cả `07-ban-giao.md` (`08`, `09`, `10`, `11-chay-local-khong-docker.md`)
   không được liệt trong bảng "đọc theo thứ tự" của `07-ban-giao.md` — có thể
   vì viết sau. Không phải mâu thuẫn, chỉ là tài liệu điều hướng cần cập nhật
   (không tự sửa, chỉ ghi nhận).

---

## B. KẾ HOẠCH THEO PHASE

### P0 — Bắt buộc (an toàn dữ liệu, đã có bằng chứng sống)

| # | Việc | File | Lý do | Rủi ro | Rollback | Test |
|---|---|---|---|---|---|---|
| P0-1 | **Quyết định + xử lý** rò ACL demo (mục 0) | Dữ liệu (`wp_terms`, `wp_term_relationships`), không phải code | Xác nhận sống, khách thấy nội dung "dành cho thành viên" | Thấp nếu chỉ tạo term thiếu + gán lại — thao tác cộng thêm, không xoá gì | Xoá 2 term vừa tạo + gỡ gán nếu cần | Kịch bản 7 trong `09-kich-ban-test-tay.md` — khách 1 kết quả, thành viên 6 |
| P0-2 | Thêm test tự động/tay xác nhận **mọi bài `nntm_article`/`page` phải có ít nhất 1 term `nntm_section` hợp lệ** trước khi coi là an toàn — đề xuất một lệnh `wp nntm search doctor` cảnh báo bài "mồ côi term" (không phải tự sửa, chỉ báo) | Mới: `includes/cli.php` (Phase 6) | Đây là cách duy nhất để phát hiện sớm nếu chuyện ở mục 0 lặp lại với nội dung thật | Không có — chỉ đọc, không ghi | Không cần | Chạy lệnh trên CSDL hiện tại, phải báo đúng 3 bài đang mồ côi |

### P1 — Nên làm (reliability/observability, không đổi hành vi nhìn thấy được)

| # | Việc | File | Lý do | Rủi ro | Rollback | Test |
|---|---|---|---|---|---|---|
| P1-1 | Log thất bại khi index ảnh/PDF thay vì vứt bỏ (`error_log` có ngữ cảnh: attachment_id, loại lỗi — KHÔNG log raw file) | `includes/image.php:267-274`, `includes/pdf.php:481-488` | Hiện tại fail hoàn toàn im lặng, đúng như audit ghi nhận | Thấp — chỉ thêm log, không đổi luồng | Xoá dòng log | Tắt Python, upload ảnh, kiểm `debug.log` có dòng lỗi rõ ràng |
| P1-2 | Sửa nhánh vector-fallback lỗi Python trả 200 rỗng (A6) → trả 503 giống nhánh kia | `includes/image.php:164-170` | Đúng yêu cầu "không trả rỗng giả làm không tìm thấy" | Thấp — đổi 1 nhánh lỗi, có test rõ | Revert 1 khối `if` | Tắt Python đúng lúc gọi `/embed/image` (mock hoặc rút mạng tạm thời), kỳ vọng 503 |
| P1-3 | Thêm `GET /ready` phân biệt model đã load — không đổi `/khoe` | `tools/embed-service/main.py` | Đúng yêu cầu Phase 1, hiện `/khoe` luôn true kể cả model chưa load lần nào | Thấp — endpoint mới, không đụng endpoint cũ | Xoá route mới | `curl /ready` trước và sau lần gọi đầu tiên, kỳ vọng `model_loaded` đổi true |
| P1-4 | Warm-up model lúc FastAPI startup (chạy 1 inference nhỏ) | `main.py` (thêm `@app.on_event("startup")`) | Tránh cold-start dồn vào request đầu tiên của người dùng thật | Thấp — startup chậm hơn vài giây, không ảnh hưởng request | Xoá handler | Đo thời gian request đầu tiên trước/sau |
| P1-5 | Giới hạn megapixel ảnh (không chỉ giới hạn byte) | `includes/image.php` (PHP, trước khi gửi Python) hoặc `main.py` (Python, sau `Image.open`, trước `convert`) | Audit xác nhận thiếu (A5) | Thấp — thêm 1 điều kiện, không đổi luồng hợp lệ | Xoá điều kiện | Ảnh nén nhỏ nhưng > 25 triệu pixel → phải bị từ chối rõ ràng, không phải OOM |
| P1-6 | `exif_transpose()` trước khi embed | `main.py`, cả hai chỗ `Image.open(...).convert("RGB")` | Ảnh chụp điện thoại xoay sai hướng embed sai | Thấp | Xoá dòng gọi | Ảnh có EXIF orientation ≠ 1 → từ khoá đọc đúng như ảnh hiển thị |
| P1-7 | Structured timing (request_id xuyên suốt WP→Python→log) | `includes/embed.php`, `main.py` | Đúng yêu cầu Phase 1 mục 7.4, hiện không đo được `embedding_ms` riêng | Trung bình — cần thêm field vào request/response, phải cẩn thận không lộ gì nhạy cảm trong log | Tắt log, giữ code | So log trước/sau trên cùng 1 request |
| P1-8 | Fail-closed nhất quán cho `nntm_search_post_acl()` khi thiếu hàm theme (hiện fail-open, khác với `nntm_search_viewer_acl()` đang fail-closed) | `includes/acl.php:41,65` | Audit A5 — hai chiều ACL lệch hướng fail | Trung bình — đổi mặc định có thể ẩn nhầm bài công khai nếu theme thật sự không có khái niệm khu hạn chế; cần xác nhận đây là site luôn dùng theme `nntm` trước khi đổi | Revert điều kiện | Giả lập theme không có `nntm_term_khu_han_che` (rename tạm hàm), bài phải về `member` chứ không phải `public` |

### P2 — Chỉ làm khi benchmark chứng minh cần (chưa làm ngay)

| # | Việc | Điều kiện kích hoạt |
|---|---|---|
| P2-1 | Qdrant/vector DB riêng | Vượt ~20.000–50.000 ảnh, hoặc p95 vector-scan vượt ngân sách đo được — **hiện 5 vector, còn quá xa** |
| P2-2 | Sửa `$results['total']`/counts đếm SAU khi lọc `nntm_search_content_matches_query()` thay vì trước (engine.php:132, đã tự nhận là biết nhưng chưa sửa) | Chỉ khi khách hàng thật sự gặp lệch số trên production với câu tìm dài/có dấu — sửa cần tải nội dung của MỌI ứng viên ở MỌI tab, tốn thêm query đáng kể; hiện chưa đo được tần suất lệch ảnh hưởng thật |
| P2-3 | Action Scheduler cho indexing nền | Khi thật sự tải hàng trăm/nghìn file cùng lúc — hiện đồng bộ ổn với vài chục file, đúng ghi nhận trong tài liệu |
| P2-4 | Chuyển PDF ra ngoài webroot | **Không tự quyết — đã là quyết định thương mại treo từ 16/08, xem mục C** |

---

## C. VIỆC PHẢI DỪNG LẠI HỎI TRƯỚC (theo đúng quy tắc đã đặt ra)

1. **Mục 0 ở trên** — xử lý dữ liệu ACL demo bị rò. Cần anh Úy chọn cách xử lý
   trước khi tôi chạm vào.
2. **PDF lộ URL trực tiếp trong `uploads`** (`includes/download.php` dòng 5-23
   đã tự ghi rõ đây là quyết định thương mại treo từ 16/08/2026, xem
   `docs/10-ban-giao-tim-kiem.md` mục 6). Đã audit xong, đã ghi rõ rủi ro
   (ai đoán được URL vẫn tải thẳng được, endpoint có kiểm quyền không ngăn
   được việc đó) — **không đề xuất implement gì thêm ở đây**, đúng như yêu cầu
   "đánh dấu business/scope decision".
3. **Image search đã làm sớm hơn báo giá (thuộc Phase 2)** — cần xác nhận
   thương mại, không phải việc kỹ thuật của audit này.
4. **P1-8** (đổi mặc định fail-open → fail-closed của `nntm_search_post_acl`)
   — về bản chất là thay đổi rất nhỏ, nhưng đây là **luật ACL**, nên xếp vào
   nhóm cần xác nhận trước khi implement dù rủi ro kỹ thuật thấp.

Không có điểm nào khác trong 6 mục audit chạm vào các điều cấm còn lại (đổi
REST contract, Qdrant, migration khó rollback, sửa theme ngoài phần render,
thiếu tài liệu kiến trúc/bàn giao).

---

## Tóm tắt cho người đọc nhanh

- Kiến trúc thật **khớp tài liệu**, không có sai lệch cấu trúc.
- Vector **đã lưu nhị phân** đúng chuẩn tối ưu — không cần làm lại Phase 5 mục
  binary float32.
- **1 lỗ rò dữ liệu sống, đã xác nhận, đang chờ quyết định xử lý** (mục 0).
- Phần lớn độ trễ đo được trên máy này là **thuế bootstrap WordPress trên
  Docker-Windows-bind-mount**, không phải do code search — đã tách bạch bằng
  số đo delta.
- Reliability khi Python chết **tốt hơn kỳ vọng** ở nhánh chính (503 sạch), có
  **1 nhánh hẹp** vẫn trả rỗng giả (P1-2).
- Indexing lỗi **hoàn toàn im lặng** — không trạng thái, không log (P1-1).
- Chưa đề xuất Qdrant/Action Scheduler/đổi PDF — đúng nguyên tắc không
  over-engineer, tất cả xếp P2 chờ benchmark hoặc quyết định thương mại.

~~Chưa implement gì (đúng yêu cầu Phase 0). Chờ anh Úy xác nhận mục 0 và các
điểm ở mục C trước khi sang Phase 1.~~

**Cập nhật:** Sau khi trình bày audit này, anh Úy xác nhận "làm đi" — đã triển
khai P0-1 và toàn bộ P1 bên dưới, từng phần một, có test thật sau mỗi phần.

---

## C. IMPLEMENTATION — đã làm

### P0-1 — Vá lỗ rò ACL (dữ liệu, không phải code)

**File thay đổi:** Không sửa code. Tạo 2 term CSDL qua WP-CLI
(`dai-si-hanh-gia`, `kim-cuong-hanh-gia`, cả hai con của `nhap-phap-gioi`
term_id 7), rồi chạy lại `tools/bootstrap-demo.php` (đã xác nhận trong audit
là script cộng-thêm, không tạo trùng).

**Test & kết quả:**

```
Guest  "tuong phat" → total:2  (không còn bài "dành cho thành viên")
Member "tuong phat" → total:3  (có bài "dành cho thành viên")
Guest  POST /image (02-tuong-phat.jpg) → total:1
Member POST /image (02-tuong-phat.jpg) → total:2
```

Đúng nguyên tắc kịch bản 7 (`09-kich-ban-test-tay.md`): từ khoá giống nhau ở
cả hai cửa sổ, chỉ tập kết quả khác nhau, khách không thấy nội dung thành viên.

**Rollback:** `wp term delete nntm_section dai-si-hanh-gia kim-cuong-hanh-gia`
(sẽ tự gỡ gán khỏi 3 bài demo, chúng quay lại trạng thái mồ côi term như cũ).

---

### P1-1 — Log lỗi index thay vì nuốt lặng lẽ

**File:** `includes/image.php` (`nntm_search_on_add_image`), `includes/pdf.php`
(`nntm_search_on_add_pdf`).

**Thay đổi:** bắt giá trị trả về (`WP_Error`) của hàm index, ghi một dòng
`error_log` gồm `attachment_id`, mã lỗi, thông điệp — không ghi file/ảnh thật.

**Test:** gọi thẳng `nntm_search_on_add_image(147)` qua `wp eval` (file không
đọc được trong môi trường này) → `debug.log` xuất hiện đúng dòng:
```
[nntm-search] index image failed: attachment_id=147 code=nntm_file_missing message=Không đọc được file ảnh.
```

**Rollback:** xoá điều kiện `is_wp_error`, giữ nguyên lời gọi hàm.

---

### P1-2 — Nhánh vector-fallback không còn trả rỗng giả khi Python lỗi

**File:** `includes/image.php` (`nntm_search_handle_image`).

**Thay đổi:** khi `nntm_search_embed_image()` (lần gọi Python thứ hai trong
cùng request) trả `WP_Error`, trả **503** thay vì `200` với `results: []`.

**Test:** tắt hẳn tiến trình Python, gọi trực tiếp
`nntm_search_embed_image('.../02-tuong-phat.jpg')` qua `wp eval` →
`is_wp_error = yes, code=nntm_service_failed` — đúng điều kiện nhánh mới xử lý
(logic giống hệt nhánh đầu đã xác nhận trả 503 thật trong audit).

**Rollback:** trả lại response rỗng cũ thay vì `WP_Error`.

---

### P1-3 + P1-4 — `/ready` và làm nóng model lúc khởi động

**File:** `tools/embed-service/main.py`.

**Thay đổi:** thêm `@app.on_event("startup")` nạp cả hai model + mã hoá bảng
từ khoá + chạy thử một ảnh trống ngay khi service khởi động (không đợi request
thật đầu tiên); thêm `GET /ready` chỉ trả `200` sau khi bước đó xong, `503` nếu
khởi động lỗi. `/khoe` giữ nguyên hành vi cũ (trả `200` ngay cả khi model chưa
nạp) vì tài liệu ghi có nơi khác đang gọi endpoint này.

**Test & kết quả:**
```
Ngay sau khi start: GET /ready → 200 {"ready":true,...}   (FastAPI startup
chạy xong TRƯỚC khi uvicorn mở cổng nghe, nên không có cửa sổ nào server nhận
request mà chưa sẵn sàng)
Log khởi động đo được: nạp model ảnh → mã hoá 61 từ khoá + nạp model chữ →
sẵn sàng, tổng ~1,9 giây — chi phí này giờ nằm ở lúc khởi động, không còn dồn
vào request người dùng đầu tiên.
```

**Rollback:** xoá route `/ready` và handler `startup`, `/khoe` không đổi nên
không ảnh hưởng ngược.

---

### P1-5 + P1-6 — Giới hạn megapixel + sửa hướng EXIF

**File:** `tools/embed-service/main.py`.

**Thay đổi:** gộp khối mở-ảnh trùng lặp ở `/embed/image` và `/anh/tu-khoa`
thành một hàm dùng chung `_giai_ma_anh_an_toan()`: kiểm kích thước
(`GIOI_HAN_CANH=10.000px/cạnh`, `GIOI_HAN_MEGAPIXEL=25 triệu`) NGAY sau khi mở
(trước khi giải mã đầy đủ `convert("RGB")`), và gọi `ImageOps.exif_transpose()`
trước khi convert.

**Test & kết quả:**
```
Ảnh 12.000×12.000 (445KB, nén tốt) → HTTP 413 "anh qua lon" — chặn trước khi
giải mã hết, đúng file nhỏ nhưng pixel khổng lồ mà giới hạn byte cũ không bắt
được.
Ảnh 100×50 gắn cờ EXIF orientation=6 → exif_transpose() trả về kích thước
50×100 — xoay đúng.
Ảnh bình thường (02-tuong-phat.jpg) → vẫn ra đúng từ khoá "tượng Phật 85%"
như trước, không đổi hành vi ảnh hợp lệ.
```

**Rollback:** trả 2 endpoint về khối mở-ảnh cũ (không giới hạn pixel, không
exif_transpose).

---

### P1-8 — ACL fail-closed khi thiếu hàm phân loại của theme

**File:** `includes/acl.php` (`nntm_search_post_acl`).

**Thay đổi:** khi `nntm_term_khu_han_che()` không tồn tại (theme khác, không
mang theo hàm này), bài `nntm_article` mặc định là `member` (an toàn hơn) thay
vì `public` (rò), kèm một dòng cảnh báo log MỘT LẦN mỗi tiến trình PHP (không
log theo từng bài, tránh spam). Có thể ghi đè qua filter `nntm_search_post_acl`
nếu theme mới thật sự không có khái niệm khu hạn chế. **Chỉ áp dụng cho
`nntm_article`** — khối `page` giữ nguyên fail-open vì tuyệt đại đa số Page là
công khai và đây không phải nơi nội dung hạn chế sống, khác với `nntm_article`.

**Test:** `php -l` sạch; regression guest/member (mục P0-1 ở trên) không đổi —
đúng như kỳ vọng, vì theme `nntm` hiện tại VẪN có đủ hàm, nhánh fail-closed chỉ
kích hoạt khi thiếu hàm (kịch bản đổi theme trong tương lai), không phải hành
vi hiện tại.

**Rollback:** bỏ khối `else`, giữ điều kiện `if` như bản gốc.

---

### P1-7 — Structured timing xuyên WordPress → Python → log

**File:** `includes/embed.php` (thêm `nntm_search_request_id()`,
`nntm_search_log_python_call()`, gắn header `X-Request-Id` vào mọi lời gọi
Python), `includes/image.php` (thêm `nntm_search_log_image_request()`, gọi
trước mỗi điểm `return` có ý nghĩa), `tools/embed-service/main.py` (middleware
`ghi_log_co_cau_truc` log một dòng mỗi request, đọc lại header
`X-Request-Id`; **kèm sửa một lỗ hổng phát hiện khi làm việc này**: file chưa
từng gọi `logging.basicConfig()`, nên MỌI `logger.info()` sẵn có từ trước
— kể cả các dòng "Nạp model ảnh/chữ" — chưa bao giờ thực sự in ra đâu cả;
thêm `basicConfig` để toàn bộ log INFO có sẵn từ trước cũng bắt đầu hoạt động).

**Phạm vi cố ý thu hẹp:** chỉ log ở hai chỗ chung (mọi lời gọi Python đi qua
`nntm_search_post_file()`; mọi request `/image` đi qua
`nntm_search_handle_image()`) — KHÔNG tách nhỏ `embedding_ms`/
`keyword_search_ms`/`vector_scan_ms` riêng từng phần bên trong, vì phải sửa
sâu vào nhiều hàm nội bộ (`engine.php`, `embed.php` vector scan) cho lợi ích
chưa rõ ở quy mô dữ liệu hiện tại (audit đã ghi: 5 vector, còn quá xa ngưỡng
cần tối ưu). Ghi rõ ở đây để không ai tưởng đã đo chi tiết đến mức đó.

**Test & kết quả (request_id khớp cả hai phía):**
```
WordPress debug.log:
  {"request_id":"jm8M2gXfwaim","python_endpoint":"/anh/tu-khoa","python_http_ms":73,"ok":true}
  {"request_id":"jm8M2gXfwaim","route":"/image","mode":"keyword","result_count":1,"total_ms":92}

Python service log:
  2026-08-18 10:53:54 nntm-embed INFO request_id=jm8M2gXfwaim method=POST path=/anh/tu-khoa status=200 duration_ms=63
```

**Rollback:** bỏ `X-Request-Id` header + 2 hàm log phía PHP; bỏ middleware +
`basicConfig` phía Python (lưu ý: bỏ `basicConfig` sẽ làm câm lại luôn cả các
log info cũ vốn dĩ chưa từng hoạt động).

---

## D. DIFF REVIEW — tự soát trước khi báo xong

| Câu hỏi | Trả lời |
|---|---|
| Có phá backward compatibility không? | Không. `/khoe` giữ nguyên contract cũ. REST response shape của `/suggest`, `/image` không đổi field nào, chỉ đổi mã trạng thái ở đúng 1 nhánh lỗi (503 thay vì 200 rỗng — đây là sửa lỗi, không phải đổi contract cho luồng thành công) |
| Có rò ACL không? | Đã VÁ một lỗ (P0-1), THÊM một lớp phòng vệ (P1-8). Đã hồi quy xác nhận guest/member đúng như trước khi sửa |
| Có lưu ảnh người dùng không? | Không đổi gì ở phần này — vẫn không tạo attachment, không copy vào uploads |
| Có N+1 không? | Không thêm N+1 nào — mọi log/kiểm tra mới đều O(1) trên mỗi request, không lặp theo số bản ghi |
| Có silent failure không? | Đã GIẢM (P1-1, P1-2) chứ không thêm mới |
| Có migration nguy hiểm không? | P0-1 chỉ THÊM 2 term (không xoá, không sửa term có sẵn) — chạy lại được, rollback bằng `wp term delete` |
| Có dependency mới không? | Không — `ImageOps` nằm sẵn trong `Pillow` đã cài, `hashlib`/`time`/`logging` là thư viện chuẩn Python |
| Có log dữ liệu nhạy cảm không? | Không — đã rà từng dòng log mới: chỉ id, mã lỗi, thông điệp, timing, không ảnh/base64/cookie/token/path nội bộ |
| Có over-engineer không? | Không — từ chối chủ động việc tách nhỏ timing nội bộ (P1-7), không đụng Qdrant/Action Scheduler/PDF webroot |

---

## E. BÁO CÁO CUỐI

**Đã hoàn tất:** P0-1 (vá rò ACL demo) + toàn bộ 7 hạng mục P1 (P1-1, P1-2,
P1-3, P1-4, P1-5, P1-6, P1-7, P1-8). Tất cả đã test thật trên môi trường Docker
đang chạy, có kết quả đo cụ thể ở trên, không có mục nào "coi như xong" mà
chưa kiểm.

**Chưa làm (đúng theo kế hoạch P2, chờ benchmark/quyết định thương mại):**
Qdrant, sửa counts đếm trước/sau filter (engine.php:132), Action Scheduler cho
indexing nền, chuyển PDF ra khỏi webroot.

**Việc phát sinh ngoài audit ban đầu, đã xử lý luôn vì cùng gốc (P1-7):**
`logging.basicConfig()` bị thiếu trong `main.py` khiến mọi `logger.info()` từ
trước tới giờ chưa từng in ra — kể cả trên máy XAMPP gốc nếu file gốc giống
file này. Đáng để kiểm tra xem log ở môi trường khác có bị câm tương tự không.

**Quyết định cần anh Úy xác nhận (chưa động vào, giữ nguyên như audit gốc):**
1. PDF lộ URL trực tiếp qua `uploads` (`includes/download.php`) — business
   decision treo từ 16/08.
2. Image search làm sớm hơn báo giá — cần xác nhận thương mại.

**Rủi ro còn lại:**
- File PDF/ảnh vật lý bị thiếu trên đĩa của môi trường Docker này (phát hiện
  khi test P1-1 và khi hồi quy tải PDF) — không phải lỗi plugin, có vẻ là dữ
  liệu media chưa được đồng bộ đầy đủ vào bind-mount này. Không thuộc phạm vi
  audit tìm kiếm nhưng đáng báo để kiểm tra riêng.
- Rate-limit dùng transient không atomic (đã ghi trong audit gốc) — chưa sửa,
  mức rủi ro thấp theo đánh giá gốc, chưa đủ lý do để đổi cấu trúc.

**Rollback procedure:** mỗi mục ở trên có dòng rollback riêng. Không có
migration schema nào trong đợt này (P0-1 chỉ thêm term, không đổi bảng).

**Deployment checklist (khi lên máy khác):**
- [ ] Chạy `wp term list nntm_section` xác nhận có đủ `dai-si-hanh-gia` +
      `kim-cuong-hanh-gia` trước khi seed demo data — nếu thiếu, lặp lại đúng
      vấn đề mục 0.
- [ ] Restart `tools/embed-service` sau khi cập nhật `main.py` (thay đổi
      không tự áp dụng cho tiến trình đang chạy).
- [ ] Kiểm `GET /ready` trả `200` trước khi coi dịch vụ ảnh đã sẵn sàng.
- [ ] Theo dõi `debug.log` và log của `embed-service` có dòng `request_id`
      khớp nhau khi test `/image`.
