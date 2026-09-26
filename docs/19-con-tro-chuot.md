# Hiệu ứng con trỏ chuột (Phase 2)

Phiên 26/09/2026. Nhánh `giao-dien-con-tro`.

Đổi hình con trỏ chuột (mặt trời, hoa sen, sao chổi...) và một vệt sáng/hạt đi
theo khi rê chuột, cho toàn site. **Mặc định KHÔNG DÙNG** — phải vào wp-admin
bật tay.

## BQT dùng ở đâu

**Từ khoá động → Con trỏ chuột** (`edit.php?post_type=nntm_tu_khoa_dong&page=nntm-con-tro`,
quyền `edit_theme_options`). Màn này nằm cạnh "Từ khoá động" chứ không phải
"Giao diện", vì có một phần cấu hình gắn thẳng vào từng từ khoá.

Bốn phần trên cùng một trang, một nút **Lưu cài đặt** duy nhất:

1. **Xem thử** — khung nền tối/kem (nút "Đổi nền sáng/tối"), chạy đúng bộ máy
   thật ở chế độ "vùng". Đổi bất cứ ô nào ở phần đang xem thử là khung cập
   nhật ngay, chưa cần bấm Lưu. Bấm "Xem thử" ở một dòng trong hai bảng bên
   dưới sẽ đưa dòng đó lên khung.
2. **Cài đặt chung** — áp dụng mọi trang không có cài đặt riêng:
   - Lưới 21 thẻ: "Không dùng" (mặc định) + 20 kiểu, mỗi thẻ có tên + mô tả.
   - Màu chính / màu phụ (wp-color-picker, trống = màu mặc định của kiểu; màu
     phụ trống = tự pha sáng ~35% từ màu chính), nút "Về màu mặc định".
   - Độ dài vệt (thanh trượt 10–60, mặc định 30).
   - Mật độ hạt: Ít / Vừa / Nhiều.
   - **Cỡ con trỏ**: Nhỏ (×0.75) / Vừa (×1, mặc định) / Lớn (×1.35).
   - **Khi bấm vào kết quả tìm kiếm** — ô tích "Giữ hiệu ứng đang hiện sang
     trang kế tiếp", mặc định TẮT (xem mục riêng bên dưới).
3. **Theo từng trang** — bảng các dòng: gõ tên bài/trang để tìm (REST
   `/wp/v2/search`, giới hạn các loại nội dung trong filter
   `nntm_con_tro_post_types`), chọn hiệu ứng ("Theo cài đặt chung" / "Không
   dùng ở trang này" / 1 trong 20 kiểu), màu riêng, độ dài vệt riêng (trống =
   theo chung). Không cho chọn trùng một trang hai lần (kiểm cả JS lẫn lúc
   lưu). "+ Thêm trang" / "Xoá" từng dòng.
4. **Theo từ khoá tìm kiếm** — liệt kê MỌI Từ khoá động, mỗi dòng chọn hiệu
   ứng + màu. Khi câu tìm kiếm của khách khớp đúng một từ khoá (hoặc một cách
   viết khác của nó) đã gắn hiệu ứng ở đây, trang kết quả tìm kiếm dùng hiệu
   ứng đó — xem "Kích hoạt theo tìm kiếm" bên dưới.

Ngoài ra, mỗi bài/trang/CPT công khai và mỗi Từ khoá động có một **meta box**
lối tắt ở thanh bên màn sửa (select kiểu + màu, với Từ khoá động còn không có
độ dài riêng) — chỉ ghi vào đúng các khoá post meta bên dưới, không phải một
kho dữ liệu khác; đủ dùng cho việc sửa nhanh một dòng mà không cần mở màn
tổng.

## 20 kiểu

| Mã | Tên | Ý tưởng |
|---|---|---|
| `mat-troi` | Mặt trời | Lõi tròn vàng + 12 tia xoay chậm, bụi ánh vàng tan dần |
| `hoa-sen` | Hoa sen | Đoá sen 6 cánh xoay nhẹ, cánh rơi lả tả |
| `dom-sang` | Đóm sáng | Sao 4 cánh, lấp lánh sao nhỏ nhấp nháy |
| `hao-quang` | Hào quang | Chấm sáng + vòng hào quang trễ một nhịp |
| `sao-choi` | Sao chổi | Đuôi thon mượt theo quỹ đạo, mờ dần |
| `gon-nuoc` | Gợn nước | Toả gợn vòng tròn như mặt nước |
| `dom-dom` | Đom đóm | Vài đốm sáng bay lượn quanh con trỏ |
| `trang-khuyet` | Trăng khuyết | Vầng trăng khuyết, vệt ánh bạc mờ |
| `banh-xe-phap` | Bánh xe Pháp | Pháp luân 8 căm xoay chậm |
| `la-bo-de` | Lá bồ đề | Lá thuôn nhọn có gân giữa, rơi theo gió |
| `ngon-nen` | Ngọn nến | Lửa dao động, tàn lửa bay lên rồi tắt |
| `khoi-huong` | Khói hương | Đầu nhang đỏ, khói mảnh uốn lượn |
| `chuoi-hat` | Chuỗi hạt | Chuỗi hạt nối nhau đi theo như dây mềm |
| `bui-vang` | Bụi vàng | Rắc bụi vàng li ti rơi nhẹ |
| `vien-tron-thien` | Viền tròn thiền (Ensō) | Nét bút lông hở một khe, xoay chậm |
| `net-muc` | Nét mực | Dày khi đi chậm, mảnh khi đi nhanh |
| `bong-bong` | Bong bóng | Bong bóng xà phòng nổi lên, có ánh viền |
| `hoa-mai` | Hoa mai | 5 cánh vàng, cánh rơi xoay |
| `sao-bang` | Sao băng | Rê nhanh bắn tia lửa ngược hướng đi |
| `cham-vong` | Chấm vòng | Tối giản: chấm + vòng trễ, nở to trên link/nút |

Nguồn sự thật của danh sách này (tên/mô tả/màu mặc định) nằm ở **plugin**
`nntm-core/includes/con-tro-dung-chung.php` (`nntm_con_tro_ds_kieu()`,
`nntm_con_tro_mau_mac_dinh()`) — vì Từ khoá động (CPT của plugin) cũng cần
đọc được danh sách này. Theme chỉ có hai hàm bọc mỏng
(`nntm_con_tro_danh_sach_kieu()` / `nntm_con_tro_mau_mac_dinh_theo_kieu()`)
gọi thẳng sang plugin — **không có bản chép thứ hai**. Bộ máy vẽ thật (canvas,
vật lý hạt) nằm ở theme: `assets/js/con-tro.js`.

## Quy tắc bắt buộc (đã kiểm thật)

- Chỉ chạy khi `matchMedia('(hover: hover) and (pointer: fine)')` — điện
  thoại/máy tính bảng không chạy. Xác nhận: Chrome headless mặc định báo
  `hover:none` (không có thiết bị trỏ thật), và bộ máy tự im lặng không vẽ gì.
- `prefers-reduced-motion: reduce` → chỉ vẽ hình con trỏ, không vệt/hạt/xoay
  (kiểm bằng ảnh chụp, xem `sao-choi-giam-chuyen-dong.png`).
- Giữ con trỏ gõ chữ ở input/textarea/select/`[contenteditable]`.
- Chỉ ẩn con trỏ hệ thống SAU pointermove đầu tiên; ẩn hình khi chuột rời cửa
  sổ / vào iframe.
- Một vòng `requestAnimationFrame` duy nhất; dừng khi hết chuyển động + hết
  hạt, chạy lại khi có pointermove/pointerdown, dừng khi tab ẩn. Trần 300 hạt.
- **Tự thích nghi theo nền** (mới thêm): cứ ~150ms lấy màu nền thật ngay dưới
  con trỏ (`elementFromPoint` rồi dò `background-color`/ảnh/video ngược lên
  tổ tiên), tính độ sáng. CHỈ áp dụng khi màu đang dùng là **màu mặc định**
  của kiểu (BQT chưa chọn màu riêng): kiểu có màu mặc định tối (mực, khói...)
  mà gặp nền cũng tối thì tự pha sáng; kiểu có màu mặc định gần trắng mà gặp
  nền cũng sáng thì tự pha tối. Chọn màu riêng thì giữ đúng màu đó, chỉ có
  viền tối mảnh lo phần tương phản.
- Hình con trỏ phóng đại ~1.8 lần so với bản đầu (từ ~14–16px lên ~24–29px),
  hạt/vệt phóng ~1.45 lần — nhân thêm hệ số "Cỡ con trỏ" (0.75/1/1.35) và
  ×1.3 khi đang ở trên link/nút.

## Ưu tiên cấu hình ở frontend

`nntm_con_tro_cau_hinh_trang()` (theme, `inc/con-tro.php`) quyết định theo
thứ tự:

1. **Trang đơn** (`is_singular()`, các post type trong `nntm_con_tro_post_types`)
   có meta riêng (`_nntm_con_tro_kieu` = `tat` hoặc 1 trong 20 mã) → dùng meta
   đó, `tat` = tắt hẳn dù cài đặt chung đang bật.
2. **Trang kết quả tìm kiếm** (`is_search()`, kể cả trang tìm bằng ảnh —
   `nntm-search` tự đặt `?s=<từ khoá đọc được>` nên `get_search_query()` đã
   đủ) không phải trang đơn → gọi `nntm_tkd_khop_tim_kiem()` (plugin), khớp
   thì dùng hiệu ứng/màu của từ khoá đó.
3. Còn lại → **cài đặt chung**.
4. Filter `nntm_con_tro_bat` áp dụng SAU CÙNG — mặc định tắt ở trang đọc sách
   (`nntm_theme_o_trang_doc()`) trừ khi chính bài đó tự chọn một kiểu cụ thể
   trong cài đặt riêng.

Độ dài vệt/mật độ/cỡ luôn theo cài đặt CHUNG (không có riêng cho trang tìm
kiếm/từ khoá) — chỉ kiểu + màu được ghi đè.

## Kích hoạt theo tìm kiếm

- **Phía máy chủ**: `nntm_tkd_khop_tim_kiem( string $cau ): ?array` (plugin
  `nntm-core/includes/con-tro-khop-tim-kiem.php`). Quy tắc:
  - So khớp TIÊU ĐỀ + mọi "cách viết khác" của từng Từ khoá động đã gắn hiệu
    ứng (`_nntm_tkd_con_tro_kieu` khác rỗng).
  - Khớp theo NGUYÊN TỪ/CỤM TỪ (tách bằng `\p{L}\p{N}`), không khớp giữa từ:
    "hoa" khớp "Hoa sen", không khớp "hoàng".
  - Câu tìm CÓ dấu → đòi đúng dấu; KHÔNG dấu → so sánh bỏ dấu (dùng
    `nntm_search_fold()` / `nntm_search_term_has_diacritics()` của
    `nntm-search` nếu có, để đồng bộ với tìm kiếm chữ; có bản dự phòng tự thân
    nếu plugin đó tắt).
  - Nhiều từ khoá cùng khớp: ưu tiên (1) khớp nguyên văn cả cụm, (2) từ khoá
    NẰM TRỌN trong câu tìm và DÀI HƠN, (3) câu tìm là TIỀN TỐ của từ khoá và
    từ khoá NGẮN HƠN, (4) `menu_order` rồi ngày đăng mới nhất.
  - Filter `nntm_tkd_khop_tim_kiem( $ket, $cau )` để chỉnh lại kết quả.
- **Phía trình duyệt** (`assets/js/con-tro-tim-kiem.js`, theme): đổi con trỏ
  NGAY khi khách đang gõ vào ô tìm ở header, debounce 150ms, cùng bộ quy tắc
  ở trên (bản JS thuần, không gọi máy chủ). Script chỉ được nạp khi có ÍT
  NHẤT 1 từ khoá gắn hiệu ứng; bộ máy vẽ (`con-tro.js`) nạp LƯỜI — trang đang
  "Không dùng" theo cài đặt chung vẫn không tải engine cho tới khi khách gõ
  trúng một từ khoá. Xoá ô tìm / gõ câu không còn khớp gì → quay lại cấu hình
  gốc của trang.
  - Quy tắc khớp JS và PHP dùng CHUNG một bộ ca thử
    (`scratchpad/con-tro/ca-thu-khop-tu-khoa.json`), chạy qua cả
    `ca-thu-runner.php` (gọi thẳng hàm PHP thật) lẫn `ca-thu-runner.mjs`
    (bản sao thuật toán JS) — cả hai đều khớp 8/8 ca thử khi kiểm.
- **"Giữ hiệu ứng khi bấm vào kết quả tìm kiếm"** (cài đặt chung, mặc định
  TẮT): bật thì bấm bất kỳ link nào trong lúc một hiệu ứng-theo-từ-khoá đang
  hiện (do gõ hoặc do đang ở trang tìm kiếm khớp) sẽ lưu `{kieu,mau}` vào
  `sessionStorage['nntm_con_tro_giu']`. Trang kế tiếp: nếu bản thân trang đó
  KHÔNG có cài đặt riêng (`nguon !== 'trang'`) thì áp dụng giá trị nhớ này.
  Tự hết khi đóng tab (sessionStorage), hoặc khi gõ một câu tìm khác không
  còn khớp từ khoá nào (script tự xoá key). Cài đặt riêng của trang LUÔN
  thắng — không bao giờ bị giá trị nhớ ghi đè.

## Tệp

- `wp-content/themes/nntm/inc/con-tro.php` — option chung, meta box, màn
  admin, xử lý lưu, quyết định cấu hình frontend, enqueue.
- `wp-content/themes/nntm/assets/js/con-tro.js` — bộ máy vẽ (canvas, 20 kiểu,
  vật lý hạt, tự thích nghi nền).
- `wp-content/themes/nntm/assets/js/con-tro-tim-kiem.js` — khớp con trỏ khi
  gõ vào ô tìm ở header, nạp `con-tro.js` lười.
- `wp-content/themes/nntm/assets/js/admin/con-tro-admin.js` — màn quản trị:
  khung xem thử, wp-color-picker, bảng "Theo từng trang" (thêm/xoá/tìm REST).
- `wp-content/themes/nntm/assets/css/con-tro.css` — ẩn con trỏ hệ thống, định
  vị canvas, giao diện màn admin.
- `wp-content/plugins/nntm-core/includes/con-tro-dung-chung.php` — danh sách
  20 kiểu + màu mặc định + hàm sanitize dùng chung (nguồn thật).
- `wp-content/plugins/nntm-core/includes/con-tro-khop-tim-kiem.php` —
  `nntm_tkd_khop_tim_kiem()`.
- `wp-content/plugins/nntm-core/includes/class-tu-khoa-dong.php` — thêm 2
  meta (`_nntm_tkd_con_tro_kieu`, `_nntm_tkd_con_tro_mau`), select+màu trong
  meta box sẵn có, cột "Hiệu ứng con trỏ" ở màn danh sách. Xem thêm docs/13.

## Đã kiểm (kết quả thật, không phải suy đoán)

1. `php -l` mọi tệp PHP đã sửa/thêm; `node --check` mọi tệp JS — không lỗi.
2. Script PHP kiểu WP-CLI (scratchpad, nạp `wp-admin/includes/admin.php`,
   `wp_set_current_user(1)`): 27 phép thử sanitize (kiểu lạ → rỗng, `<script>`
   → rỗng, hex sai → rỗng, độ dài vệt kẹp 10–60, mật độ/cỡ lạ → mặc định) —
   toàn bộ OK. Render màn admin không một PHP warning/notice nào (đo được:
   HTML ~28KB).
3. Lưu thật qua `nntm_con_tro_xu_ly_luu()` với `$_POST` giả + nonce thật: 2
   trang test, 1 dòng trùng ID bị bỏ qua (giữ dòng đầu), 1 ID không tồn tại bị
   bỏ qua — đọc lại DB đúng như kỳ vọng.
4. `curl` trang chủ khi option trống: không một byte `con-tro.js`/`con-tro.css`
   nào. Đặt `kieu=mat-troi`: trang chủ có cả hai + cấu hình inline đúng. Trang
   có meta riêng `tat`: không nạp gì dù cài đặt chung đang bật. Cuối cùng trả
   option về trống — xác nhận lại bằng curl lần nữa.
5. Trang đọc sách: plugin `nntm-library` (cung cấp `nntm_dang_o_trang_doc()`)
   **không được cài ở môi trường dev local này** (`active_plugins` không có),
   nên không curl thật được URL `/doc/`. Đã kiểm LOGIC riêng bằng cách mô
   phỏng hàm đó (`function nntm_dang_o_trang_doc(){return true;}` trong một
   script test): không có meta riêng → hiệu ứng bị tắt bởi filter; có meta
   riêng chọn kiểu cụ thể → vẫn bật. Cả hai đúng như spec.
6. Từ khoá tìm kiếm: tạo thật 1 Từ khoá động (`nntm_tu_khoa_dong`) "Hoa sen",
   gắn kiểu `hoa-sen` + 2 cách viết khác — khớp nguyên văn, khớp khi từ khoá
   nằm trong câu dài hơn, không khớp câu không liên quan — đều đúng. Trang
   tìm kiếm thật (`WP_Query` với `is_search`): `nguon` trả về đúng `tu-khoa` /
   `chung`. `curl /?s=hoa` (đã gắn thật `hoa-sen` cho từ khoá "Hoa sen"): có
   `con-tro.js`/`.css` + `"kieu":"hoa-sen"` dù cài đặt chung đang "Không dùng".
   `curl /?s=xyz`: không nạp gì. Đã xoá từ khoá test + xác nhận lại bằng curl.
7. Bộ ca thử khớp từ khoá (8 câu: tiền tố, nguyên từ không khớp giữa từ, có/
   không dấu, nằm trọn trong câu dài, cách viết khác, không khớp gì, tiền tố
   ngắn) chạy qua CẢ HAI bản PHP thật và JS thật: 8/8 khớp ở cả hai.
8. Hình ảnh THẬT bằng Chrome headless qua CDP (WebSocket thô, không thư viện
   ngoài): giả lập `matchMedia` trong một trang harness (vì headless mặc định
   báo `hover:none`) để lái chuột thật qua `Input.dispatchMouseEvent` theo
   đường cong 40 bước, cho cả 20 kiểu × 2 nền (tối `#2b2a24`, kem `#F7F1DE`) +
   3 kiểu với màu tuỳ chỉnh `#1F4E79` = 43 ảnh, ghép thành
   `scratchpad/con-tro/luoi-toi.png` / `luoi-kem.png`. Phát hiện qua ảnh:
   `trang-khuyet` gần như vô hình trên nền kem (fill trắng ngà ≈ nền) → thêm
   viền tối rõ hơn; `vien-tron-thien`/`net-muc`/`khoi-huong`/`gon-nuoc` mờ
   trên nền tối → thêm cơ chế tự thích nghi nền (mục ở trên) rồi chụp lại,
   xác nhận rõ hẳn. Cũng chụp riêng `sao-choi-giam-chuyen-dong.png` (chỉ còn
   1 chấm, không vệt/hạt) xác nhận `prefers-reduced-motion`.
9. Màn admin: kết xuất HTML thật của `nntm_con_tro_trang_admin()` (không đăng
   nhập bằng mật khẩu — gọi thẳng hàm PHP), bọc CSS/JS thật của wp-admin từ
   site (jquery, wp-color-picker, dashicons...), chụp bằng CDP →
   `scratchpad/con-tro/man-admin-full.png` (2362px cao) — thấy đủ 4 phần,
   lưới 21 thẻ, hai bảng "Theo từng trang"/"Theo từ khoá tìm kiếm" với dữ
   liệu thật (4 từ khoá sản xuất đang có sẵn).
10. Hiệu năng (CDP `Performance.getMetrics` + đếm `requestAnimationFrame`):
    kiểu nặng nhất (`chuoi-hat`, mật độ Nhiều) di chuột liên tục 3 giây → 192–
    197 khung hình / ~3.1–3.3 giây ≈ **60 khung hình/giây ổn định**, không bị
    tụt khung.
11. Chrome headless đã tắt khi xong. ⚠️ Lần kiểm này dùng `taskkill /IM chrome.exe /F`
    — lệnh đó đóng CẢ Chrome thật của người dùng; lần sau chỉ tắt tiến trình có
    `--user-data-dir` của phiên thử. Toàn bộ post/meta/option thử đã
    xoá — xác nhận lại bằng truy vấn DB (0 dòng meta con-tro còn sót) và curl
    trang chủ (0 byte liên quan).

## Sửa sau khi rà (26/09/2026)

- **Gõ tìm không đổi con trỏ ở trang không có hiệu ứng**: hàm nạp asset thoát sớm khi
  trang không có hiệu ứng nên bản đồ gõ tìm không bao giờ được in. Giờ bản đồ được nạp
  ở mọi trang khi có ít nhất 1 từ khoá gắn hiệu ứng (engine vẫn chỉ nạp khi gõ trúng);
  trừ trang tự chọn "Không dùng ở trang này" và trang bị filter `nntm_con_tro_bat` tắt
  (cờ `cho_go`). Kiểm curl: `/`, `/?s=xyz`, bài viết → có bản đồ, không engine;
  `/lien-he/` đặt "tat" → không nạp gì; `/?s=hoa` → engine + `hoa-sen`.
- URL nạp lười của engine/CSS kèm `?ver=` (trước không có → dễ dính cache cũ).

## Chưa kiểm / điểm không chắc

- **Nạp lười engine khi gõ trên trang không có hiệu ứng** chưa chạy trong trình duyệt
  thật (mới kiểm HTML trả về và đọc code).

- **Trang đọc sách qua curl thật**: không thể vì `nntm-library` chưa cài ở
  máy này (chỉ kiểm logic mô phỏng, xem mục 5). Cần BQT tự thử lại trên site
  thật (nntm.com có cài plugin đó).
- **Màn admin qua đăng nhập thật + click chuột thật** (thay vì kết xuất HTML
  tĩnh): không đăng nhập bằng mật khẩu theo đúng giới hạn — chỉ xác nhận bố
  cục/PHP render, không xác nhận từng nút bấm chuột thật trong trình duyệt có
  đăng nhập. Các hành vi JS (thêm dòng, xoá dòng, tìm REST, đổi màu) mới kiểm
  qua đọc code + `node --check`, chưa chạy tay trong wp-admin thật.
- **Tự thích nghi theo nền**: kiểm bằng nền phẳng (div `background-color`
  đơn sắc) trong khung harness — CHƯA thử trên ảnh nền thật (banner, ảnh bìa
  ấn phẩm...) hay gradient phức tạp trên site thật.
- **Chọn màu riêng cho từng dòng "Theo từng trang"/"Theo từ khoá tìm kiếm"
  qua chính wp-color-picker trong trình duyệt thật**: chưa thử tay (chỉ xác
  nhận `.wpColorPicker()` được gọi trên đúng các ô mới sinh qua đọc code).
- Alpha/độ đậm của từng hạt theo từng kiểu (20 hàm `veHat` khác nhau) chưa
  chỉnh riêng khi tăng cỡ — chỉ tăng KÍCH THƯỚC hạt đồng loạt qua một phép
  biến đổi `ctx.scale()` quanh tâm mỗi hạt, không sửa từng con số alpha bên
  trong 20 hàm. Nhìn ảnh chụp thấy đã đậm/rõ hơn nhiều nên coi là đủ, nhưng
  chưa tinh chỉnh riêng từng kiểu.
- Component test giữa JS thật (`con-tro-tim-kiem.js`) và bản sao trong
  `ca-thu-runner.mjs`: bản sao được chép nguyên văn cùng lúc viết, KHÔNG phải
  import trực tiếp từ file gốc (file gốc bọc IIFE, không export hàm ra
  ngoài để Node `require`) — nếu sau này sửa thuật toán trong
  `con-tro-tim-kiem.js` mà quên sửa `ca-thu-runner.mjs`, bộ kiểm sẽ không bắt
  được sai lệch. Nên cân nhắc tách phần thuật toán thuần ra một module dùng
  chung được cả trình duyệt lẫn Node `require`/`import` trực tiếp.
