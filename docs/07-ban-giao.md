# Bàn giao phiên làm việc — 14/08/2026

Đọc file này trước tiên khi mở phiên chat mới, rồi đọc `04-kien-truc.md`.

*Bản trước: 13/08/2026. Bản này viết lại toàn bộ, đã gộp và loại các mục đã xong.*

---

## 0. Đọc theo thứ tự này

| # | File | Vì sao |
|---|---|---|
| 1 | `07-ban-giao.md` (file này) | tình trạng hiện tại, việc còn lại, bẫy đã cắn |
| 2 | `04-kien-truc.md` | **mọi code phải bám** — quyết định kiến trúc đã chốt |
| 3 | `03-chot-tu-khao-sat.md` | yêu cầu chức năng đã chốt với khách + điểm khách chưa trả lời |
| 4 | `02-bao-gia-va-pham-vi.md` | phạm vi và tiền — dùng khi cần biết việc nào thuộc Phase nào |
| 5 | `05-font-thay-the.md` · `06-chay-local.md` | font và cách chạy local |

**Trước khi viết bất kỳ dòng code nào, đọc mục 9 "Bẫy đã cắn thật" ở cuối file này.**
Mỗi bẫy trong đó đều đã làm hỏng việc ít nhất một lần.

---

## 1. Môi trường

| | Giá trị |
|---|---|
| Site local | `http://nntm.com/` (XAMPP, vhost) |
| WordPress | 7.0.3 tiếng Việt |
| CSDL | `nntm_dev`, tiền tố `wp_` |
| PHP local | **8.1** — ⚠️ báo giá cam kết **8.3**, phải nâng trước khi lên staging |
| Quản trị | `nntm_admin` / `NntmDev!2026` — **chỉ dùng local** |
| Chạy PHP CLI | `"C:/xampp8_2/php/php.exe" tools/<script>.php` |
| Plugin đang bật | `nntm-core`, `polylang`, `akismet` |

### Nếu phải nhập lại CSDL từ dump của máy Ubuntu
1. Dump do MariaDB 10.11 sinh, dòng đầu `/*M!999999\- enable the sandbox mode */` làm
   client 10.4 của XAMPP báo `Unknown command '\-'` → **cắt bỏ dòng đầu**.
2. `permalink_structure` trong dump bị Git Bash bẻ thành `/C:/Program%20Files/Git/%postname%/`
   → đặt lại `/%postname%/`.
3. Đổi host cũ sang host hiện tại ở `options`, `posts`, `guid`, `postmeta`, `termmeta`,
   `usermeta`. Nếu hai chuỗi **dài bằng nhau** thì `REPLACE()` thuần SQL không vỡ dữ liệu
   serialize; khác độ dài thì phải dùng công cụ hiểu serialize.

---

## 2. Nguồn sự thật — và chỗ nó KHÔNG còn đúng

| Thứ | Ở đâu |
|---|---|
| Yêu cầu chức năng | `03-chot-tu-khao-sat.md` (phiếu khảo sát 40 câu đã chốt) |
| Kiến trúc kỹ thuật | `04-kien-truc.md` — **ràng buộc mạnh nhất** |
| Thiết kế | Figma R5 **+ ảnh chủ dự án gửi qua chat** — xem cảnh báo ngay dưới |

### ⚠️ Ảnh chủ dự án gửi qua chat ĐÈ LÊN Figma

Từ 14/08/2026, chủ dự án gửi thiết kế **bằng ảnh chụp trong chat**, không cập nhật vào
Figma. Các màn sau dựng theo **ảnh chat**, KHÔNG theo Figma:

- Nhập Pháp Giới (Figma có `07. NHAP PHAP GIOI 6376:6488` — **không dùng**)
- Đăng nhập / Đăng ký / Quên mật khẩu (Figma có `6588:10221`, `6588:10263`, `6588:10548` — **không dùng**)
- Toàn bộ khu Đại Sĩ / Kim Cương Hành Giả
- Trang Nghi Quỹ, trang chi tiết ấn phẩm
- Khối Thống Kê + Bảng Xếp Hạng

**Hệ quả:** số đo của các màn này là **ước lượng từ ảnh chụp**, không phải số Figma.
Chỗ nào trong CSS ghi `SUY DOAN` thì đúng nghĩa đen là suy đoán.

**Ngoại lệ duy nhất có số đo thật:** màn "Tham Gia Chuỗi Trì" — Figma `6613:10636`,
khung 1366×770, ảnh đã xuất sẵn ở `design/figma/6613-10636@1x.png`.

### Figma R5

File chỉ còn **một page**: `6376:6205` = `DESKTOP - R5 (UPDATED 11 AUG)`.
Các page R1 / R3 / R4 đã bị khách xoá, nhưng khách cập nhật **đè lên chính node của R4**
nên **mọi node id cũ vẫn còn hiệu lực**.


⚠️ **Token này đang nằm trong file docs được Git theo dõi.** Trước khi repo đi ra khỏi máy
local, phải thu hồi và cấp lại token, chuyển sang biến môi trường.

#### Tám nhóm cấp một

| Node | Nhóm |
|---|---|
| `6376:6206` | 01_HOMEPAGE - VERSION 2 (cao 6530) |
| `6588:10178` | DESKTOP — 23 frame nội dung |
| `6588:9644` | TEMPLATE PAGES |
| `6588:10046` | FUNCTION PAGES |
| `6588:8344` | 09. KIM CUONG HANH GIA (bản rời, khác bản trong DESKTOP) |
| `6588:9389` | COMPONENTS |
| `6376:7183` | 01_VIEMNAONHATBAN |
| `6588:9645` | IMG |

#### Frame hay dùng

`01_HOMEPAGE 6376:6322` · `02. DIEU THUONG 6376:6694` · `03. PHAP TOA 6376:6727` ·
`03. PHAP TOA - NGUYEN THUY 6376:6984` · `04. LIEN DAN 6376:6744` ·
`05. HOA KHAI 6376:6603` · `06. VUON XOAI 6386:5177` · `08. DAI SI 6376:7048` ·
`09. KIM CUONG 6376:7066` · `BAI CHI TIET 6376:6956` · `GUI CHUOI TRI 6613:10636`

#### Component

`HEADER 6376:7302` · `FOOTER 6376:7384` · `CTA 6376:7354` · `MENU 6376:7377` ·
`BUTTON 6376:7401` · `CARD 6376:7408` · `PAGING 6376:7674` · `CARD DAI SI/KIM CUONG 6376:7675` ·
`TEXT INPUT 6588:10206` · `LOGGED IN 6613:10626` · `TICK 6588:10458`

#### Gọi Figma API mà không bị chặn

Giới hạn 429 tính theo **file + tài khoản**, đổi token KHÔNG gỡ được.
1. `GET /v1/files/{key}?depth=3` — lấy cả cây page → nhóm → frame trong MỘT lệnh. **Dùng lệnh này trước tiên.** Kết quả đã lưu ở `design/figma/r5-tree-depth3.json`.
2. `GET /v1/files/{key}/nodes?ids=...` — **dễ dính 429 nhất**, luôn kèm `depth`.
3. `GET /v1/images` — bị chặn sau khoảng 20 ảnh liên tiếp. Gộp nhiều id vào một lệnh.

Ảnh đã xuất nằm ở `design/figma/<node-id>@1x.png` (dùng dấu `-` thay `:`).

---

## 3. Kiến trúc thực tế đang chạy

### Plugin `nntm-core` — dữ liệu & nghiệp vụ

```
includes/
  class-post-types.php   8 CPT: nntm_article, nntm_publication, nntm_talk,
                         nntm_retreat, nntm_abode, nntm_video, nntm_zen_track,
                         nntm_program (mới 14/08 — Chương trình trì tụng)
  class-taxonomies.php   nntm_section (phân cấp), nntm_topic, nntm_series
  class-roles.php        nntm_dai_si, nntm_kim_cuong + nntm_user_rank()
  class-schema.php       5 bảng riêng (xem dưới)
  class-post-meta.php    post meta, gồm 5 meta của nntm_program
  class-chuoi-tri.php    NGHIỆP VỤ CỘNG TU — 11 hàm (mới 14/08)
```

Bảng riêng: `nntm_reading_progress` · `nntm_notes` · `nntm_favorites` ·
`nntm_kpi_log` (đã thêm cột `program_id`) · `nntm_retreat_signup`

### Theme `nntm` — 17 block

`article-feature` · `article-mosaic` · `article-rows` · `banner` · `card` · `card-list` ·
**`cong-tu`** · `cta` · `dieu-thuong` · `engineering-earth` · `feature` · `hero-slider` ·
`paging` · **`rank-card`** · `term-list` · `thien-duong` · `tru-xu-list`

### Theme — PHP template (màn nghiệp vụ, khách không sửa bố cục)

```
header.php · footer.php · page.php · single.php · search.php · archive.php · 404.php
single-nntm_article.php        bài chi tiết (khu Hành Giả: navy / vàng)
single-nntm_publication.php    chi tiết ấn phẩm (nền trắng, dải liên quan kem)
page-dang-nhap.php · page-dang-ky.php · page-quen-mat-khau.php
page-tham-gia-chuoi-tri.php · page-khai-bao-chuoi-tri.php
page-r1.php                    bản R1 cũ, trang đã chuyển nháp
```

### Theme — `inc/`

```
setup.php      tính năng theme, nntm_page_uses_section_blocks(), nntm_page_starts_with_hero()
enqueue.php    token → base → layout → header/footer, font Google
blocks.php     tự quét thư mục blocks/ để đăng ký
patterns.php · cleanup.php · language-switcher.php
auth.php       đăng nhập/đăng ký/quên mật khẩu + modal + nntm_login_url()
hanh-gia.php   nhận diện khu Hành Giả + CỔNG QUYỀN (xem mục 5)
an-pham.php    CSS trang ấn phẩm + nntm_an_pham_can_access() (chưa bật)
cong-tu.php    trang tham gia / khai báo chuỗi trì, xử lý POST
```

### CSS

`tokens.css` → `tokens.generated.css` → `base.css` → `layout.css` → `header/footer.css`
`assets/css/pages/`: `auth.css` · `bai-hanh-gia.css` · `an-pham.css` · `cong-tu.css` · `r1.css`

⚠️ **Cấm viết mã màu trực tiếp trong file component.** Mọi màu lấy từ `tokens.css`.

Hai màu **do chủ dự án cấp ngày 14/08/2026** (không phải ước lượng, đừng tự chỉnh):
`--nntm-vang-nghe: #D4AF37` · `--nntm-do-tham: #8B1E2D`

---

## 4. Trang & dữ liệu hiện có

| ID | Slug | Dựng theo | Tình trạng |
|---|---|---|---|
| 110 | `trang-chu` | Figma R4 | ✅ chuẩn hoá xong 13/08 |
| 15 · 24 · 25 · 46 · 66 | `dieu-thuong` `phap-toa` `nguyen-thuy` `hoa-khai` `lien-dan` | R3 | ⚠️ **cần đối chiếu lại R5** |
| 77 | `vuon-xoai` | — | ❌ chưa dựng |
| 79 | `nhap-phap-gioi` | ảnh chat 14/08 | ✅ |
| 242 | `dai-si-hanh-gia` | ảnh chat 14/08 | ✅ nền navy |
| 243 | `kim-cuong-hanh-gia` | ảnh chat 14/08 | ✅ nền vàng, 5 dải |
| 393 | `nghi-quy` | ảnh chat 14/08 | ✅ công khai, không chặn |
| 245 · 246 · 247 | `dang-nhap` `dang-ky` `quen-mat-khau` | ảnh chat 14/08 | ✅ |
| 350 · 351 | `tham-gia-chuoi-tri` `khai-bao-chuoi-tri` | Figma `6613:10636` / tự dựng | ✅ |
| 111 | `r1` | R1 | đã chuyển nháp |

### Taxonomy `nntm_section` — cấu trúc thật

```
Diệu Thượng (2) · Hoa Khai (5) · Liên Đàn (4) · Vườn Xoài (6)
Pháp Tòa (3)
├── Nguyên Thuỷ (8) · Thiền Tông (9) · Tịnh Độ (10) · Mật Tông (11)
Nhập Pháp Giới (7)
├── Đại Sĩ Hành Giả (49)     — 9 bài
└── Kim Cương Hành Giả (50)  — 26 bài
```

⚠️ Chủ dự án từng hỏi tách CPT riêng cho Nhập Pháp Giới; đã **chốt giữ term con** vì trang
Kim Cương ghép từ **bốn loại nội dung khác nhau** (bài viết + ấn phẩm + sự kiện + KPI), nên
"khu" là một cái **trang**, không phải một loại nội dung.

### Số lượng nội dung

`nntm_article` 51 · `nntm_publication` 45 · `nntm_video` 12 · `nntm_retreat` 17 ·
`nntm_abode` 4 · `nntm_zen_track` 5 · `nntm_program` 1 · `post` 14

### Script seed (chạy lại được nhiều lần)

```
seed-menu.php · seed-trang-chu.php · seed-video-mau-trang-chu.php
seed-dieu-thuong.php · seed-phap-toa.php · seed-hoa-khai.php · seed-lien-dan.php
seed-nhap-phap-gioi.php · seed-dai-si-hanh-gia.php · seed-kim-cuong-hanh-gia.php
seed-nghi-quy.php · seed-trang-dang-nhap.php · seed-cong-tu.php
setup-polylang.php · setup-bilingual-menus.php
figma-sync.mjs · figma-node.mjs · png-do.mjs
```

⚠️ `seed-menu.php` chạy lại **sinh ID menu mới** — đừng viết CSS/PHP trỏ vào `#menu-item-<id>`.

---

## 5. Cổng quyền — chỗ dễ vỡ nhất, đọc kỹ trước khi sửa

Chủ dự án chốt 14/08: **mọi thành viên đã đăng nhập** đều xem được khu Hành Giả
(không phân biệt cấp Đại Sĩ / Kim Cương).

Cài trong `inc/hanh-gia.php`, **hai tầng — chặn một tầng là hở**:

| Tầng | Hook | Chặn gì |
|---|---|---|
| 1 | `template_redirect` | Page `dai-si-hanh-gia` / `kim-cuong-hanh-gia`, kho lưu trữ term, và bài `nntm_article` thuộc khu → 302 về `/dang-nhap/?redirect_to=…` |
| 2 | `pre_get_posts` | loại bài khu này khỏi **mọi truy vấn khác** của khách: tìm kiếm, dải "bài mới nhất", feed RSS, REST |

**Đo trước khi bịt:** một truy vấn tìm kiếm của khách trả về **10 bài Đại Sĩ**. Rò thật.

Ba filter để đổi mà không sửa code:
`nntm_trang_can_dang_nhap` · `nntm_term_khu_han_che` · `nntm_duoc_xem_khu_han_che`

### Điều kiện nghiệm thu — chạy lại mỗi khi đụng vào phần này

| | Khách | Thành viên |
|---|---|---|
| `/dai-si-hanh-gia/` · `/kim-cuong-hanh-gia/` | 302 | 200 |
| `/tham-gia-chuoi-tri/` · `/khai-bao-chuoi-tri/` | 302 | 200 |
| `/nghi-quy/` · trang chủ · các phân mục | 200 | 200 |
| tìm "Đại Sĩ Hành Giả" | 4 kết quả, **không bài nào** | 10 kết quả, **có Bài 1,2,3…** |
| tổng `nntm_article` truy vấn được | 16 | 51 |

**Không được giết nhầm:** Hoa Khai 7→7, Nguyên Thuỷ 4→4, `post` 14→14, video 12→12,
ấn phẩm 45→45.

⚠️ `pre_get_posts` phải **CỘNG THÊM** `tax_query` chứ không gán đè — block `nntm/card-list`
đã tự đặt `tax_query`; gán đè là mọi dải bài trên site hiện sai nội dung.

⚠️ Bộ lọc **tự bỏ qua khi `PHP_SAPI === 'cli'`**. Lý do ở mục 9.

---

## 6. Cộng Tu "chuỗi trì" — quy tắc chủ dự án chốt 14/08/2026

Hạng mục này thuộc **Phase 2** trong báo giá, được chủ dự án yêu cầu kéo về làm sớm.

### Quy tắc tính

Mỗi người giữ **hai dòng số độc lập, chỉ cộng thêm, KHÔNG BAO GIỜ ghi đè**:

| Dòng | Cộng từ | metric |
|---|---|---|
| CAM KẾT | đăng ký ban đầu (+100), cam kết thêm (+200) | `cam_ket` |
| THỰC TẾ | khai báo mỗi ngày (+10, +20, +50) | `thuc_hien` |

`Tiến trình = thuc_hien / cam_ket`. BXH xếp theo **`thuc_hien` giảm dần**.

- **Nhiều chương trình** → mỗi dòng sổ mang `program_id`
- **Mọi thành viên đã đăng nhập** tham gia được
- Một ngày khai báo **nhiều lần, cộng dồn**; **không cho khai lùi ngày**
- Vượt cam kết: **số thật giữ nguyên**, chỉ thanh tiến trình chặn ở 100%
- **BXH chốt mỗi ngày** (transient 24h, **không xoá cache khi ghi**);
  ba con số thống kê **tươi ngay khi ghi** (đệm trong option, cộng dồn lúc ghi);
  số của **chính người đang xem** luôn truy vấn thẳng nên đổi ngay
- **Không đẩy realtime** — sẽ phải bật lại Soketi mà `03-chot-tu-khao-sat.md` mục C đã đề
  xuất bỏ (khách không chọn ở câu 24, không nằm trong hạng mục nào của báo giá)

### API (plugin `class-chuoi-tri.php`)

```php
nntm_program_dang_mo( int $program_id ): bool
nntm_program_hien_tai(): ?WP_Post
nntm_kpi_cam_ket( int $program_id, int $user_id, int $so_chuoi ): bool|WP_Error
nntm_kpi_ghi_nhan( int $program_id, int $user_id, int $so_chuoi ): bool|WP_Error
nntm_kpi_tong_cua_nguoi( int $program_id, int $user_id ): array
nntm_kpi_da_tham_gia( int $program_id, int $user_id ): bool
nntm_kpi_ghi_hom_nay( int $program_id, int $user_id ): int
nntm_kpi_tong_chuong_trinh( int $program_id ): array
nntm_kpi_bang_xep_hang( int $program_id, int $limit = 200 ): array
nntm_kpi_hang_cua_nguoi( int $program_id, int $user_id ): ?int
nntm_kpi_tinh_lai_tong( int $program_id ): array
```

### Điều kiện nghiệm thu (POST thật qua form)

```
cam kết 100 → cam kết thêm 200 → khai báo 10, 20, 50
→ cam_ket=300  thuc_hien=80  tiến trình 27%
→ "Hôm nay bạn đã ghi" = 80
```
Nhập `0`, `-5`, `abc`, hoặc chưa tick điều khoản → ở lại trang, hiện lỗi, **không ghi dòng nào**.

### Dữ liệu demo trong CSDL

Chương trình **352 "Lễ Đàn Khổng Tước"** đang mở, kèm vài dòng KPI demo của `nntm_admin`
để khối thống kê có số mà xem. Xoá khi cần:
```sql
DELETE FROM wp_nntm_kpi_log WHERE program_id = 352;
```
rồi xoá option `nntm_kpi_tong_352` và transient `nntm_kpi_bxh_352_*`.

---

## 7. Việc còn lại

### 🔴 Ưu tiên cao

**Nút "Yêu thích" chỉ có vỏ ở HAI trang.** `single-nntm_article.php` và
`single-nntm_publication.php` đều render `data-nntm-favorite="<post ID>"` nhưng **chưa có
endpoint lưu/bỏ**. Bảng `wp_nntm_favorites` đã có sẵn từ Phase 1. **Làm một lần cho cả hai
chỗ, đừng làm rời.**

**Chữ trắng trên nền vàng không đọc được.** Đo trên mã màu thật:

| Chữ | Nền | Tỉ lệ | |
|---|---|---|---|
| trắng `#FFFFFF` | vàng `#D4AF37` | **2,10 : 1** | ❌ dưới cả ngưỡng chữ lớn 3:1 |
| mực `#3F3B3B` | vàng `#D4AF37` | 5,26 : 1 | ✅ |
| trắng `#FFFFFF` | đỏ `#8B1E2D` | 9,05 : 1 | ✅ |

Ảnh hưởng tiêu đề "Kim Cương Hành Giả" và "Thống Kê Của Đạo Tràng". **Đã giữ nguyên chữ
trắng đúng thiết kế** — chờ chủ dự án chốt: giữ nguyên / đổi chữ sang mực sẫm / thêm nền
tối mờ sau chữ.

### 🟠 Chưa nghiệm thu được bằng mắt

**Trình duyệt tự động trong phiên KHÔNG nạp được stylesheet liên kết** — chỉ CSS nội tuyến
chạy (WordPress nội tuyến file style dưới ~20KB). Hậu quả: mọi số đo lấy từ công cụ đó đều
sai; đo trang chủ cũng ra header cao 357px. Đã kiểm CSS phục vụ qua HTTP đều 200 và đúng
dung lượng — lỗi ở công cụ, không ở code.

**Mọi khẳng định về bố cục trong tài liệu này đều chưa được nhìn tận mắt.** Chỉ hành vi đo
qua HTTP và CSDL là chắc chắn. Chủ dự án phải mở trình duyệt thật và báo chỗ lệch.

Còn chờ duyệt bằng mắt: trang Nghi Quỹ · khối Thống Kê + BXH · màn khai báo hằng ngày ·
chiều cao banner tràn viền `clamp(420px, 45vw, 700px)` · toàn bộ mobile/tablet.

### 🟠 Việc chưa động tới

- **Vườn Xoài** — trang phân mục duy nhất chưa dựng
- **5 trang phân mục dựng theo R3** — cần đối chiếu lại R5 (R5 đổi chiều cao lần nữa)
- **Thư Viện PDF** (30tr, nặng nhất Phase 1) · **Pháp Thoại + player** · **Thiền Đường** ·
  **Tìm kiếm không dấu + autocomplete** · **Đăng nhập Google/Facebook** ·
  **Song ngữ Polylang (nội dung EN)** · **Bảo mật & golive**
- **Trang tài khoản cá nhân** — `inc/auth.php` đang trỏ `/tai-khoan/` và `/yeu-thich/`
  qua filter, hai trang này **chưa tồn tại**

### 🟡 Lỗi cũ còn mở

- **Thứ tự bài trong `nntm/article-mosaic` không ổn định.** Gốc rễ: 6 bài trong category 13
  có **ngày đăng trùng nhau** nên `orderby=date` không quyết định được thứ tự. Cách chữa rẻ
  nhất là đặt ngày lệch nhau vài phút (đã áp cho các seed mới), không phải sửa `post__in`.
- **SECTION 1 trang Hoa Khai lệch 39px** (873 so với Figma 834). Nghi ở đoạn trích thẻ cột
  trái. Tắt `showExcerpt` thì về ~828. **Phải hỏi chủ dự án trước.**
- **Ba lớp chữ trong thẻ nhỏ hero** (`.nntm-hero-slider__sidecard`) là số ước lượng từ ảnh,
  chưa lấy từ Figma.
- **Ảnh mẫu còn sót:** tấm thứ 3 của `hero-slider` vẫn dùng `hero-3.png` — ảnh xám phẳng do
  script seed sinh.
- **Link YouTube trên trang chủ là LINK TẠM** `gJAbDSse5WM`. Khi khách gửi danh sách thật
  thì sửa `tools/seed-video-mau-trang-chu.php` rồi chạy lại.
- **Biến thể `article-hover` của `nntm/card`** khai trong enum nhưng không khác gì `article`
  — chưa hiện thực.

---

## 8. Chờ khách trả lời

| Việc | Vì sao chặn |
|---|---|
| **Thư Viện PDF bắt buộc đăng nhập?** (`03-chot…` mục A) | `nntm_an_pham_can_access()` đang để mặc định **ai cũng đọc được** và **chưa bật chặn ở đâu** — không tự quyết thay khách |
| **Câu 16** — tìm bằng ảnh ngay ngày ra mắt hay Phase 2 | khách tích "ngay ra mắt", báo giá xếp Phase 2 |
| **Câu 19, 21** — số lượng PDF và bài audio | chưa chốt được gói VPS/CDN |
| **Thiền Đường** — chỉ đăng nhập hay theo cấp | có sẵn filter `nntm_thien_duong_can_access` |
| **Font Battambang** — có mua bản Việt hoá không | bản thay Be Vietnam Pro làm bề rộng chữ lệch Figma ~2% |
| **Chữ trắng trên nền vàng** | xem mục 7 |
| **Ảnh banner "Lễ Đàn Khổng Tước"** | đang tạm dùng lại attachment 239 |
| **Khối BXH là Phase 2** | đã làm sớm theo yêu cầu — cần xác nhận thương mại |

---

## 9. Bẫy đã cắn thật — ĐỌC TRƯỚC KHI CODE

Mỗi mục dưới đây đã làm hỏng việc ít nhất một lần trong dự án này.

### Thêm attribute vào `block.json` mà quên ô điều khiển trong `editor.js`
Vi phạm ràng buộc mạnh nhất của dự án (`04-kien-truc.md` mục 2: khách phải tự sửa được).
Triệu chứng: nút hiện trên trang mà trong trình soạn thảo không có chỗ nào chỉnh.
**Đã mắc nhiều lần.** Cũng từng có biến thể khai trong enum mà `render.php` **chưa từng đọc
tới** — bấm vào không có tác dụng gì.

### Polylang: bài không gán ngôn ngữ thì BIẾN MẤT khỏi tìm kiếm
```
WP_Query( post_type = 'nntm_article' )  -> 11 ket qua, CO du bai
WP_Query( post_type = 'any' )           ->  6 ket qua, MAT sach
```
Tìm kiếm của site chạy `post_type = 'any'` → bài **không bao giờ tìm thấy được, kể cả khi
đã đăng nhập**, dù vẫn hiện bình thường trên trang danh sách. Rất dễ tưởng là lỗi phân quyền.
→ **Mọi script seed tạo post đều phải gọi `pll_set_post_language()`.**

### `gmdate()` cho `post_date` làm bài rơi vào trạng thái `future`
Lệch múi giờ khiến `wp_insert_post()` tự đổi status. Dùng `date()` + `get_gmt_from_date()`,
hoặc `current_time()`. **Không dùng `gmdate()` / `date()` trần cho ngày giờ nghiệp vụ.**

### Bộ chặn quyền làm hỏng chính script seed của mình
Script chạy bằng dòng lệnh **không có phiên đăng nhập** → `pre_get_posts` giấu bài khỏi
chính công cụ của mình. `seed-kim-cuong-hanh-gia.php` kiểm tra trùng không thấy 26 bài vừa
tạo, chạy lần hai tạo thêm 26 bài nữa — **tổng 52**.
→ Đã sửa tận gốc: `nntm_duoc_xem_khu_han_che()` bỏ qua chặn khi `PHP_SAPI === 'cli'`.

### Hai script seed giành nhau một trang
`seed-kim-cuong-hanh-gia.php` ghi đè toàn bộ nội dung trang 243, **xoá mất khối
`nntm/cong-tu`** mà `seed-cong-tu.php` đã chèn — không có lỗi nào báo.
→ Script giờ chỉ dựng lại phần **nó sở hữu**, block lạ được `serialize_block()` giữ nguyên.

### Sửa cấu hình trang bằng script tạm rồi vứt đi
Một phần việc gắn `className` + bật `tranVien` cho trang 243 bằng script trong scratchpad,
không cập nhật vào script seed. Lần sau chạy seed → reset sạch: ảnh hết tràn màn, CSS scoped
không ăn, băng chạy về lưới tĩnh.
→ **Script seed phải dựng ra trạng thái cuối cùng.** Chạy lại bao nhiêu lần cũng ra đúng vậy.

### `render.php` của block bị `require` (không phải `require_once`)
Hàm khai thẳng trong `render.php` sẽ chết "Cannot redeclare function" khi block render lần
thứ hai trong cùng request (ví dụ ServerSideRender). → Hàm dùng chung để trong `inc/`.

### Đừng suy kích thước từ ảnh chụp
Hero từng sai 120px vì `aspect-ratio` lấy từ ảnh mẫu chứ không từ thiết kế. Chỗ nào comment
ghi `SUY DOAN` / *"do tu anh"* thì nên nghi ngờ và đối chiếu lại.

### Bảng đo dải ngang KHÔNG cho biết cấu trúc lồng nhau
Bản sửa 12/08 dựa vào bảng đo đã làm mất lớp nền bọc ngoài chân trang và dựng sai hẳn khối
ENGINEERING EARTH. **Phải cắt ảnh từng vùng ra nhìn.**

### Vá triệu chứng thì che mất nguyên nhân
Mục menu đầu lệch 4px, hướng đầu tiên là thêm `padding-top` bù vào. Làm ngược lại — bỏ đệm
ở các mục kia — mới ra `li + li` trong `base.css`, một lỗi ảnh hưởng toàn site.
**Khi thấy mình đang bù trừ cho một con số lẻ, hãy hỏi vì sao con số đó tồn tại.**

### Plugin bên thứ ba lọc truy vấn sau lưng mình
Khi một bản ghi hiện ở chỗ này mà mất ở chỗ kia, hãy hỏi **plugin nào đang cắm vào
`pre_get_posts`** trước khi nghi code của mình.

### Ràng buộc của WordPress lõi phải thử bằng dữ liệu thật tiếng Việt
Trường "Pháp danh" gán thẳng vào `user_login` nhìn thì hợp lý, chạy thử với chữ không dấu
cũng qua — nhưng `sanitize_user( $x, true )` bóc dấu nên `validate_username()` đánh trượt
**mọi** pháp danh tiếng Việt. Một trang Phật giáo mà không ai đăng ký được.
→ Đã tách: pháp danh có dấu lưu ở `display_name` + meta `nntm_phap_danh`; `user_login` là
bản không dấu tự sinh; đăng nhập chấp nhận cả hai lẫn email.

### Phóng cả bản thiết kế thì phải phóng ĐỒNG THỜI ba nhóm token
Đổi `font-size` gốc để scale theo màn rộng mà `--nntm-sp-*` và `--nntm-r-*` vẫn là px cứng
→ chữ phồng 1,4 lần, logo bị cắt, chữ tràn khung. Cảnh báo đã ghi trong `tokens.css`.

### Các bài học cũ vẫn còn giá trị
Agent chết giữa chừng để lại rác · đệm chồng đệm · đừng truyền `meta_key` vào `get_terms()` ·
logic dữ liệu gom về plugin · **đo trong trình duyệt đừng tin code**.
