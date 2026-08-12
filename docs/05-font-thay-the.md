# Bộ font thay thế — quyết định ngày 06/08/2026

Bản so sánh trực quan gửi khách: https://claude.ai/code/artifact/f1d812d9-1851-4ec4-b43b-217bd37890f1

## Vấn đề

Quét 4.689 phần tử trên trang Figma `DESKTOP - R3 CODING`, thiết kế dùng 5 họ chữ. Kiểm tra bộ ký tự trên kho Google Fonts (`https://fonts.google.com/metadata/fonts`, trường `subsets`) cho kết quả: **4/5 không dùng được trên web**.

Bằng chứng cứng — dải ký tự mà Google Fonts phục vụ cho Battambang:

```
/* latin */
unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6,
               U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, ...
```

Không có dải `U+1EA0–U+1EF9` (ạ ả ấ ầ ẩ ẫ ậ ắ ằ ẳ ẵ ặ …) và không có `U+01A0-01B0` (ơ ư). Trình duyệt sẽ lấy chữ có dấu từ một font khác → vỡ nét ngay giữa câu, kể cả trên tên "Nẵng Nhân Tịch Mặc".

## Bảng thay thế đã chốt

| Figma | Số ô chữ | Lý do loại | Thay bằng | Vai trò |
|---|---|---|---|---|
| Battambang | 826 | Font Khmer, phần Latin không có tiếng Việt | **Be Vietnam Pro** | thân bài, tiêu đề nhỏ, giao diện |
| Baskerville | 195 | Font hệ thống macOS, không có bản web. Libre Baskerville (bản web gần nhất) cũng thiếu tiếng Việt | **Lora** | trích dẫn, dẫn nhập |
| Google Sans Flex | 157 | Font nội bộ Google, không cấp phép ra ngoài | **Inter** | nhãn, nút, số liệu |
| EB Garamond | 117 | — | **giữ nguyên** | tiêu đề lớn |
| Century Gothic | 19 | Monotype, giấy phép web tính theo lượt xem | **Questrial** | chữ nhấn hình học |

Cả 5 họ đều đã xác nhận có `vietnamese` trong danh sách subset. Cả 5 đều miễn phí cho mục đích thương mại → **không phát sinh chi phí bản quyền**.

## Đã áp dụng ở đâu

- `wp-content/themes/nntm/theme.json` — 5 `fontFamilies` với slug `sans` / `serif` / `display` / `ui` / `geo`
- `wp-content/themes/nntm/assets/css/tokens.css` — biến `--nntm-font-*`
- `wp-content/themes/nntm/inc/enqueue.php` — nạp từ Google Fonts kèm `display=swap`

## Việc còn phải làm

- [ ] Khách duyệt bản so sánh trước khi code hàng loạt component
- [ ] Trước golive: tự host font (tải file `.woff2` về máy chủ) thay vì gọi Google Fonts — nhanh hơn và không gửi IP người đọc sang Google. Đã ghi vào task 10.
- [ ] Nếu khách muốn giữ đúng cảm giác Battambang, phương án B là mua một font Việt hóa có dáng tương tự — phát sinh chi phí, cần khách quyết.
