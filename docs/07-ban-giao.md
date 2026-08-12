# Bàn giao phiên làm việc — 07/08/2026

Tài liệu này để mở đầu phiên chat mới. Đọc file này trước, rồi đọc `04-kien-truc.md`.

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
DESKTOP - R1                      (bản cũ nhất, bỏ qua)
DESKTOP - R3                      (bản cũ — PHẦN LỚN CODE HIỆN TẠI DỰNG THEO ĐÂY)
DESKTOP - R4 (UPDATED 6AUG)       ← BẢN CHÍNH THỨC, id 6376:6205
```

**Từ 07/08/2026 chỉ dùng R4.** Trước đó nhầm dùng R3 nên nhiều trang cần dựng lại.

Node R4 hay dùng:
- Trang chủ `6376:6322` · Header `6376:7302` · Footer `6376:7384`
- Diệu Thượng `6376:6694` · Pháp Toà `6376:6727` · Liên Đàn `6376:6744`
- Hoa Khai `6376:6603` · Vườn Xoài `6386:5177` · Nhập Pháp Giới `6376:6488`

**Figma API hay bị chặn tốc độ (lỗi 429)** ở endpoint đọc node. Giới hạn tính theo *file và tài khoản*, đổi token KHÔNG gỡ được. Hai cách đi vòng đã dùng thành công:
1. Endpoint **xuất ảnh** (`/v1/images`) không bị chặn → xuất PNG rồi đo pixel. Có sẵn công cụ đọc/cắt PNG thuần Python trong thư mục scratchpad (`png.py`, `crop.py`).
2. Token từ **tài khoản Figma khác** thì có hạn mức riêng.

Khi đọc được node, chỉ lấy đúng node cần kèm `depth`, **đừng kéo cả trang** — đó là nguyên nhân bị chặn.

---

## 2. Đang có gì

### Môi trường
- WordPress **7.0.2** tiếng Việt, database `nntm_dev`, tiền tố bảng `wp_`
- PHP local **8.1** — ⚠️ báo giá cam kết **8.3**, phải nâng trước khi lên staging
- Quản trị: `nntm_admin` / `NntmDev!2026` (chỉ dùng local)
- Chạy: xem `docs/06-chay-local.md`

### Plugin `nntm-core` (dữ liệu — không phụ thuộc theme)
- 7 CPT: bài viết, ấn phẩm, pháp thoại, khoá tu, trú xứ, video, nhạc thiền
- 3 taxonomy: phân mục (phân cấp), chủ đề, bộ/series
- 2 vai trò: Đại Sĩ, Kim Cương Hành Giả + nâng cấp hàng loạt trong admin
- 5 bảng riêng: tiến độ đọc/nghe, ghi chú, yêu thích, đăng ký khoá tu, KPI
- Term meta: ảnh chuyên mục + **thứ tự hiển thị**
- Post meta: tệp nhạc thiền, địa điểm trú xứ
- `nntm_sort_terms_by_order()` — hàm sắp chuyên mục dùng chung cho nhiều block

### Theme `nntm` — 11 block
`card` · `card-list` (lưới + băng cuộn tự chạy) · `cta` · `paging` · `feature` · `tru-xu-list` · `term-list` · `article-rows` · `article-mosaic` · `thien-duong` · `hero-slider`

Đầu trang và chân trang là **PHP template**, không phải block.

### Trang đã dựng

| Trang | Theo bản | Tình trạng |
|---|---|---|
| Trang chủ | **R4** ✅ | 6 khối, là trang chủ của site |
| Chân trang | **R4** ✅ | vừa dựng lại |
| Đầu trang | R3 ⚠️ | 3 trạng thái chạy được, còn 3 điểm chờ xác nhận |
| Diệu Thượng | R3 ⚠️ | cần đối chiếu R4 |
| Pháp Tòa | R3 ⚠️ | cần đối chiếu R4 |
| Nguyên Thuỷ | R3 ⚠️ | cần đối chiếu R4 |
| Hoa Khai | R3 ⚠️ | cần đối chiếu R4 |
| Liên Đàn | R3 ⚠️ | cần đối chiếu R4 |
| Vườn Xoài | — | chưa dựng (mới có trang trống) |
| Nhập Pháp Giới | — | chưa dựng (mới có trang trống) |

### Script tạo dữ liệu mẫu (chạy lại được nhiều lần)
```
tools/seed-menu.php         menu chính + chân trang
tools/seed-trang-chu.php    trang chủ + ảnh mẫu + video
tools/seed-dieu-thuong.php  Diệu Thượng + Trú Xứ
tools/seed-phap-toa.php     Pháp Tòa + 4 truyền thống
tools/seed-hoa-khai.php     Hoa Khai + Hoằng Pháp/Tin Tức + ấn phẩm
tools/seed-lien-dan.php     Liên Đàn + khoá tu + nhạc thiền
tools/figma-sync.mjs        kéo design token từ Figma
```

---

## 3. Việc cần làm tiếp, xếp theo mức quan trọng

### 🔴 Đối chiếu 5 trang phân mục với R4
Chiều cao đổi rất nhiều: Diệu Thượng 1560→1125, Pháp Tòa 1164→843, Liên Đàn 2892→1932, Hoa Khai 2813→2547, Vườn Xoài 3897→2836. Càng dựng thêm trên nền R3 càng phải sửa nhiều.

R4 còn có 3 khung mới chưa dựng: `LIEN DAN - KHOA TU LISTING`, `VUON XOAI LISTING`, `KHOA TU CHI TIET`.

### 🟠 Ba điểm đầu trang chờ anh Úy xác nhận
1. Nút ngôn ngữ: R4 bản chưa đăng nhập chỉ có **một nút EN**, bản đã đăng nhập mới có cặp **VI/EN**. Hiện đang dựng cặp cho cả hai.
2. Viên thuốc "Nhập Pháp Giới": trong R4 nó luôn là mục cuối và chỉ thành nút khi **đã đăng nhập**. Hiện đang dựng theo hướng "mục đang xem" — **có thể sai**.
3. Bản dính khi cuộn: nền tối thật hay chỉ là nền đen của khung Figma lộ ra?

### 🟠 Trang chủ còn thiếu
- Khối **ENGINEERING EARTH** (chưa rõ lấy nội dung từ đâu)
- Khối **GITA CENTER** cần **nền cam** — block băng cuộn chưa có tuỳ chọn màu nền
- Trong Figma, "Chúng sanh tranh đấu và đau khổ do đâu?" là **tiêu đề bài nổi bật**, không phải tiêu đề khối — hiện đang dựng sai vai trò

### 🟡 Điểm mờ chờ khách trả lời (từ phiếu khảo sát)
- **Thiền Đường và Thư Viện PDF**: chỉ cần đăng nhập, hay phải là Đại Sĩ / Kim Cương? Phiếu khảo sát nói đăng nhập, tài liệu kiến trúc nói theo cấp. Hiện theo phiếu khảo sát, có sẵn filter `nntm_thien_duong_can_access` để đổi bằng một dòng.
- **Câu 16**: khách tích "tìm bằng ảnh ngay ngày ra mắt" nhưng báo giá xếp Phase 2 — cần xác nhận bằng văn bản.
- **Câu 19, 21**: chưa biết số lượng PDF và bài audio → chưa chốt được gói VPS/CDN.

### 🟡 Hạng mục lớn chưa động tới
Thư Viện PDF (30tr, nặng nhất Phase 1) · Pháp Thoại + Soketi · Hệ thống thành viên + đăng nhập Google/Facebook · Tìm kiếm không dấu + autocomplete · Song ngữ Polylang · Bảo mật & golive

---

## 4. Bài học rút ra trong phiên này

**Agent chết giữa chừng để lại rác.** Một agent bị ngắt vì hết hạn mức đã để lại block `hero-banner` hỏng — khai báo 3 file không tồn tại nhưng vẫn đăng ký, hiện trong trình soạn thảo như block lỗi. Sau mỗi lần agent bị ngắt **phải quét lại xem có file dở dang không**.

**Đệm chồng đệm.** Template từng bọc nội dung trong khung 1220 rồi mỗi block lại trừ tiếp đệm riêng theo Figma → mọi trang hẹp hơn thiết kế ~200px. Quy tắc: **block tự mang đệm, template không canh lề hộ** (mục 11 tài liệu kiến trúc).

**`render.php` của block được nạp bằng `require`, không phải `require_once`.** Khai báo hàm trong đó sẽ chết khi block xuất hiện lần thứ hai trên cùng trang. Hàm dùng chung phải để trong `blocks/<tên>/inc/` nạp bằng `require_once`.

**Đừng truyền `meta_key` vào `get_terms()` để sắp xếp** — sẽ làm biến mất những chuyên mục chưa nhập giá trị. Sắp trong PHP.

**Mỗi block tự viết logic sắp xếp riêng → cùng dữ liệu ra hai thứ tự khác nhau.** Logic dữ liệu phải gom về plugin.

**Đo trong trình duyệt, đừng tin code.** Lỗi băng cuộn tự nhảy 110px do `scroll-snap` chỉ lộ ra khi đo `scrollLeft` thật.
