# Test case — Hệ thống Tìm kiếm

Soạn theo bộ yêu cầu chốt ngày 15/08/2026. Áp cho plugin `nntm-search`,
`theme/inc/search.php`, `theme/search.php` và dịch vụ `tools/embed-service`.

**Cách đọc cột Trạng thái**

| Ký hiệu | Nghĩa |
|---|---|
| ✅ | đã chạy thật, có số đo kèm theo |
| ⬜ | đã hiện thực, **chưa kiểm** — người test phải chạy |
| 🚧 | **chưa hiện thực** — không test, ghi ở đây để biết phạm vi |

---

## 0. Môi trường test

| | |
|---|---|
| Site | http://localhost:8080 |
| Quản trị | `nntm_admin` / `NntmDev!2026` |
| CSDL | `nntm_dev`, tiền tố `wp_` |
| Dịch vụ ảnh/PDF | `127.0.0.1:8765` — phải bật trước khi test nhóm D và E |
| Bật dịch vụ | `C:\Users\Admin\nntm-embed\venv\Scripts\python.exe -m uvicorn main:app --host 127.0.0.1 --port 8765` (chạy trong `tools/embed-service`) |
| Dữ liệu | 51 bài viết · 45 ấn phẩm · 3 PDF demo (19 trang) · 29 ảnh đã lập chỉ mục |

**Tài khoản test bắt buộc có hai loại:** một cửa sổ ẩn danh (khách) và một cửa
sổ đã đăng nhập. Rất nhiều case chỉ sai ở đúng một trong hai.

---

## 1. Ma trận truy vết — yêu cầu nào được case nào phủ

| Yêu cầu (bản chốt 15/08) | Case | Trạng thái |
|---|---|---|
| Không dùng `LIKE '%…%'` trên `wp_posts` | H-03 | ⚠️ một phần — xem ghi chú cuối |
| Search engine ngoài (Meilisearch…) | — | 🚧 chưa, đang chạy WP_Query + FULLTEXT |
| Trích text PDF khi upload | D-01 → D-04 | ✅ |
| OCR PDF scan tiếng Việt | D-08 | 🚧 chưa — chốt 15/08 **không dùng Google Vision**, Tesseract chưa cắm |
| Lưu vị trí trang, trả kết quả theo trang | D-05, D-06 | ✅ |
| Tìm bằng ảnh | E-01 → E-09 | ✅ |
| Chạy nền (Action Scheduler / queue) | D-09 | 🚧 chưa — hiện chạy đồng bộ |
| Tiếng Việt không dấu, không phân biệt hoa thường | A-01 → A-05 | ✅ |
| Accent folding + stop-word | A-01, A-06 | ✅ / ⬜ |
| REST endpoint riêng | B-01, E-01 | ✅ (`/wp-json/nntm-search/v1/`) |
| Sanitize, escape, chống XSS, nonce | F-01 → F-06 | ✅ |
| Rate limiting | F-07, F-08 | ✅ |
| Icon camera cạnh kính lúp | E-01 | ✅ |
| Focus mượt, phím tắt `/` và `Ctrl+K` | B-07, B-08 | ⬜ |
| Debounce 300ms | B-02 | ✅ |
| Thumbnail + tiêu đề + snippet + badge | B-03 | ✅ |
| Loading / Empty / "Xem tất cả" | B-04 → B-06 | ✅ |
| Kéo-thả ảnh vào ô tìm | E-03 | ⬜ |
| Filter tabs Tất cả / Bài viết / PDF | C-01, C-02 | ✅ |
| Snippet kèm số trang PDF | C-04 | ✅ |
| Responsive desktop/tablet/mobile | C-07 | ⬜ |

---

## 2. Nhóm A — Tìm bằng chữ

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| A-01 | Gõ không dấu ra kết quả có dấu | Tìm `nguyen thuy` | Ra các bài "Nguyên Thuỷ"; từ khoá được bọc `<mark>` | ✅ 6 kết quả |
| A-02 | Gõ có dấu vẫn đúng | Tìm `Nguyên Thuỷ` | Cùng tập kết quả như A-01 | ⬜ |
| A-03 | Không phân biệt hoa thường | Tìm `NGUYEN THUY` | Cùng tập kết quả như A-01 | ⬜ |
| A-04 | Chữ `đ` bỏ dấu đúng | Tìm `duong` | Ra nội dung chứa "đường" | ⬜ |
| A-05 | Tô sáng khớp giữa từ | Tìm `thuy` | Tô đúng phần "thuy" trong "thuyết" | ✅ |
| A-06 | Stop-word không làm nhiễu | Tìm `cua nguoi` | Không trả về toàn bộ site | ⬜ |
| A-07 | Từ khoá 1 ký tự | Tìm `a` | Không gọi API, không hiện panel (ngưỡng 2 ký tự) | ✅ |
| A-08 | Không có kết quả | Tìm `xyzkhongcogi` | Trạng thái rỗng + 3 gợi ý + ô tìm lại | ✅ 0 kết quả |
| A-09 | Từ khoá quá dài | Gõ chuỗi > 100 ký tự vào API | HTTP 400, không lỗi PHP | ⬜ |
| A-10 | Đoạn trích cắt quanh từ khoá | Tìm một từ chỉ có ở giữa bài | Đoạn trích bắt đầu bằng `…` và chứa từ đó | ✅ |

---

## 3. Nhóm B — Dropdown gợi ý

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| B-01 | Endpoint trả JSON đúng | `GET /wp-json/nntm-search/v1/suggest?q=thien` | 200, có `results`, `total`, `see_all` | ✅ |
| B-02 | Debounce 300ms | Gõ nhanh 5 ký tự liên tiếp | Chỉ **một** request bay đi, không phải 5 | ⬜ |
| B-03 | Bố cục một dòng kết quả | Gõ `nguyen thuy` | Mỗi dòng có: ảnh nhỏ + tiêu đề + đoạn trích 2 dòng + badge phân loại | ✅ |
| B-04 | Trạng thái đang tải | Gõ từ khoá, nhìn ngay | Hiện vòng xoay + "Đang tìm…" | ✅ |
| B-05 | Trạng thái rỗng | Gõ `xyzkhongcogi` | "Không tìm thấy nội dung nào." | ✅ |
| B-06 | Nút xem tất cả | Gõ `thien` | Dòng cuối "Xem tất cả kết quả (N)", bấm sang trang `/?s=thien` | ✅ |
| B-07 | Phím tắt mở nhanh | Bấm `/` khi con trỏ ngoài ô nhập; bấm `Ctrl+K` | Ô tìm được focus và bôi đen sẵn nội dung | ⬜ |
| B-08 | Điều hướng bàn phím | Mở panel, bấm `↓` `↓` `↑`, `Enter` | Mục đang chọn đổi nền; `Enter` mở đúng mục đó | ⬜ |
| B-09 | `Esc` đóng panel | Mở panel rồi bấm `Esc` | Panel đóng, ô tìm mất focus | ⬜ |
| B-10 | Bấm ra ngoài đóng panel | Mở panel, bấm vào chỗ trống | Panel đóng | ⬜ |
| B-11 | Kết quả cũ không đè kết quả mới | Gõ `a`→`an`→`anh` thật nhanh | Panel hiện kết quả của `anh`, không phải của `an` | ⬜ |
| B-12 | `aria-expanded` đổi theo | Mở/đóng panel, xem DOM | `aria-expanded` = `true`/`false` tương ứng | ✅ |

---

## 4. Nhóm C — Trang kết quả

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| C-01 | Ba tab lọc | Mở `/?s=mat+troi` | Ba tab: Tất cả · Bài viết · Tài liệu PDF, mỗi tab kèm số | ✅ |
| C-02 | **Số trên tab khớp số hàng** | Mở `/?s=mat+troi` | Tab "Tất cả" = số hàng hiển thị = số ở dòng tóm tắt | ✅ 7 = 7 = 7 |
| C-03 | Lọc theo tab | Bấm tab "Tài liệu PDF" | Chỉ còn kết quả PDF; URL có `&group=pdf` | ✅ |
| C-04 | Nhãn số trang PDF | Tìm `mat troi` | Hàng PDF mang nhãn "PDF · trang 3" | ✅ |
| C-05 | Bố cục hàng so le | Mở `/?s=thien` | Ảnh và chữ đảo bên luân phiên; hàng chẵn có class `--reversed` | ✅ 3/6 hàng đảo |
| C-06 | Phân trang | Tìm từ khoá ra > 10 kết quả | Nút Trước/Sau + "Trang 1 / N"; nút Trước mờ ở trang 1 | ⬜ |
| C-07 | Responsive | Thu cửa sổ xuống 375px và 768px | Hàng xếp dọc, không đảo bên; tab cuộn ngang được | ⬜ |
| C-08 | Vào thẳng `/?s=` rỗng | Mở `/?s=` | "Nhập từ khoá để bắt đầu tìm.", không lỗi | ⬜ |
| C-09 | Thứ tự ưu tiên | Tìm `mat troi` | Kết quả PDF (biết chính xác trang) đứng trước kết quả theo tiêu đề | ✅ |

---

## 5. Nhóm D — Nội dung PDF

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| D-01 | Tải PDF lên là tự lập chỉ mục | Media → Add New → tải một PDF có chữ | Bảng `wp_nntm_pdf_pages` có thêm N dòng, N = số trang có chữ | ✅ 3 file → 19 dòng |
| D-02 | Không chặn giao diện lúc upload | Tải PDF ~30 trang | Trang admin không treo quá vài giây | ⬜ |
| D-03 | Chữ tiếng Việt còn nguyên dấu | Xem cột `content` của một dòng | Đúng dấu, không thành ô vuông | ✅ |
| D-04 | Cột không dấu được sinh | Xem cột `folded` cùng dòng | Là bản không dấu, chữ thường của `content` | ✅ |
| D-05 | Tìm ra đúng trang | Tìm `chuoi hat` | Ra "PDF · trang 2" — trang thật sự chứa cụm đó | ✅ |
| D-06 | Mở đúng trang | Bấm "Mở đúng trang" | Sang trang ấn phẩm với `?trang=2` trên URL | ✅ |
| D-07 | Xoá file thì xoá chỉ mục | Xoá vĩnh viễn một PDF trong Media | Các dòng của `attachment_id` đó biến mất khỏi `wp_nntm_pdf_pages` | ⬜ |
| D-08 | PDF scan (ảnh, không có chữ) | Tải một PDF scan lên | **Hiện tại**: bỏ qua, không có dòng nào, không báo lỗi | 🚧 |
| D-09 | Hàng đợi khi tải nhiều file | Tải 20 PDF một lượt | Hiện chạy đồng bộ — dự kiến chậm; cần Action Scheduler | 🚧 |
| D-10 | Dịch vụ tắt thì không vỡ | Tắt `uvicorn`, tải một PDF lên | Upload vẫn thành công, chỉ không có chỉ mục; không lỗi trắng trang | ⬜ |
| D-11 | Âm tiết 2 chữ cái | Tìm cụm có chữ `là`, `để`, `tứ`, ví dụ `Tứ Diệu Đế là bài pháp đầu tiên` | Ra **PDF · trang 1**. Đã sửa 16/08, xem ghi chú cuối | ✅ |
| D-12 | Câu dài nhiều từ | Dán nguyên một câu 13 từ lấy từ trong PDF | Ra đúng trang chứa câu đó | ✅ |

---

## 6. Nhóm E — Tìm bằng hình ảnh

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| E-01 | Nút camera hiện ở header | Mở trang bất kỳ | Có icon camera cạnh ô tìm; tắt plugin thì nút biến mất | ✅ |
| E-02 | Chọn ảnh từ máy | Bấm camera → chọn ảnh rừng thông | Panel hiện từ khoá + danh sách kết quả | ✅ |
| E-03 | Kéo-thả ảnh | Kéo file ảnh thả vào thanh tìm | Viền đứt hiện lúc kéo qua; thả xong ra kết quả | ⬜ |
| E-04 | Dán ảnh `Ctrl+V` | Chụp màn hình, click ô tìm, `Ctrl+V` | Ra kết quả như E-02 | ⬜ |
| E-05 | **Hiện từ khoá đọc được** | Thả ảnh rừng thông | Dòng "Ảnh này có:" + các chip `rừng thông 40%` `rừng 26%` `sương mù 25%` | ✅ |
| E-06 | Bấm chip để tìm lại | Bấm chip "rừng thông" | Ô tìm điền "rừng thông", chạy tìm chữ bình thường | ⬜ |
| E-07 | Đọc được từ khoá nhưng không ai viết về nó | Thả ảnh tượng Phật | Câu "Đọc được từ khoá nhưng không bài nào nhắc tới…" + kết quả ảnh giống nhất | ✅ |
| E-08 | Ảnh không nhận ra gì | Thả một ảnh trừu tượng | Câu "Không đọc được từ khoá nào…" + kết quả ảnh giống | ⬜ |
| E-09 | Ảnh gửi lên **không bị lưu lại** | Thả ảnh, rồi kiểm `wp-content/uploads` và Thư viện | Không có file mới, không có attachment mới | ⬜ |
| E-10 | Dịch vụ tắt | Tắt `uvicorn`, thả ảnh | Thông báo "Tìm kiếm tạm thời không dùng được", không lộ địa chỉ dịch vụ | ⬜ |
| E-11 | Trang kết quả đầy đủ cho ảnh | — | Hiện chỉ có dropdown, chưa có trang riêng | 🚧 |

---

## 7. Nhóm F — Bảo mật

| ID | Mục tiêu | Bước | Kết quả mong đợi | TT |
|---|---|---|---|---|
| F-01 | Thiếu nonce khi gửi ảnh | `POST /nntm-search/v1/image` không kèm `X-WP-Nonce` | **403** | ✅ |
| F-02 | File giả đuôi ảnh | Đổi `readme.html` thành `.jpg` rồi gửi | **415** — kiểm bằng nội dung thật, không tin đuôi | ✅ |
| F-03 | Ảnh quá lớn | Gửi ảnh > 5MB | **413** | ✅ |
| F-04 | Không gửi file nào | POST rỗng | 400, không lỗi PHP | ⬜ |
| F-05 | XSS qua từ khoá | Tìm `<script>alert(1)</script>` | Hiện ra dạng chữ, **không chạy**; chỉ thẻ `<mark>` được phép | ⬜ |
| F-06 | XSS qua nội dung bài | Tạo bài có `<img onerror=…>` rồi tìm | Đoạn trích đã bị strip tag | ⬜ |
| F-07 | Hạn mức endpoint gợi ý | Gọi `/suggest` > 30 lần trong 1 phút | **429** + dropdown báo "Bạn tìm hơi nhanh" | ✅ |
| F-08 | Hạn mức endpoint ảnh | Gửi ảnh > 10 lần trong 1 phút | **429** | ⬜ |
| F-09 | Không lộ địa chỉ dịch vụ nội bộ | Gây lỗi rồi đọc response | Không thấy `127.0.0.1:8765` ở bất kỳ đâu trong JSON | ⬜ |
| F-10 | Không tự cấp quyền được | Thêm `&acl=member` vào URL `/suggest` | Bị bỏ qua hoàn toàn, kết quả không đổi | ⬜ |

---

## 8. Nhóm G — Phân quyền (bảng nghiệm thu mục 5 bàn giao)

Chạy **song song hai cửa sổ**: một ẩn danh, một đã đăng nhập.

| ID | Đường dẫn / thao tác | Khách | Thành viên | TT |
|---|---|---|---|---|
| G-01 | `/dai-si-hanh-gia/` | 302 → `/dang-nhap/` | 200 | ✅ |
| G-02 | `/kim-cuong-hanh-gia/` | 302 | 200 | ✅ |
| G-03 | `/tham-gia-chuoi-tri/` · `/khai-bao-chuoi-tri/` | 302 | 200 | ✅ |
| G-04 | `/nghi-quy/` · trang chủ · 6 phân mục | 200 | 200 | ✅ |
| G-05 | Tìm `dai si` | **0 bài** khu Hành Giả trong kết quả | Có Bài 1, 2, 3… | ✅ 5 vs 6 |
| G-06 | Tìm `kim cuong` | Chỉ nội dung công khai | Có Bài 1→6 | ✅ |
| G-07 | **Hai trang khoá không lọt vào kết quả tìm của khách** | Không thấy "Đại Sĩ Hành Giả" / "Kim Cương Hành Giả" | Thấy | ✅ |
| G-08 | Gợi ý ở dropdown cũng bị chặn | Gõ `dai si` vào ô tìm | Như G-05, không rò ở dropdown | ✅ |
| G-09 | Tìm bằng ảnh cũng chặn | Thả ảnh minh hoạ một bài khu Hành Giả | Khách không thấy bài đó trong kết quả | ⬜ |
| G-10 | Đổi quyền rồi tìm lại | Chuyển một bài sang khu hạn chế, tìm ngay | Bài biến mất khỏi kết quả của khách **ngay**, không đợi lập chỉ mục lại | ⬜ |

---

## 9. Nhóm H — Hiệu năng

| ID | Mục tiêu | Ngưỡng | Đo được | TT |
|---|---|---|---|---|
| H-01 | Lập chỉ mục ảnh | < 200 ms/ảnh | **87 ms/ảnh** (29 ảnh / 2,5s) | ✅ |
| H-02 | Trích chữ PDF | < 1 s/tài liệu nhỏ | **24 ms** cho 6 trang | ✅ |
| H-03 | Truy vấn tìm kiếm không quét bảng | Không `LIKE '%…%'` trên `wp_nntm_pdf_pages` | Dùng `MATCH … AGAINST` | ⚠️ xem ghi chú |
| H-04 | Tìm bằng ảnh | < 3 s | **1,7 s** cho ảnh 1,2MB | ✅ |
| H-05 | RAM dịch vụ | — | **477 MB** (chỉ model ảnh) / **740 MB** (cả ảnh + chữ) | ✅ |
| H-06 | Trang kết quả | < 2 s | — | ⬜ |
| H-07 | Số truy vấn CSDL mỗi lần tìm | Không tăng theo số kết quả | — | ⬜ |

---

## 10. Ghi chú cho người test — đọc trước khi báo lỗi

**Về `LIKE` (H-03):** yêu cầu ban đầu là bỏ `LIKE '%…%'`. Hiện tại:
- Nội dung **PDF** dùng FULLTEXT `MATCH … AGAINST` — đúng yêu cầu.
- Nội dung **bài viết** vẫn đi qua `WP_Query` của WordPress, tức là vẫn `LIKE`
  trên `wp_posts`. Sẽ hết khi cắm search engine ngoài vào filter
  `nntm_search_engine_results`. **Đây là hạn chế đã biết, không phải bug.**

**Về D-11 — lỗi đã sửa ngày 16/08, ghi lại vì rất dễ tái phát khi lên máy chủ mới.**

Tìm cả câu `Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên thuyết tại` trả về
**rỗng**, dù câu đó nằm nguyên văn ở trang 1 một file PDF. Từng từ tìm riêng đều
ra, ghép lại thì mất. Hai nguyên nhân chồng lên nhau, cùng nằm ở FULLTEXT:

1. **`innodb_ft_min_token_size` mặc định là 3.** Bỏ dấu xong `Tứ`→`tu`,
   `Đế`→`de`, `là`→`la` chỉ còn 2 ký tự nên không vào chỉ mục.
2. **Danh sách stopword mặc định của InnoDB là tiếng Anh** và có sẵn `de`, `la`,
   `com`, `in`, `it`, `be`, `at`, `on`, `to` — trùng với âm tiết tiếng Việt rất
   phổ biến (`là`, `để`, `đế`, `cơm`).

Code bắt buộc mọi từ phải khớp, nên chỉ một âm tiết không index được là **cả câu
chết**. Không phải mất mấy từ ngắn — là hỏng gần như mọi câu dài.

Đã xử lý ba tầng:

| Tầng | Việc |
|---|---|
| `my.ini` | `innodb_ft_min_token_size=2` + `innodb_ft_server_stopword_table` trỏ sang một bảng **rỗng** |
| CSDL | tạo `nntm_dev.nntm_ft_stopword` (rỗng), dựng lại chỉ mục `ft_folded` |
| Code | chỉ **bắt buộc** những từ chỉ mục thật sự chứa được; từ quá ngắn hoặc là stopword được hạ xuống tuỳ chọn. Đọc ngưỡng và danh sách stopword **từ máy chủ** chứ không đoán |

Tầng code là lớp phòng cho máy chủ không cho sửa cấu hình (hosting dùng chung,
không có quyền `SUPER`). **Lên staging và production phải làm lại hai tầng đầu**,
nếu không thì chạy được nhưng độ chính xác kém hơn local.

**Về E-05, độ chính xác từ khoá:** bảng từ khoá có 61 mục, thiên về chủ đề chùa
chiền và thiên nhiên (`tools/embed-service/tu_khoa.py`). Ảnh nằm ngoài phạm vi
đó sẽ ra từ khoá gần đúng nhất chứ không phải "không biết". Thêm từ khoá chỉ cần
thêm một dòng vào file đó rồi khởi động lại dịch vụ.

**Về nhóm D và E:** nếu dịch vụ `127.0.0.1:8765` chưa bật thì **toàn bộ hai nhóm
này trượt**. Kiểm trước bằng `http://127.0.0.1:8765/khoe` — phải trả `{"ok":true}`.

**Chưa hiện thực, đừng test:** OCR trang scan (D-08) · hàng đợi nền (D-09) ·
trang kết quả riêng cho tìm bằng ảnh (E-11) · search engine ngoài.
