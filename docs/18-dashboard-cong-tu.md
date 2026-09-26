# Dashboard cá nhân Cộng Tu — Phase 2 (khảo sát câu 26–28)

Phiên 25/09/2026. Nhánh `phase2-dashboard-cong-tu` (nền `main-go-chu-thich`).

Trang **`/cong-tu-cua-toi/`** — "Cộng tu của tôi". Vào từ menu tài khoản trên header
(chỉ khi đã đăng nhập). Khách bị chuyển tới đăng nhập rồi quay lại.

## 1. Trang có gì

| Phần | Nội dung |
|---|---|
| Đầu trang | chào theo pháp danh, tên chương trình + Đang mở / Đã khép lại, "Hôm nay: Thứ Sáu, 25/09/2026"; danh sách chương trình khác nếu có sổ ở ≥ 2 chương trình |
| Nút | *Khai báo hôm nay* · *Cam kết thêm* (hoặc *Tham gia chương trình*) — mở modal có sẵn; chỉ hiện khi đang xem đúng chương trình đang mở |
| 3 ô số | hôm nay · tuần này (kèm "4/5 ngày có khai báo") · **nhịp công phu** (số ngày liền có khai báo) |
| Cam kết của bạn | đã trì / cam kết, %, thanh tiến trình (vượt thì đổi màu, số thật giữ nguyên), "còn X nữa" hoặc "đã tròn · vượt Y"; có ngày kết thúc hợp lệ thì thêm "còn N ngày — khoảng Z mỗi ngày" |
| 14 ngày gần nhất | cột theo ngày; hôm nay màu đỏ thẫm; ngày trước khi bắt đầu tham gia màu nhạt; cả 14 ngày trống → câu chữ thay biểu đồ |
| Theo tuần | tối đa 8 tuần, bỏ các tuần trước khi người này bắt đầu; so với **cùng kỳ** tuần trước (cùng số ngày, không so nửa tuần với cả tuần) |
| Nhật ký khai báo | bảng theo tuần, mới nhất trước: ngày, đã trì, số lần ghi, cam kết thêm — cũng là bản thay thế đọc được của hai biểu đồ |

Khai báo trong modal xong → trang tải lại (sự kiện `nntm-congtu:da-ghi` mà
`cong-tu-modal.js` giờ phát ra) để mọi số được tính lại từ máy chủ.

## 2. Thiết kế

- **Một truy vấn** gom theo ngày cho (chương trình, người) — index `user_date` — rồi
  PHP tính tất cả. Mọi số trên trang luôn khớp nhau và khớp `nntm_kpi_tong_cua_nguoi()`.
- **Không đệm** — số của chính người xem phải đổi ngay (docs/07 mục 6).
- **"Hôm nay" = `current_time('Y-m-d')`** — đúng hàm ghi `log_date`. Không dùng
  `CURDATE()`/`NOW()` của MySQL (máy dev đo thật: MySQL lệch PHP 7 giờ). Cộng trừ ngày
  bằng `DateTimeImmutable` UTC trên nhãn `Y-m-d`.
- **Tuần** theo Cài đặt → Tổng quan → "Tuần bắt đầu vào" (đang Thứ Hai); gọi bằng
  khoảng ngày "21/09 – 27/09", không đánh số tuần ISO. Filter `nntm_kpi_bat_dau_tuan`.
- `?chuong-trinh=ID` chỉ nhận chương trình người này có sổ hoặc chương trình đang mở.
- Biểu đồ: **HTML + CSS**, không thư viện, không SVG co giãn (chữ nhãn giữ đúng cỡ trên
  điện thoại). Trang `noindex` + `nocache`.
- Page thật do `tools/seed-cong-tu.php` tạo → BQT sửa được tiêu đề và đoạn giới thiệu;
  bố cục số liệu là màn chức năng (docs/04 mục 2), không phải block.

| Tệp | |
|---|---|
| `plugins/nntm-core/includes/class-chuoi-tri-ca-nhan.php` | `nntm_kpi_bang_dieu_khien()`, `nntm_kpi_theo_ngay_cua_nguoi()`, `nntm_kpi_chuong_trinh_cua_nguoi()`, `nntm_kpi_dau_tuan()`, `nntm_kpi_ngay_hop_le()` … |
| `themes/nntm/inc/cong-tu-ca-nhan.php` | URL, chọn chương trình, noindex/nocache, nạp CSS/JS, định dạng ngày |
| `themes/nntm/page-cong-tu-cua-toi.php` + `template-parts/cong-tu/ca-nhan.php` | **màn tự dựng**, Figma chưa có |
| `themes/nntm/assets/css/pages/cong-tu-ca-nhan.css` | chỉ token |
| `inc/cong-tu.php` (chặn khách), `header.php` (menu), `cong-tu-modal.js` (sự kiện), `tools/seed-cong-tu.php` (tạo trang) | sửa nhỏ |

## 3. ⚠️ Chờ chủ dự án

- **Múi giờ site đang là UTC** (`timezone_string` rỗng, `gmt_offset` 0). Với thành viên
  ở Việt Nam, "hôm nay" **đổi ngày lúc 7:00 sáng** — khai báo từ 0:00 đến 6:59 bị ghi vào
  ngày hôm trước. Ảnh hưởng cả luồng khai báo có sẵn lẫn dashboard. Đổi sang
  `Asia/Ho_Chi_Minh` trước khi ra mắt thì sạch nhất; dòng sổ cũ giữ ngày cũ (không có cột
  giờ GMT để tính lại). Kiểm cả trên production.
- Khai báo mà **chưa cam kết** vẫn được (code có sẵn cho phép) — dashboard hiện "chưa đặt
  mức cam kết" thay cho %.
- Meta chương trình (ngày bắt đầu/kết thúc, đơn vị, mục tiêu) **chưa có ô nhập trong
  wp-admin**, chỉ đặt bằng script seed. "Còn N ngày — Z mỗi ngày" chỉ hiện khi có ngày kết thúc.

## 4. Đã kiểm

- Dữ liệu thật chương trình 352: tổng khớp `nntm_kpi_tong_cua_nguoi`, "hôm nay" khớp
  `nntm_kpi_ghi_hom_nay`, tổng nhật ký = tổng đã trì, đầu tuần đúng cả qua năm mới
  (01/01/2027 → 28/12/2026), kiểm ngày sai định dạng. Giả lập ngày 22/08: nhịp 2, tuần 86.
- Dòng sổ tạm 10 ngày gần đây (đã xoá sau khi thử): hôm nay 30, tuần này 145 = 40+21+54+30,
  nhịp 2 (23/09 trống), 14 ngày 262, cùng kỳ tuần trước 99.
- HTTP: khách → 302 tới `/dang-nhap/?redirect_to=…`; các trang Cộng Tu cũ giữ nguyên hành vi.
- Render như thành viên (định tuyến WordPress thật, `wp_set_current_user`, không giả cookie
  đăng nhập): 3 trạng thái — vượt cam kết (1552%), 12%, chưa tham gia; không PHP warning.
- Chrome headless 1366px + 390px. **Lỗi thấy trên ảnh và đã sửa:** biểu đồ 14 ngày trên
  điện thoại sụp (ẩn hàng số bằng `display:none` làm lưới mất một hàng) và nhãn "Tuần này"
  bị cắt.

⚠️ **Chưa kiểm:** bấm thật "Khai báo hôm nay" → modal → trang tải lại (cần đăng nhập
trong trình duyệt).

## 6. Bảng xếp hạng trong wp-admin (26/09/2026)

BQT xem BXH của từng chương trình Cộng Tu ngay trong wp-admin, không phải chỉ
xem ngoài trang. Nhánh `phase2-bxh-quan-tri`.

| | |
|---|---|
| Vào ở đâu | Submenu **Bảng xếp hạng** dưới CPT "Chương trình trì tụng" (`edit.php?post_type=nntm_program`), slug `nntm-bxh`. Cũng có hàng thao tác "Xếp hạng" ở màn danh sách chương trình, trỏ thẳng tới đúng chương trình đó. Quyền lọc được qua filter `nntm_bxh_quyen` (mặc định `manage_options`). |
| Chọn chương trình | Dropdown mọi chương trình (kể cả draft/private/future), có đánh dấu "(đang mở)". Mặc định: `?chuong-trinh=ID` hợp lệ → chương trình đang mở → chương trình mới nhất. |
| Cột bảng | Hạng · Pháp danh (link hồ sơ user) · Tài khoản (user_login + email) · Vùng miền · Đã trì · Cam kết · Tiến trình % · Số ngày khai báo · Khai báo gần nhất · Tham gia từ. Tìm theo pháp danh/tài khoản/email (bỏ dấu, không phân biệt hoa thường), sắp xếp được hạng/đã trì/cam kết/tiến trình/số ngày/gần nhất, phân trang 50 dòng. |
| Tệp | `plugins/nntm-core/includes/class-bxh-admin.php` — class `\NNTM\Core\Bxh_Admin` (singleton) + hàm `nap_lop_bang_xep_hang()` nạp lớp `Bxh_Bang_Danh_Sach extends WP_List_Table` (khai báo NGOÀI class vì PHP không cho lồng khai báo class trong thân một class khác). |

### Khác BXH ngoài trang thế nào

- **Tươi vs chốt ngày**: màn này LUÔN truy vấn thẳng bảng `nntm_kpi_log` (không đọc
  transient/option đệm) — BQT cần số thật ngay sau khi sửa dữ liệu. BXH ngoài trang
  (`nntm_kpi_bang_xep_hang()`) cố ý cache transient 24 giờ để đỡ tải mùa cao điểm
  (xem chú thích trong `nntm_kpi_cam_ket()`), nên có thể lệch tới 24 giờ — màn này
  có ghi rõ câu đó ngay trên đầu trang.
- **Cách xếp hạng khác nhau — ⚠️ CHỜ CHỦ DỰ ÁN CHỌN THỐNG NHẤT**: màn quản trị dùng
  kiểu "competition ranking" giống `nntm_kpi_hang_cua_nguoi()` — bằng điểm thì cùng
  hạng, hạng kế nhảy đúng số người phía trước (ví dụ 1, 2, 2, 4). BXH ngoài trang
  (`nntm_kpi_bang_xep_hang()`) lại đánh số hạng TUẦN TỰ theo thứ tự dòng trả về
  (1, 2, 3, 4…), nên hai người bằng điểm ngoài trang vẫn ra hai hạng liền nhau khác
  nhau. Đây là chênh lệch có sẵn giữa hai nơi trong code, chưa thống nhất — chủ dự
  án cần chốt dùng kiểu nào cho cả hai, hiện tại cố ý giữ nguyên cả hai để không
  đổi hành vi ngoài trang đã chạy.

### CSV và nút làm mới

- **Tải về CSV**: toàn bộ người của chương trình đang chọn, theo hạng, có BOM
  UTF-8, tên file `bang-xep-hang-<slug chương trình>-<Y-m-d>.csv`. Chặn CSV
  injection: ô bắt đầu bằng `= + - @` thì thêm dấu `'` phía trước. Hàm dựng nội
  dung (`xuat_csv_noi_dung()`) tách khỏi hàm gửi header/exit để dễ kiểm.
- **Nút "Làm mới BXH ngoài trang"**: xoá mọi transient `nntm_kpi_bxh_<id>_*` của
  chương trình (tự lặp lại cách quét LIKE trên `wp_options` — KHÔNG gọi hàm
  `nntm_congtu_xoa_cache_bxh()` của theme vì plugin không được phụ thuộc theme,
  chỉ gọi thêm qua `function_exists()` nếu có) rồi gọi `nntm_kpi_tinh_lai_tong()`.
  Dùng khi BQT vừa sửa dữ liệu và muốn ngoài trang cập nhật ngay, không đợi hết
  24 giờ.

### Đã kiểm

- `php -l` cho `class-bxh-admin.php` và `nntm-core.php`.
- Script thử tạo 1 chương trình nháp + 4 user tạm (`@example.test`) + dòng
  `kpi_log` tạm, 2 người bằng điểm: hạng ra đúng 1, 2, 2, 4 (không nhảy thành
  1, 2, 2, 3). Tổng cam kết/đã trì/tiến trình khớp; số ngày khai báo, ngày gần
  nhất, ngày tham gia đúng cho từng người; người cam kết 0 hiện "—" thay vì lỗi
  chia 0. Tìm "nguyen" (không dấu) khớp pháp danh "Nguyễn Văn A". Sắp xếp theo
  cam kết giảm dần đúng thứ tự. Phân trang đúng khi hạ per_page xuống 2 (trang 1
  và trang 2 mỗi trang đủ 2 dòng, không đổi hạng theo trang).
- Dựng CSV: có BOM, dòng đầu đúng hạng 1, pháp danh giả `=HYPERLINK(1)` bị chặn
  CSV injection (thêm dấu `'` phía trước), không còn ô nào bắt đầu bằng `=` trần.
- Nút làm mới: đặt transient giả `nntm_kpi_bxh_<id>_200` rồi gọi hàm làm mới →
  transient mất, đồng thời tổng đệm (`nntm_kpi_tong_<id>`) được tính lại đúng.
- Hàng thao tác "Xếp hạng": hiện với bài `nntm_program`, KHÔNG hiện với post type
  `post`.
- Render toàn màn (`ob_start`) cho cả chương trình nháp lẫn chương trình thật ID
  352 (chỉ đọc) — không có PHP warning/notice nào (bắt bằng `set_error_handler()`
  trong lúc render).
- HTTP: chưa đăng nhập vào `wp-admin/edit.php?post_type=nntm_program&page=nntm-bxh`
  → 302 về `wp-login.php` (không lỗi 500).
- Dữ liệu tạm (dòng `kpi_log`, 4 user, chương trình nháp, option/transient liên
  quan) đã xoá sạch sau khi kiểm, kiểm lại xác nhận không còn sót.