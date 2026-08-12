# Kiến trúc kỹ thuật — Nẵng Nhân Tịch Mặc

Quyết định chốt ngày 06/08/2026. Mọi code phải bám tài liệu này.

---

## 0. Ba quyết định của anh Úy (06/08/2026)

1. **Figma thiếu màn → không chờ.** Dựng các màn còn thiếu bằng design token lấy từ Figma. Khi khách gửi thiết kế mới thì đọc lại từ Figma API và chỉnh cho khớp. → Mọi màn "tự dựng" phải **tách CSS riêng, không hardcode**, để thay đổi sau ít tốn công.
2. **Giữ Soketi.** Thiền Đường hiển thị *"đang có N người cùng nghe"* theo thời gian thực. Đây là vượt trên phiếu khảo sát câu 24 — coi là điểm cộng tặng khách, không tính thêm tiền.
3. **Admin phải sửa được trang, không cần lập trình viên.** Đây là ràng buộc kiến trúc mạnh nhất, chi phối toàn bộ mục 2 và 3 bên dưới.

---

## 1. Chia tầng: cái gì ở plugin, cái gì ở theme

Nguyên tắc: **dữ liệu và nghiệp vụ ở plugin, hình ảnh ở theme.** Đổi theme sau này không được mất bài viết, thành viên, ghi chú, KPI.

```
wp-content/
  plugins/
    nntm-core/        ← CPT, taxonomy, role, quyền, cài đặt chung   [Phase 1]
    nntm-library/     ← PDF: bảo vệ, watermark, ghi chú, bookmark   [Phase 1]
    nntm-audio/       ← Pháp Thoại + Thiền Đường + Soketi presence  [Phase 1]
    nntm-search/      ← không dấu, autocomplete; sau cắm ảnh + OCR  [P1 → P2]
    nntm-congtu/      ← KPI, khai báo công phu, BXH                 [Phase 2]
    nntm-community/   ← diễn đàn có kiểm duyệt                      [Phase 3]
  themes/
    nntm/             ← theme.json, block, pattern, template, CSS/JS
```

Lý do tách `nntm-library` khỏi core: logic chặn tải + watermark là phần dễ phải sửa nhất khi có phản hồi thực tế; tách ra thì sửa không đụng vào phần còn lại.

---

## 2. Làm sao khách tự sửa trang được — quyết định quan trọng nhất

### Chọn Gutenberg block tự viết, KHÔNG dùng page builder mua ngoài

| Phương án | Vì sao loại / chọn |
|---|---|
| Elementor / WPBakery | ❌ Trả phí hằng năm, HTML phình, tốc độ kém, khóa chân khách vào một hãng thứ ba |
| ACF Pro Flexible Content | ❌ Tốn giấy phép; khách sửa trong form chứ không thấy trước được kết quả |
| **Gutenberg block tự viết + theme.json** | ✅ Có sẵn trong WordPress, miễn phí, khách kéo thả và **nhìn thấy đúng như trang thật**; token màu/chữ khai trong theme.json nên khách **chỉ chọn được màu thương hiệu, không phá được thiết kế** |

### Cách ánh xạ từ Figma sang block

Figma đã tổ chức sẵn theo **component set + variant**. Ánh xạ 1–1:

| Figma component set | Block | Biến thể |
|---|---|---|
| `CARD` | `nntm/card-list` | ARTICLE, SMALL, XS, DAI SI CARD, VIDEO, KHOA TU, BOOKS, ARTICLE HOVER |
| `CTA` | `nntm/cta` | Default, HOVER, GHOST, GHOST HOVER, CTA TEXT, TEXT HOVER, FAV BUTTON |
| `HEADER` | `nntm/header` (template part) | 1, WHITE, TRANS |
| `MAIN NAV` | `nntm/main-nav` | Default, V2, ACTIVE, V2 ACTIVE |
| `TRU XU CARD` | `nntm/tru-xu-card` | 1–4 |
| `PHAP TOA CARD` | `nntm/phap-toa-card` | Default, HOVER |
| `CARD DAI SI/KIM CUONG` | `nntm/rank-card` | Đại Sĩ, Kim Cương |
| `BANNER TONG CHI` | `nntm/banner` | — |
| `PAGING`, `TABS`, `BUTTON`, `FAQ`, `MENU FLOATING ITEM`, `LANG BUTTON`, `NAV_TITLE` | block tương ứng | theo variant Figma |

**Quy tắc bắt buộc:** biến thể của block phải trùng tên với variant trong Figma. Sau này khách đổi thiết kế một variant, mình sửa đúng một chỗ.

### 6 phân mục là Page, không phải template PHP

Khảo sát Figma cho thấy mỗi phân mục (`05. HOA KHAI`, `07. NHAP PHAP GIOI`...) được ghép từ các khối `SECTION 1..6`, mỗi section là một tiêu đề + một danh sách nội dung. Đó chính là mô hình block.

→ Mỗi phân mục là một **Page** trong WordPress, ghép từ block pattern dựng sẵn. Khách vào Trang → sửa → kéo section lên xuống, đổi tiêu đề, đổi nguồn bài, thêm bớt section. **Không cần lập trình viên.**

`nntm/card-list` nhận tham số: nguồn nội dung (CPT nào / taxonomy nào), số lượng, cách sắp xếp, biến thể thẻ, có phân trang hay không. Một block phục vụ mọi section.

### Cái gì vẫn nằm trong PHP template

Những màn có nghiệp vụ nặng, khách không có nhu cầu sửa bố cục: **trình đọc PDF, trình phát Pháp Thoại, Thiền Đường, đăng nhập/đăng ký, trang tài khoản, kết quả tìm kiếm, dashboard Cộng Tu.** Đây là theme lai (hybrid): block cho trang nội dung, PHP template cho trang chức năng.

---

## 3. Data model

### Custom Post Type (khai trong `nntm-core`)

| CPT | Nội dung | Ghi chú |
|---|---|---|
| `nntm_article` | Bài viết của 6 phân mục | taxonomy `nntm_section` quyết định thuộc phân mục nào |
| `nntm_publication` | Ấn phẩm PDF (BOOKS) | đính file PDF, nhạc nền tùy chọn, mục lục |
| `nntm_talk` | Pháp Thoại (audio ~1h) | file trên CDN, ký URL khi phát |
| `nntm_retreat` | Khóa Tu | ngày bắt đầu/kết thúc, địa điểm, cho đăng ký hay không |
| `nntm_abode` | Trú Xứ | ứng với `TRU XU CARD` |
| `nntm_video` | Video / phim Phật pháp | ứng với `CARD variant=VIDEO`, popup video |
| `nntm_zen_track` | Nhạc thiền cho Thiền Đường | |
| `post` (có sẵn) | Tin Tức + Hoằng Pháp | dùng category, không đẻ thêm CPT |

### Taxonomy

| Taxonomy | Gắn vào | Giá trị |
|---|---|---|
| `nntm_section` | `nntm_article` | Diệu Thượng, Pháp Tòa, Liên Đàn, Hoa Khai, Vườn Xoài, Nhập Pháp Giới |
| `nntm_topic` | article, publication, talk | chủ đề tự do |
| `nntm_series` | talk, video | bộ / series nhiều tập |

### Bảng riêng (không nhét vào `wp_postmeta`)

Những thứ ghi rất nhiều lượt, nhét vào postmeta sẽ làm chậm cả site:

| Bảng | Dùng cho |
|---|---|
| `nntm_reading_progress` | vị trí đang đọc PDF / đang nghe audio (user_id, object_id, position, updated_at) |
| `nntm_notes` | ghi chú cá nhân theo trang PDF |
| `nntm_favorites` | yêu thích |
| `nntm_kpi_log` | khai báo công phu (Phase 2) |
| `nntm_retreat_signup` | đăng ký khóa tu |

### Vai trò thành viên

| Role | Cách lên cấp | Quyền |
|---|---|---|
| `subscriber` | tự đăng ký | xem nội dung công khai |
| `nntm_dai_si` | **BQT nâng thủ công** | giao diện Đại Sĩ, thư viện PDF, Thiền Đường, Cộng Tu |
| `nntm_kim_cuong` | **BQT nâng thủ công** | như trên + nội dung dành riêng Kim Cương |

Giao diện tự đổi theo cấp bằng cách gắn class gốc (`is-dai-si` / `is-kim-cuong`) lên `<body>` và đảo biến CSS — **không nhân đôi template**. Figma cho thấy hai bộ khác nhau chủ yếu ở màu và trang trí, không khác cấu trúc.

---

## 4. Bảo vệ PDF — chốt cách làm

Ba lớp, không lớp nào tự nó đủ:

1. **File PDF gốc không bao giờ lộ URL.** Nằm ngoài thư mục web, phục vụ qua một endpoint PHP kiểm tra đăng nhập và cấp phép.
2. **Trình đọc PDF.js tùy biến**, gỡ hết nút tải/in, chặn chuột phải và tổ hợp phím sao chép.
3. **Watermark tên tài khoản** vẽ đè lên canvas ngay trong trình duyệt, chéo góc, mờ, lặp lại — kèm ID phiên đọc.

**Vì sao vẽ watermark ở trình duyệt chứ không đóng dấu sẵn vào file trên máy chủ:** đóng dấu ở máy chủ thì mỗi người đọc sinh ra một bản PDF riêng, CDN không cache được gì, CPU tăng vọt — với sách vài trăm trang là không chịu nổi trên VPS 4 nhân. Vẽ ở trình duyệt thì file gốc dùng chung, cache tốt, máy chủ nhẹ.

**Nói thẳng giới hạn:** người có kỹ thuật vẫn gỡ được lớp watermark bằng công cụ nhà phát triển. Không có công nghệ web nào chặn tuyệt đối — báo giá mục 9 đã ghi rõ điều này với khách, đây là mức răn đe cao nhất khả thi.

*(Nếu sau này khách muốn mức cao hơn, phương án là đóng dấu ở máy chủ cho riêng nhóm tài liệu mật + hàng đợi xử lý nền. Ngoài phạm vi báo giá hiện tại.)*

---

## 5. Thiền Đường realtime (anh Úy yêu cầu giữ)

- Soketi tự vận hành trên VPS, tương thích giao thức Pusher.
- Kênh presence `presence-thien-duong`. Vào trang thì tham gia kênh, rời trang thì tự rớt.
- Hiển thị: **"Đang có N người cùng nghe"** + danh sách pháp danh. Không lộ email, không lộ ảnh thật trừ khi thành viên đã đặt ảnh đại diện.
- Cấp phép kênh qua endpoint PHP kiểm tra đăng nhập — người chưa đăng nhập không vào được kênh.
- Chi phí bộ nhớ: Soketi rảnh chiếm ~80–150MB. Đã tính trong VPS 8GB, nhưng khi triển khai Qdrant ở Phase 2 thì phải xem lại tổng thể.

---

## 6. Chống lấy trộm audio

File audio nằm trên CDN, phục vụ qua **URL có chữ ký hết hạn sau 15 phút**, ký theo phiên đăng nhập. Người dùng sao chép link gửi cho người khác thì link đã chết. Kèm chặn `Range` request bất thường để hạn chế công cụ tải hàng loạt.

---

## 7. Song ngữ

Polylang bản miễn phí đủ cho Phase 1 (khách chưa có bản dịch nào — khảo sát câu 6). Dựng sẵn hạ tầng: mọi CPT và taxonomy đăng ký kèm khai báo đa ngữ, chuỗi trong theme bọc hàm dịch ngay từ đầu. Bổ sung nội dung tiếng Anh sau **không phát sinh chi phí** đúng như báo giá cam kết.

---

## 8. Môi trường

| | Local | Staging | Production |
|---|---|---|---|
| PHP | **8.1 (XAMPP hiện tại)** | 8.3 | 8.3 |
| CSDL | MariaDB 10.4 | MySQL 8 | MySQL 8 |
| Máy chủ web | Apache | Nginx | Nginx |

⚠️ **Chênh lệch cần xử lý:** báo giá cam kết PHP 8.3 nhưng XAMPP trên máy đang là 8.1. Code sẽ viết tương thích 8.1+ để chạy được ở local, nhưng **staging và production bắt buộc 8.3**, và phải kiểm thử lại trên 8.3 trước golive. Nên nâng XAMPP local lên 8.3 để tránh lỗi chỉ xuất hiện ở production.

---

## 9. Quy ước code

- Tiền tố: hàm `nntm_`, hằng `NNTM_`, block `nntm/`, CSS `.nntm-`.
- Mọi chuỗi hiển thị bọc `__()` / `esc_html__()` với text domain `nntm`.
- Escape mọi đầu ra, `prepare()` mọi câu truy vấn, kiểm tra nonce + quyền ở mọi endpoint.
- Không đụng vào lõi WordPress, không sửa file plugin bên thứ ba.
- CSS dùng biến từ `tokens.css` — **cấm viết mã màu trực tiếp trong file component**, để khi Figma đổi màu chỉ sửa một chỗ.
- Màn tự dựng (chưa có Figma) đặt trong `assets/css/pages/_provisional/` để sau này dễ tìm và thay.

---

## 10. Phân cấp trong phân mục — quyết định 06/08/2026 (từ trang Pháp Tòa)

### Vấn đề

Trang `03. PHAP TOA` trong Figma không liệt kê bài viết, mà liệt kê **4 truyền thống**: Nguyên Thuỷ, Đại Thừa, Tịnh Độ, Mật Tông. Mỗi truyền thống dẫn sang một trang riêng (`03. PHAP TOA - NGUYEN THUY`) chứa danh sách bài viết xếp so le trái–phải.

Tức là giữa "phân mục" và "bài viết" còn **một tầng nữa**.

### Ba cách và lý do chọn

| Cách | Đánh giá |
|---|---|
| Tạo CPT `nntm_tradition` | ❌ Đẻ thêm loại nội dung chỉ để chứa 4 mục. Sau này Hoa Khai, Vườn Xoài cũng có tầng con thì lại đẻ tiếp |
| Tạo taxonomy `nntm_tradition` riêng | ❌ Giải quyết được Pháp Tòa nhưng không tái dùng cho phân mục khác |
| **Dùng term con của `nntm_section`** | ✅ `nntm_section` vốn đã phân cấp. Pháp Tòa là term cha, 4 truyền thống là term con. Không thêm gì mới |

### Cấu trúc chốt

```
nntm_section (phân cấp)
├── Diệu Thượng
├── Pháp Tòa
│   ├── Nguyên Thuỷ
│   ├── Đại Thừa
│   ├── Tịnh Độ
│   └── Mật Tông
├── Liên Đàn
├── Hoa Khai
├── Vườn Xoài
└── Nhập Pháp Giới
```

Mỗi term mang: `name` (tên hiển thị), `description` (mô tả trên thẻ), term meta `_nntm_term_image` (ảnh nền thẻ, nhập trong màn hình sửa chuyên mục).

### Hai block sinh ra từ đây, đều tái dùng được

| Block | Việc | Dùng lại ở đâu |
|---|---|---|
| `nntm/term-list` | Liệt kê term con của một term cha, dạng thẻ ảnh cao 280×467 | Mọi phân mục có tầng con |
| `nntm/article-rows` | Danh sách bài xếp so le, ảnh 534×358 đảo bên từng hàng | Mọi trang chi tiết |

**Vì sao quan trọng:** ban quản trị muốn thêm truyền thống thứ 5 thì chỉ cần **thêm một chuyên mục con và tải ảnh lên** — trang Pháp Tòa tự hiện thêm thẻ. Không sửa code, không gọi lập trình viên. Đúng yêu cầu đặt ra ngày 06/08/2026.

### Ghi chú khi dựng

- `LIST` trong Figma rộng 1273 trong khung 1184 → thẻ cuối **cố ý ló ra mép phải** để gợi ý còn nội dung. Chỉ ló khi thật sự tràn.
- Hàng so le đảo bằng CSS `order`, **không đảo thứ tự trong HTML** — để người dùng bàn phím và trình đọc màn hình luôn gặp ảnh trước, chữ sau ở mọi hàng.

---

## 11. Canh lề trang phân mục — sửa lỗi đệm chồng đệm (06/08/2026)

### Triệu chứng

Mọi trang phân mục hiển thị hẹp hơn Figma khoảng 200px. Khung danh sách đo được 980px trong khi Figma là 1180px.

### Nguyên nhân

Hai lớp đệm cộng dồn:

```
<main class="nntm-container">   max-width 1220 + đệm ngang 40  →  1140
  <section class="nntm-...">    đệm ngang 70 (theo Figma)      →  1000
    <div class="__content">     đệm ngang 10 (theo Figma)      →   980
```

Trong Figma, `SECTION` là **toàn chiều rộng khung 1366** và tự mang đệm ngoài. Template lại bọc thêm một container nữa, nên đệm bị tính hai lần.

### Cách sửa

`page.php` kiểm tra nội dung có block `nntm/*` hay không:

- **Có** → `<main class="nntm-main--full">`, toàn chiều rộng, nhường việc canh lề cho từng block. Đúng như Figma.
- **Không** (trang thường như Về chúng tôi, Liên hệ) → giữ `.nntm-container` như cũ.

Hàm kiểm tra: `nntm_page_uses_section_blocks()` trong `inc/setup.php`.

### Kết quả đo sau khi sửa

| Phần tử | Figma | Thực tế |
|---|---|---|
| Khung danh sách | 1180 | 1191 |
| Cột bài nổi bật | 349 | 352 |
| Cột lưới phải | 811 | 819 |
| Thẻ vừa | 370 | 372 |
| Thẻ Ấn Phẩm | 388 | 388 |

### Quy tắc rút ra cho block sau này

Block section **luôn tự mang đệm ngoài theo Figma**, không bao giờ trông chờ template canh lề hộ. Template chỉ dựng khung, không chen đệm vào giữa.
