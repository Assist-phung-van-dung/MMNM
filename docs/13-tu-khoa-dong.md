# Từ khoá động — Phase 2 (khảo sát câu 33–34)

Phiên 25/09/2026. Nhánh `phase2-tu-khoa-dong`.

Rê chuột (hoặc chạm, hoặc Tab bằng bàn phím) vào một từ khoá trong nội dung
trang → chữ ánh vàng chạy qua + thẻ minh hoạ nổi lên (hình, tên, mô tả, nút
"Xem thêm" tuỳ chọn). **Chỉ chạy trên trang BQT đã tích bật** (câu 33).

---

## 1. BQT dùng thế nào

1. **wp-admin → Từ khoá động → Thêm từ khoá**
   - Tiêu đề = từ khoá chính
   - *Cách viết khác*: mỗi dòng một cách (`sen`, `đoá sen`…)
   - *Mô tả ngắn*, *Kiểu hiệu ứng* (Thẻ minh hoạ / Chỉ hình), *Liên kết Xem thêm*
   - *Hình minh hoạ* = ô ảnh đại diện bên phải
2. Mở trang/bài cần bật → thanh bên phải → mục **Từ khoá động** → tích ô.
3. Màn danh sách từ khoá có dòng thông báo liệt kê **trang nào đang bật**.

Áp dụng cho: Trang, Tin tức (`post`), Bài viết phân mục (`nntm_article`).

## 2. Chia tầng

| Ở đâu | Việc gì |
|---|---|
| `plugins/nntm-core/includes/class-tu-khoa-dong.php` | CPT `nntm_tu_khoa_dong`, meta box, meta cờ `_nntm_tu_khoa_dong` trên trang, cột danh sách, đệm transient |
| `plugins/nntm-core/assets/js/tu-khoa-dong-editor.js` | ô tích trong trình soạn thảo khối |
| `themes/nntm/inc/tu-khoa-dong.php` | quyết định nạp asset, đẩy JSON xuống trang |
| `themes/nntm/assets/js/tu-khoa-dong.js` | dò chữ, bọc `<span class="nntm-tkd">`, thẻ nổi |
| `themes/nntm/assets/css/tu-khoa-dong.css` | **màn tự dựng** — Figma chưa có; chỉ dùng token |

Trang không bật → **không nạp một byte nào** (đã kiểm bằng curl).

## 3. Quy tắc dò chữ

- Không phân biệt hoa/thường; **khớp nguyên từ** theo Unicode (chữ có dấu vẫn
  đúng ranh giới — `\b` của JS không dùng được cho tiếng Việt).
- Từ dài thắng từ ngắn: có cả "tâm tỉnh thức" và "tỉnh thức" thì bọc cụm dài.
- Mỗi từ khoá chỉ gắn **lần xuất hiện đầu tiên** trên trang (filter
  `nntm_tu_khoa_dong_so_lan` để đổi).
- **Bỏ qua**: liên kết, nút, tiêu đề H1–H6, form, nav/header/footer, code,
  phần tử `aria-hidden`, và bất cứ gì có `data-nntm-tkd-bo-qua`.
- Chỉ dò trong `#nntm-noi-dung-chinh` (filter `nntm_tu_khoa_dong_vung_do`).
- Bắt được cả chữ dạng NFD (dán từ máy Mac).
- Từ khoá không có cả hình lẫn mô tả → bị bỏ khỏi danh sách frontend.

## 4. Filter

| Filter | Mặc định |
|---|---|
| `nntm_tu_khoa_dong_post_types` | `page`, `post`, `nntm_article` |
| `nntm_tu_khoa_dong_bat` | theo ô tích của trang |
| `nntm_tu_khoa_dong_so_lan` | `1` |
| `nntm_tu_khoa_dong_vung_do` | `#nntm-noi-dung-chinh` |

## 5. Dữ liệu mẫu

```bash
"C:/xampp8_2/php/php.exe" tools/seed-tu-khoa-dong.php
"C:/xampp8_2/php/php.exe" tools/seed-tu-khoa-dong.php --bat=<ID hoặc slug>
```

4 từ khoá mẫu (meta `_nntm_tkd_mau = 1`), bật trên bài
`/bai-viet/hoa-sen-no-giua-bun-nho/`. "Hoa sen" **cố ý không có hình** — kho
ảnh mẫu không có ảnh sen, ảnh đại diện của bài đó là đồi sương.
**Khi khách gửi danh sách thật: xoá các từ khoá mẫu.**

## 6. Đã kiểm

Chrome headless qua CDP: rê chuột cả 4 từ, rời chuột thì đóng, focus bàn phím +
Esc, chạm trên điện thoại 390px (mở / chạm lại đóng), trang chủ nhiều block
(không bọc vào liên kết, không lỗi JS). PHP: lọc dữ liệu nhập, sai nonce bị từ
chối, `javascript:` trong liên kết bị gỡ, meta hiện trong REST của
`nntm_article`, xoá đệm khi lưu/xoá.

⚠️ **Chưa kiểm bằng tay trong wp-admin** (ô tích ở thanh bên trình soạn thảo,
meta box) — cần đăng nhập, làm ở vòng test tay.

## 7. Chờ khách

- **Danh sách từ khoá + hình** (câu 34).
- **Trang trọng điểm nào bật** — BQT tự tích, nhưng nên hỏi để làm sẵn lúc bàn giao.
- **Thiết kế hiệu ứng**: đang tự dựng. Có Figma thì chỉ sửa `tu-khoa-dong.css`.
- Danh sách **dùng chung mọi ngôn ngữ** (CPT không public nên Polylang không
  quản). Khi có bản tiếng Anh cần từ khoá riêng thì phải bật Polylang cho CPT này.
