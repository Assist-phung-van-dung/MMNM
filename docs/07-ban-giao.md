# Bàn giao phiên làm việc — 10/08/2026

Tài liệu này để mở đầu phiên chat mới. Đọc file này trước, rồi đọc `04-kien-truc.md`.

*Bản trước: 07/08/2026.*

---

## 1. Nguồn sự thật

| Thứ | Ở đâu |
|---|---|
| Yêu cầu chức năng | `docs/03-chot-tu-khao-sat.md` (40 câu khảo sát đã chốt) |
| Phạm vi & tiền | `docs/Bao_Gia_Chi_Tiet_320tr_Theo_Khao_Sat.docx`, tóm tắt ở `02-bao-gia-va-pham-vi.md` |
| Kiến trúc kỹ thuật | `docs/04-kien-truc.md` — **mọi code phải bám file này** |
| Font | `docs/05-font-thay-the.md` |
| Chạy local | `docs/06-chay-local.md` |

### ⚠️ Figma — điểm quan trọng nhất

File Figma có **ba trang**:

```
DESKTOP - R1                      4231:851   (bản cũ nhất, bỏ qua)
DESKTOP - R3                      6134:2075  (bản cũ)
DESKTOP - R4 (UPDATED 6AUG)       6376:6205  ← BẢN CHÍNH THỨC
```

**Chỉ dùng R4.**

Node R4 hay dùng:
- Trang chủ `6376:6322` (1366×5734) · Header `6376:7302` · Footer `6376:7384`
- Diệu Thượng `6376:6694` · Pháp Toà `6376:6727` · Liên Đàn `6376:6744`
- Hoa Khai `6376:6603` · Vườn Xoài `6386:5177` · Nhập Pháp Giới `6376:6488`
- Viên phân mục `6376:6351` · Login `6497:7151`

**Figma API chặn tốc độ (429) ở endpoint đọc node.** Giới hạn tính theo *file và tài khoản* — đổi token cùng tài khoản **không gỡ được** (đã thử 3 token ngày 10/08). Endpoint xuất ảnh thì không bị chặn.

**Cách hiệu quả nhất trong thực tế:** nhờ anh Úy mở **Dev Mode** rồi dán CSS của từng lớp vào chat. Phiên 10/08 sửa đúng ngay lần đầu ở mọi chỗ có CSS thật, và sai ở mọi chỗ tự suy từ ảnh chụp.

**Số đo R4 homepage đã bóc được** (toạ độ thiết kế, khung 1366×5734):

| y | Khối | Cao | Nền |
|---|---|---|---|
| 0 | Hero slider | 768 | ảnh |
| 768 | Mosaic "Chúng sanh tranh đấu…" | 834 | `#F7F1DE` |
| 1602 | Bài nổi bật toàn văn | 1097 | trắng |
| 2699 | Băng video "Gót Son" | 544 | đen |
| 3243 | The Drum of the True Dharma | 254 | trắng |
| 3497 | ENGINEERING EARTH | 418 | đen |
| 3915 | Hoạt động – Sự kiện | 986 | trắng |
| 4901 | GITA CENTER | 666 | `#FB5102` |
| 5567 | Footer | 168 | `#4F4F4F` |

---

## 2. Đã làm được trong phiên 10/08/2026

### Môi trường
Chuyển từ XAMPP sang **Docker**, PHP lên **8.3** — hết nợ cam kết báo giá (trước là 8.1). Database nạp sẵn, đổi URL sang cổng mới. Chi tiết cách chạy: `06-chay-local.md`.

### Trang chủ R4

| Chỗ | Đã sửa gì |
|---|---|
| Hero | `aspect-ratio` `1366/648` → **`1366/768`**. Số 648 trước đó đo từ ảnh mẫu `hero-1.png` chứ không từ thiết kế — **hụt 120px** |
| Thẻ nhỏ hero | Hộp đen đặc bo góc 20 → **kính mờ** `rgba(247,241,222,.2)` + `blur(5px)`, rộng 388, lề 20/29, bo góc 0. Toàn bộ số cũ là "ước lượng từ ảnh" |
| Viên phân mục | Bỏ dạng viên thuốc → bo góc 0, viền `#B4B7A7`, chữ `#F7F1DE`, cao 45 |
| GITA CENTER | Nền cam **`#FB5102`**, và thêm tuỳ chọn `background` (không nền / kem / cam / tối) cho `nntm/card-list` — trước đây block băng cuộn không có cách nào đổi màu nền |
| Mosaic SECTION 1 | Nền `#F7F1DE` khai rõ (trước ăn theo `body`) · gap hàng nhỏ 20→**65** · bỏ tiêu đề khối · bỏ nhãn chuyên mục và ngày cập nhật · bỏ link ở tiêu đề thẻ vừa/nhỏ · bỏ bo góc mọi ảnh · thêm nút "Xem Tất cả" · hàng ảnh nhỏ xê lên. Chiều cao từ 1148 → **873** (Figma 834) |
| Ảnh | 8 ảnh thật của anh Úy vào `uploads/`, có tiêu đề và alt tiếng Việt, thay ảnh xám mẫu |

### Đầu trang

| Chỗ | Đã sửa gì |
|---|---|
| **Menu chính** | Tìm ra **gốc lỗi ảnh hưởng toàn site**: `base.css` có `li + li { margin-top }` dùng selector trần nên dính vào mọi danh sách — menu bị đẩy 8px từ mục thứ hai, `nav` cao 37px thay vì 29px, mục đầu trồi lên 4px. Đã khoanh về `ul:not([class]) > li + li`. Chân trang và mọi danh sách có class trước đó cũng dính 8px thừa mà không ai để ý |
| Màu menu | `inherit` → `#4F4F4F` theo Figma |
| Khu phải | `flex: 0 1 auto` → **`1 1 auto`** + `justify-content: flex-end` + cao 46 |
| Nút ngôn ngữ | Cặp VI/EN → **một nút**, suy theo `get_locale()`. Chốt được điểm 1 trong "ba điểm đầu trang chờ xác nhận" |
| Logo | Căn giữa hai dòng |
| Bo góc | Bỏ `border-radius` của `.nntm-header` |
| Màn > 1366 | Thanh giãn tới 1920 rồi chặn trần, đệm ngang theo tỷ lệ 1,46%. Không đụng cỡ chữ |

### Block mới và công cụ
- Block **`nntm/banner`** — băng chuyền ảnh lớn, tự chạy, dãy chấm bấm được bằng bàn phím, khách tự thêm/xoá/sắp lại tấm
- **`tools/figma-node.mjs`** — đọc một node Figma ra cây bố cục kèm số đo; có chế độ đọc lại từ JSON đã lưu (không gọi API) và chế độ chỉ xuất ảnh (không bị 429)
- **`tools/png-do.mjs`** — đọc PNG thuần, quét cột/hàng ra ranh giới và chiều cao từng khối, lấy màu tại điểm. Dùng khi Figma API bị chặn. Độ tin cậy đã kiểm chứng: quét dọc R4 homepage ra tổng chiều cao khớp đúng 5734 như Figma khai

### Bản R1 — đã thử rồi bỏ
Anh Úy đổi hướng sang R1, đã dựng đủ trang tại `/r1` (block `nntm/banner`, `page-r1.php`, 5 template part, `assets/css/pages/r1.css`, `assets/js/r1.js`), rồi quay lại R4.

Trang `r1` (ID 111) **đã chuyển nháp, không xoá file nào** — dự án không phải git repo nên xoá là mất hẳn. `page-r1.php` chỉ kích hoạt khi có trang slug `r1`; CSS/JS chỉ nạp khi `is_page('r1')`. Nằm im, không tốn byte nào. Muốn xem lại thì đăng trang đó lên.

---

## 3. Đang có gì

### Plugin `nntm-core` (dữ liệu — không phụ thuộc theme)
Không đổi so với bản 07/08: 7 CPT, 3 taxonomy, 2 vai trò, 5 bảng riêng, term meta ảnh + thứ tự, post meta, `nntm_sort_terms_by_order()`.

### Theme `nntm` — 12 block
`card` · `card-list` · `cta` · `paging` · `feature` · `tru-xu-list` · `term-list` · `article-rows` · `article-mosaic` · `thien-duong` · `hero-slider` · **`banner`**

Đầu trang và chân trang là **PHP template**, không phải block.

### Trang đã dựng

| Trang | Theo bản | Tình trạng |
|---|---|---|
| Trang chủ | **R4** | ✅ |
| Chân trang | **R4** ✅ | |
| Đầu trang | **R4** ✅ |  |
| Diệu Thượng · Pháp Tòa · Nguyên Thuỷ · Hoa Khai · Liên Đàn | R3 ⚠️ | cần đối chiếu R4 |
| Vườn Xoài · Nhập Pháp Giới | — | chưa dựng |
| `r1` (ID 111) | R1 | đã chuyển nháp |

### Script tạo dữ liệu mẫu (chạy lại được nhiều lần)
```
tools/seed-menu.php · seed-trang-chu.php · seed-dieu-thuong.php
seed-phap-toa.php · seed-hoa-khai.php · seed-lien-dan.php
tools/figma-sync.mjs · figma-node.mjs · png-do.mjs
```

⚠️ `seed-menu.php` chạy lại sẽ **sinh ID menu mới** — đừng viết CSS/PHP trỏ vào `#menu-item-<id>`.

---

## 4. Việc cần làm tiếp

### 🔴 Thứ tự bài trong `nntm/article-mosaic` không ổn định
Đặt `orderBy: manual` + `manualOrderIds: "28,29,30,31,32,33"` mà tải 3 lần thì lần 3 vẫn xoay 2 vị trí. Code đã thêm ở `render.php` case `'manual'` (`post__in` + `orderby = 'post__in'`) nhưng chưa ăn — cần dump `$query_args` và `$query->request` xem SQL thật.

**Gốc rễ:** 6 bài trong category 13 có **ngày đăng trùng nhau** nên `orderby=date` không quyết định được thứ tự. Đặt ngày lệch nhau vài phút có thể đơn giản hơn `post__in`.

### 🟠 SECTION 1 còn lệch 39px
Cao 873 so với Figma 834. Nghi ở **đoạn trích thẻ cột trái** — trong Figma thẻ đó chỉ có ảnh + tiêu đề. Tắt `showExcerpt` thì về ~828 (lệch 6px). **Phải hỏi anh Úy trước** — anh ấy từng nói bỏ nhầm.

### 🟠 Header phải đè lên hero
Figma cho banner `top: 0`, tức đầu trang nằm **đè lên** ảnh hero. Hiện header là dải riêng cao 68px đẩy hero xuống → tổng 836 thay vì 768. Sửa `header.php` + `assets/css/header.css`, **ảnh hưởng toàn site nên phải hỏi trước**.

### 🟠 Trang chủ R4 còn thiếu hai khối
- **Bài nổi bật toàn văn** (y=1602, cao 1097): dấu ❝, tiêu đề, nguồn in nghiêng, thân bài trong hộp kem, ảnh phải
- **ENGINEERING EARTH** (y=3497, cao 418): nền đen, ảnh vũ trụ trái, tiêu đề + video phải

### 🟡 Ba lớp chữ trong thẻ nhỏ hero
`.nntm-hero-slider__sidecard` — heading 19,4px/700, text 15,1px italic, cta 16,2px/700. Cả ba là số "ước lượng từ ảnh", chưa lấy từ Figma. Cần CSS thật.

### 🟡 Ảnh mẫu còn sót
Tấm thứ 3 của `hero-slider` và `nntm/banner` vẫn dùng `hero-3.png` — ảnh xám phẳng do script seed sinh.

### 🟡 Chờ khách trả lời
- **"Đại Thừa" hay "Thiền Tông"?** Figma R4 ghi *Thiền Tông*, còn database và `04-kien-truc.md` mục 10 ghi *Đại Thừa*. Dải viên phân mục sinh từ dữ liệu nên **sửa CSS không đổi được chữ**. Cần chốt bằng văn bản.
- **Thiền Đường và Thư Viện PDF**: chỉ cần đăng nhập, hay phải là Đại Sĩ / Kim Cương? Có sẵn filter `nntm_thien_duong_can_access`.
- **Câu 16**: tìm bằng ảnh ngay ngày ra mắt hay Phase 2?
- **Câu 19, 21**: số lượng PDF và bài audio → chưa chốt được gói VPS/CDN.
- **Font Battambang**: có mua font Việt hoá dáng tương tự không? Bản thay Be Vietnam Pro làm bề rộng chữ lệch Figma ~2% (đo trên menu chính: 611 so với 624).

### 🟡 Đầu trang — hai điểm còn treo
Điểm 1 (nút ngôn ngữ) **đã chốt và đã sửa**. Còn:
2. Viên "Nhập Pháp Giới": R4 để nó là mục cuối và chỉ thành nút khi **đã đăng nhập**. Hiện dựng theo hướng "mục đang xem" — có thể sai.
3. Bản dính khi cuộn: nền tối thật hay chỉ là nền đen của khung Figma lộ ra?

### 🟡 Hạng mục lớn chưa động tới
Thư Viện PDF (30tr, nặng nhất Phase 1) · Pháp Thoại + Soketi · Hệ thống thành viên + đăng nhập Google/Facebook · Tìm kiếm không dấu + autocomplete · Song ngữ Polylang · Bảo mật & golive

---

## 5. Bài học rút ra

Sáu bài học của bản 07/08 vẫn còn giá trị, **giữ nguyên**: agent chết giữa chừng để lại rác · đệm chồng đệm · `render.php` nạp bằng `require` nên hàm dùng chung phải để trong `inc/` · đừng truyền `meta_key` vào `get_terms()` · logic dữ liệu gom về plugin · đo trong trình duyệt đừng tin code.

Bốn bài học mới của phiên 10/08:

**Đừng suy kích thước từ ảnh chụp.** Hero sai 120px vì `aspect-ratio` lấy từ ảnh mẫu chứ không từ thiết kế. Thẻ nhỏ hero, nền, lề, bo góc — tất cả đều sai vì cùng một gốc "ước lượng từ ảnh". Chỗ nào comment ghi *"do tu anh"* thì nên nghi ngờ và đối chiếu lại Figma.

**Phóng cả bản thiết kế thì phải phóng ĐỒNG THỜI ba nhóm token.** Đã thử đổi `font-size` gốc để scale theo màn hình rộng và **làm vỡ bố cục**: chữ phồng 1,4 lần trong khi `--nntm-sp-*` và `--nntm-r-*` là px cứng không giãn theo → logo bị cắt, chữ tràn khung. Cảnh báo đã ghi thẳng trong `tokens.css`. Làm nửa vời là vỡ.

**Thêm thuộc tính vào `block.json` thì PHẢI thêm ô điều khiển trong `editor.js`.** Đã sót đúng lỗi này với `viewAllLabel`/`viewAllUrl`: nút hiện trên trang mà trong trình soạn thảo không có chỗ nào sửa, buộc phải gõ lệnh WP-CLI. Vi phạm ràng buộc mạnh nhất của dự án (`04-kien-truc.md` mục 2). Cũng nhân đây phát hiện `orderBy: manual` khai trong `block.json` từ trước mà `render.php` **chưa từng đọc** — ô "thứ tự thủ công" bấm vào không có tác dụng gì.

**Vá triệu chứng thì che mất nguyên nhân.** Mục menu đầu lệch 4px, hướng đầu tiên là thêm `padding-top` bù vào. Anh Úy bảo làm ngược lại — bỏ đệm ở các mục kia — và đó mới là đường dẫn tới `li + li` trong `base.css`, một lỗi ảnh hưởng toàn site. Khi thấy mình đang bù trừ cho một con số lẻ, hãy hỏi vì sao con số đó tồn tại.
