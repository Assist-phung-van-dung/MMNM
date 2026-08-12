# Báo giá & Phạm vi — Nẵng Nhân Tịch Mặc

**Nguồn chính thức:** `Bao_Gia_Chi_Tiet_320tr_Theo_Khao_Sat.docx` (cùng thư mục này).
Đã đọc và dùng làm hợp đồng kỹ thuật. Không sửa file gốc.

## Chốt nhanh

| | |
|---|---|
| Tổng | 320.000.000 VNĐ (chưa VAT) |
| Phase 1 | 155tr — 4 tuần — ra mắt chính thức |
| Phase 2 | 95tr — 6–8 tuần sau ra mắt — tìm kiếm nâng cao & Cộng Tu |
| Phase 3 | 70tr — 4–6 tuần sau P2 — Cộng đồng & hoàn thiện |
| CMS | WordPress 6.x • PHP 8.3 • MySQL 8 |
| Theme | Code riêng 100% theo Figma, không dùng theme mua sẵn |
| Đa ngữ | Polylang (Việt/Anh) |
| PDF | PDF.js tùy biến — chặn tải/copy/in + watermark theo tài khoản |
| Audio | HTML5 + CDN (Cloudflare/Bunny), signed URL |
| Realtime | Soketi (Pusher-compatible) — Thiền Đường |
| Search ảnh | Google Vision API **hoặc** CLIP + Qdrant |
| Email | Amazon SES / SendGrid |
| Hạ tầng | VPS 4 vCPU / 8GB, Nginx, Redis, Cloudflare, SSL, backup ngày |
| Quy trình | Git + CI/CD, staging riêng |

## Ràng buộc tiến độ Phase 1 (ghi trong báo giá)

- Figma hoàn chỉnh **trước** ngày khởi công
- Khách phản hồi duyệt trong 24–48h mỗi hạng mục
- Nội dung nhập song song từ tuần 2
- Tối đa 2 vòng chỉnh sửa / hạng mục; ngoài scope = 500k/giờ

## Cam kết minh bạch đã nêu với khách (không được hứa quá)

- Chặn tải PDF chỉ ở mức **răn đe**, không chặn được chụp màn hình
- Voice search phụ thuộc trình duyệt (tốt nhất Chrome)
- Search ảnh cải thiện dần theo lượng index
- OCR PDF scan phụ thuộc chất lượng bản scan
- Nhạc nền PDF không autoplay trước thao tác đầu tiên (giới hạn trình duyệt)
- Facebook Login chờ Meta duyệt 1–3 tuần, không ảnh hưởng ngày ra mắt

## Còn thiếu để bắt tay vào việc

- [ ] **Phiếu Khảo Sát 40 câu** — báo giá tham chiếu "mục 4/17/26..." xuyên suốt, thiếu file này là thiếu spec gốc
- [ ] Chat gốc trên claude.ai → `01-chat-goc.md`
- [ ] Quyền đọc Figma (token read-only)
- [ ] Chốt: Google Vision **hay** CLIP+Qdrant
- [ ] Thông tin VPS / domain đã có chưa
