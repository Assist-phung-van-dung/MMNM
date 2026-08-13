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

**Cập nhật 12/08/2026 — bản dùng là R5.**

File giờ **chỉ còn MỘT page**: `6376:6205` = `DESKTOP - R5 (UPDATED 11 AUG)`, sửa lần cuối
11/08/2026 14:37 UTC. Các page R1 / R3 / R4 **đã bị khách xoá** — khách cập nhật đè lên
chính node của R4, nên **mọi node id cũ vẫn còn hiệu lực**, chỉ khác là các frame nay
được gom vào 8 nhóm.

Token đang dùng: `figd_xxxxxxxxxxxxxxxxxxxxxxxxx`
File key: `QmYLYBVSRkqIbKuKzPUfUe`

#### Tám nhóm cấp một trong R5

| Node | Nhóm | Nội dung |
|---|---|---|
| `6376:6206` | 01_HOMEPAGE - VERSION 2 | 🆕 trang chủ bản 2, cao 6530 (bản 1 cao 5874) |
| `6588:10178` | DESKTOP | 23 frame: 6 phân mục, listing, bài chi tiết, khoá tu, Đại Sĩ / Kim Cương, popup |
| `6588:9644` | TEMPLATE PAGES | 🆕 template bài chi tiết, listing, trang tĩnh |
| `6588:10046` | FUNCTION PAGES | 🆕 đăng nhập, đăng ký, quên mật khẩu, gửi chuỗi trì |
| `6588:8344` | 09. KIM CUONG HANH GIA | bản rời, **khác** bản trong DESKTOP (`6376:7066`) |
| `6588:9389` | COMPONENTS | component set dùng chung |
| `6376:7183` | 01_VIEMNAONHATBAN | mẫu trang nội dung dài |
| `6588:9645` | IMG | kho ảnh nguồn |

#### Frame nội dung (nhóm DESKTOP `6588:10178`)

| Node | Frame | Cao R5 | Cao R4 (cũ) |
|---|---|---|---|
| `6376:6322` | 01_HOMEPAGE | 5874 | — |
| `6376:6694` | 02. DIEU THUONG | **1032** | 1125 |
| `6376:6727` | 03. PHAP TOA | **776** | 843 |
| `6376:6984` | 03. PHAP TOA - NGUYEN THUY | 2491 | — |
| `6376:6744` | 04. LIEN DAN | **1807** | 1932 |
| `6386:4484` | LIEN DAN - KHOA TU LISTING | 2440 | — |
| `6376:6603` | 05. HOA KHAI | **2637** | 2547 |
| `6386:5177` | 06. VUON XOAI | **2909** | 2836 |
| `6387:5535` | VUON XOAI LISTING | 2491 | — |
| `6376:6488` | 07. NHAP PHAP GIOI | 2352 | — |
| `6376:7048` | 08. DAI SI HANH GIA | 2185 | — |
| `6376:7066` | 09. KIM CUONG HANH GIA | 3861 | — |
| `6376:6874` · `6387:5673` | KHOA TU CHI TIET (2 bản) | 2305 | — |
| `6376:6956` | BAI CHI TIET | 2375 | — |
| `6376:6902` | BAI CHI TIET — ĐẠI SĨ | 2329 | — |
| `6376:6929` | BAI CHI TIET — KIM CƯƠNG | 2374 | — |
| `6588:9878` | LISTING TEMPLATE (bản sao) | 2491 | — |
| `6376:7137` · `6452:6256` | POPUP VIDEO · POPUP PHOTO | 768 | — |
| `6376:7158` | FLOATING BAR | 65 | — |
| `6376:7160` | NOI DUNG 2: THƠ | 888 | — |

**Cả 5 trang phân mục đều đổi chiều cao lần nữa ở R5** — bản dựng theo R4 vẫn phải đối chiếu lại.

#### Màn chức năng mới R5 vừa cấp (trước đây là khoảng trống F)

| Node | Frame | Kích thước |
|---|---|---|
| `6588:10221` | LOGIN | 1366×770 |
| `6588:10263` | REGISTRATION | 1366×1096 |
| `6588:10548` | FORGOT PASSWORD | 1366×770 |
| `6613:10636` | GUI CHUOI TRI | 1366×770 |
| `6588:9526` | TRANG CHI TIET (template bài) | 1366×2305 |
| `6493:6340` | LISTING TEMPLATE | 1366×2491 |
| `6588:10047` | CONTENT: ABOUT US, CHINH SACH | 1366×1804 |

**Vẫn CHƯA có thiết kế:** trình đọc PDF, trang Thư Viện PDF, player Pháp Thoại, Thiền Đường,
trang kết quả tìm kiếm, trang tài khoản, dashboard Cộng Tu, và toàn bộ mobile/tablet.

#### Component (nhóm `6588:9389`)

`HEADER 6376:7302` · `FOOTER 6376:7384` · `CTA 6376:7354` · `MENU 6376:7377` ·
`BUTTON 6376:7401` · `CARD 6376:7408` · `MENU FLOATING ITEM 6376:7576` ·
`LANG BUTTON 6376:7583` · `MENU FLOATING BAR 6376:7601` · `TRU XU CARD 6376:7602` ·
`PHAP TOA CARD 6376:7651` · `PAGING 6376:7674` · `CARD DAI SI/KIM CUONG 6376:7675` ·
`ICON 6509:7719` · `ROW 6337:4509`
🆕 R5 thêm: `TEXT INPUT 6588:10206` · `LOGGED IN 6613:10626` · `TICK 6588:10458` ·
`NOTE 6585:7887 / 7888 / 7890`

`LOGGED IN` là bằng chứng cho điểm chờ xác nhận số 2 ở mục 3 — header có trạng thái riêng
khi đã đăng nhập.

#### Cách gọi API mà không bị chặn

**Giới hạn 429 tính theo file + tài khoản, đổi token KHÔNG gỡ được.** Kinh nghiệm 12/08:

1. `GET /v1/files/{key}?depth=3` — **lấy được cả cây page → nhóm → frame trong MỘT lệnh**.
   Đây là cách rẻ nhất, dùng lệnh này trước tiên. Kết quả đã lưu ở
   `design/figma/r5-tree-depth3.json`.
2. `GET /v1/files/{key}/nodes?ids=...` — **dễ dính 429 nhất**, chỉ gọi khi cần đo chi tiết
   bên trong một frame, luôn kèm `depth`.
3. `GET /v1/images` — xuất PNG, hạn mức riêng nhưng **cũng bị chặn** sau khoảng 20 ảnh
   liên tiếp. Gộp nhiều id vào một lệnh, nghỉ giữa các đợt.

Ảnh đã xuất sẵn nằm ở `design/figma/<node-id>@1x.png` (dùng dấu `-` thay `:`).

---

## 2. Đã làm được trong phiên 10/08/2026

- WordPress **7.0.3** tiếng Việt, database `nntm_dev`, tiền tố bảng `wp_`
- PHP local **8.1** — ⚠️ báo giá cam kết **8.3**, phải nâng trước khi lên staging
- Quản trị: `nntm_admin` / `NntmDev!2026` (chỉ dùng local)
- Chạy: xem `docs/06-chay-local.md`


**Nhập lại CSDL ngày 12/08/2026** từ `nntm_dev_20260810.sql` (bản của máy Ubuntu,
siteurl `localhost:8082`). Ba việc bắt buộc khi nhập lại:
1. Dump do MariaDB 10.11 sinh, **dòng đầu `/*M!999999\- enable the sandbox mode */`
   làm client 10.4 của XAMPP báo `Unknown command '\-'`** → phải cắt bỏ dòng đầu.
2. `permalink_structure` trong dump bị Git Bash bẻ thành
   `/C:/Program%20Files/Git/%postname%/` → đặt lại `/%postname%/`.
3. Đổi `localhost:8082` → `localhost:8080` ở options, posts, guid, postmeta, termmeta,
   usermeta. Hai chuỗi **dài bằng nhau** nên `REPLACE()` thuần SQL không vỡ dữ liệu
   serialize.

### Plugin `nntm-core` (dữ liệu — không phụ thuộc theme)
Không đổi so với bản 07/08: 7 CPT, 3 taxonomy, 2 vai trò, 5 bảng riêng, term meta ảnh + thứ tự, post meta, `nntm_sort_terms_by_order()`.


### Theme `nntm` — 14 block *(cập nhật 12/08)*
`card` · `card-list` (lưới + băng cuộn tự chạy) · `cta` · `paging` · `feature` · `tru-xu-list` · `term-list` · `article-rows` · `article-mosaic` · `thien-duong` · `hero-slider` · `article-feature` · `banner` · `engineering-earth`


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


=======
### ✅ Trang chủ — đã chuẩn hoá xong 13/08/2026

Ba việc còn thiếu ghi ở đây trước kia (khối ENGINEERING EARTH, nền cam GITA,
vai trò tiêu đề "Chúng sanh tranh đấu…") **đã xong** trong hai ngày 08–10/08.

Ngày 12–13/08 chuẩn hoá theo số đo pixel thật của frame `01_HOMEPAGE`
(`6376:6322`). Số đo cuối, đo trong trình duyệt ở viewport 1366:

| Dải | Figma | Thực tế |
|---|---|---|
| header | 76 | 74 |
| hero | 692 | 684 |
| mosaic bài nổi bật | 834 | 830 |
| feature (trích dẫn) | 1097 | 1097 |
| Gót Son | 544 | 543 |
| Drum + ENGINEERING EARTH | 254 + 418 | 254 + 418 |
| Hoạt động – Sự kiện | 986 | 972 |
| GITA CENTER | 666 | 666 |
| chân trang | 127 | 132 |

**Đợt soi bố cục 13/08** (anh Úy yêu cầu "làm đúng như thiết kế Figma"), đã sửa:

- `article-mosaic`: bỏ hết liên kết "Xem thêm" (Figma không có), chữ ô nhỏ đổi sang
  **serif nâu-xám nhạt** thay vì sans in đậm, ảnh hàng trên 258 / hàng dưới 160.
- `article-feature`: cột trái **canh trên** thay vì canh giữa — tiêu đề y=160, dấu
  nháy kép y=75, ảnh phải 534×812. Khung nền kem chặn **cả sàn lẫn trần** (min 657,
  thân max 563) vì đặt mỗi sàn thì bài dài làm khối phồng lên 1214.
- `card-list`: **hai băng KHÁC NHAU** — Gót Son ảnh 348×198 trần trên nền đen + tiêu
  đề 2 dòng; GITA là **thẻ nền tối 388×360** bọc ảnh 348×196 + tiêu đề 3 dòng.
  Tên video lấy tự động qua **oEmbed YouTube** (không cần API key), cache transient
  1 tuần / hỏng thì 1 giờ; admin gõ `link | Tiêu đề` để đè tay.
- `engineering-earth`: dựng đúng hình học Figma — khung media lớn 590×298 ở x140
  y59, cột chữ phải ở x750 (tiêu đề y66, dòng nghiêng y197), **thẻ video nhỏ
  350×197 ở x885 y264 tràn xuống dưới mép dải đen 44px**.
- Chân trang: **gỡ khối "Hãy chia sẻ ý kiến"** (Figma không có), dựng lại hai lớp —
  nền trắng bọc ngoài, khối xám `#4F4F4F` thụt lề 20px, có đường kẻ mảnh.

⚠️ Bài học: **bảng đo dải ngang cho đúng chiều cao nhưng KHÔNG cho biết cấu trúc
lồng nhau.** Bản sửa 12/08 dựa vào bảng đo đã làm mất lớp nền bọc ngoài chân trang
và dựng sai hẳn khối ENGINEERING EARTH. Phải cắt ảnh Figma từng vùng ra nhìn.

Chức năng anh Úy chốt ngày 12–13/08, đã dựng:

- **Đầu trang đổi màu theo cuộn** — ở đỉnh trang có banner thì trong suốt, chữ
  trắng; cuộn quá 80px thì nền trắng, chữ `#4F4F4F`. Trang không có banner thì
  trắng ngay. Class `.nntm-header--trong` ↔ `.nntm-header--dac`,
  `assets/js/header-scroll.js`.
- **Thẻ nổi góc phải hero = bài mới nhất**, lấy từ CSDL, có link thật.
- **`article-mosaic` + `article-feature` lấy tin mới nhất từ phân mục Hoa Khai**,
  admin đổi được nguồn / số lượng / ghim bài trong trình soạn thảo. Hàm truy vấn
  nằm ở plugin (`nntm_core_get_latest_posts`).
- **Cắt tiêu đề đúng 2 dòng kèm `…`** — class dùng chung `.nntm-cat-2-dong`
  trong `assets/css/base.css`.
- **Gót Son**: băng marquee tự chạy phải→trái, rê chuột phóng nhẹ + tự phát
  video ngắn kiểu Netflix (iframe chỉ tạo sau 500ms rê chuột, gỡ khi rời chuột).
- **The Drum of the True Dharma**: 1 video lớn + 1 video chạy nền kiểu awwwards,
  bấm để đổi vùng xem.
- **Hero slider có nút mũi tên trái/phải** — Figma KHÔNG vẽ mũi tên, đây là bổ
  sung theo yêu cầu anh Úy 13/08; cỡ nút 48px là số tự chọn.

**Link YouTube hiện là LINK TẠM** `gJAbDSse5WM` (anh Úy cấp 13/08), đặt làm giá
trị mặc định trong `block.json` của `card-list` và `engineering-earth`, và điền
vào trang chủ bằng `tools/seed-video-mau-trang-chu.php`. **Khi khách gửi danh
sách link thật thì sửa script đó rồi chạy lại.**

Còn nợ kiểm chứng bằng mắt: đầu trang tự đổi màu khi cuộn, và hiệu ứng phóng to
khi rê chuột — công cụ trình duyệt tự động trong phiên không dựng khung hình nên
`scroll` / `requestAnimationFrame` / `:hover` không kích hoạt được.

### 🟡 Điểm mờ chờ khách trả lời (từ phiếu khảo sát)
- **Thiền Đường và Thư Viện PDF**: chỉ cần đăng nhập, hay phải là Đại Sĩ / Kim Cương? Phiếu khảo sát nói đăng nhập, tài liệu kiến trúc nói theo cấp. Hiện theo phiếu khảo sát, có sẵn filter `nntm_thien_duong_can_access` để đổi bằng một dòng.
- **Câu 16**: khách tích "tìm bằng ảnh ngay ngày ra mắt" nhưng báo giá xếp Phase 2 — cần xác nhận bằng văn bản.
- **Câu 19, 21**: chưa biết số lượng PDF và bài audio → chưa chốt được gói VPS/CDN.


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
