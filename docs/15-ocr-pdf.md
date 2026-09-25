# OCR PDF scan — Phase 2 (khảo sát câu 11–12)

Phiên 25/09/2026. Nhánh `phase2-ocr-pdf`. Bổ sung cho `10-ban-giao-tim-kiem.md`
(mục 10 việc 2: "OCR trang scan — Tesseract chạy tại chỗ").

Trang PDF không có lớp chữ (sách scan từ bản giấy) giờ được nhận dạng chữ tiếng
Việt và tìm được như trang đánh máy, kết quả trỏ đúng trang. Không gửi file ra
dịch vụ ngoài — chủ dự án đã chốt không dùng Google Vision.

---

## 1. Chạy thế nào

```
Tải PDF lên ─► /pdf/text (có sẵn) ─► trang có chữ: lưu ngay
                                 └─► trang "trong": meta _nntm_ocr_cho = [2,3,…]
WP-Cron mỗi phút ─► /pdf/ocr (3 trang/lần) ─► nntm_pdf_pages, source = 'ocr'
```

- **Không OCR trong request tải lên**: mỗi trang vài giây, sách 300 trang là
  cả chục phút. Cron làm dần; muốn làm ngay thì chạy công cụ dòng lệnh (mục 4).
- Kết quả nằm chung bảng với trang đánh máy → tìm kiếm không phải đổi gì.
- Trang kết quả ghi **"PDF · trang 12 · chữ nhận dạng từ bản scan"**.
- Trang OCR ra < 20 ký tự hoặc độ tin cậy < 30 (ảnh minh hoạ, hoa văn) → **bỏ**,
  đếm là "không đọc được". Filter `nntm_search_ocr_nguong_tin_cay`.
- Trang đã OCR thì lập chỉ mục lại **không làm lại**.
- Dịch vụ báo thiếu Tesseract (503) → **tạm dừng 30 phút**; dịch vụ không phản
  hồi → tạm dừng 5 phút. Không gọi dồn mỗi phút vào dịch vụ hỏng. Trang vẫn nằm
  trong hàng đợi, không mất. Màn Media / Ấn phẩm hiện cảnh báo vàng.
- **Thư viện Media → cột "Chỉ mục tìm kiếm"**: `12 trang chữ · 30 trang OCR ·
  đang chờ OCR 5 trang · 1 trang không đọc được`.

## 2. ⚠️ Quyết định: trang OCR khớp BỎ DẤU

Bộ lọc 17/08 (`text.php`, `nntm_search_text_matches_terms`) bắt gõ có dấu thì
nội dung phải có **đúng dấu** — để "rừng" không ra "rụng". Với chữ OCR, dấu do
máy đọc, đo thật sai khoảng **1/20 từ** (`chiếu→chiều`, `đẳng→đăng`, `bốn→bôn`).
Giữ bộ lọc thì gõ **"bình đẳng" không ra** trang scan có đúng câu đó (đã thấy thật).

→ **Chỉ với trang `source = 'ocr'`**: bỏ qua bước so đúng dấu, vẫn giữ bộ lọc
cụm câu dài. Trang đánh máy **giữ nguyên** hành vi 17/08 (có phép thử hồi quy).
Đánh đổi: tìm "rừng" có thể ra trang scan chứa "rụng" — có nhãn "nhận dạng từ bản
scan". Tắt: `add_filter( 'nntm_search_ocr_khop_bo_dau', '__return_false' );`

## 3. Cài đặt

**Linux (VPS):**
```bash
apt install tesseract-ocr tesseract-ocr-vie
pip install pypdfium2 pytesseract          # vào venv của tools/embed-service
```

**Windows (máy dev):** `winget install UB-Mannheim.TesseractOCR`, rồi tải
`vie.traineddata` từ github.com/tesseract-ocr/tessdata_best (12,4 MB) vào một thư
mục bất kỳ và đặt `NNTM_TESSDATA_DIR` = thư mục đó khi chạy dịch vụ (không cần
quyền admin để chép vào Program Files).

Kiểm: `curl http://127.0.0.1:8765/ocr/khoe` → `"san_sang": true`. `/ocr/khoe`
**chạy thử OCR thật** trên một ảnh nhỏ — có tên gói trong danh sách chưa chắc nạp
được (bẫy đã gặp, mục 6).

| Biến | Ở đâu | Mặc định |
|---|---|---|
| `NNTM_SEARCH_OCR_ENABLED` | `.env` của plugin | `true` (tắt tìm PDF thì OCR tắt theo) |
| `NNTM_SEARCH_OCR_PAGES_PER_RUN` | `.env` của plugin | `3` (1–10) |
| `NNTM_TESSERACT_CMD` | môi trường dịch vụ Python | tìm trong PATH, rồi `C:\Program Files\Tesseract-OCR` |
| `NNTM_TESSDATA_DIR` | môi trường dịch vụ Python | thư mục cài Tesseract |
| `NNTM_OCR_DPI` | môi trường dịch vụ Python | `300` |

Thư viện Python mới: **pypdfium2** (Apache/BSD, có sẵn PDFium trong wheel — không
cài poppler, không gọi shell). **Không** dùng PyMuPDF: giấy phép AGPL.

## 4. Kho PDF đã có

PDF tải lên TRƯỚC khi có OCR thì trang scan đã bị bỏ qua không để lại dấu. Sau
khi cài Tesseract, chạy một lần:

```bash
"C:/xampp8_2/php/php.exe" tools/ocr-pdf.php            # xếp hàng mọi PDF, cron làm dần
"C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --chay     # làm tới hết ngay (nên dùng cho kho lớn)
"C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --id=123 --chay
"C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --lam-lai  # xoá chữ OCR cũ, làm lại
```

Dòng lệnh không bị giới hạn thời gian như cron qua web. Cron 3 trang/phút ≈
180 trang/giờ — kho lớn thì dùng `--chay`.

## 5. Đã đo / đã kiểm

**OCR thật** (Tesseract 5.4.0, gói `vie` tessdata_best, máy dev) trên
`tools/test-assets/pdf/scan-mau.pdf` — PDF chỉ có ảnh, chữ xoay lệch + nhiễu,
sinh bằng `tools/tao-pdf-scan-mau.py`:

| | Trang 1 | Trang 2 |
|---|---|---|
| Thời gian | 1,2 s | 1,2 s |
| Giống bản gốc (ký tự) | 98,6 % | 96,3 % |
| Từ đúng hoàn toàn | 93,9 % | 93,8 % |
| Độ tin cậy Tesseract | 95,2 | 93,6 |

Lỗi còn lại gần như toàn là **dấu** — bỏ dấu thì khớp, nên tìm vẫn ra (mục 2).
Scan thật của khách (giấy cũ, chữ in mờ, sách dọc) sẽ **kém hơn** bản mẫu — cần
đo lại khi có file thật. VPS 4 vCPU có thể chậm hơn máy dev 1,5–2 lần.

**Phép thử:**
- Dịch vụ giả lập (19 phép): xếp hàng đúng trang, trang chữ lưu ngay, lô gửi kèm
  file + danh sách trang, bỏ trang tin cậy thấp, không OCR lại, tạm dừng khi 503 /
  khi dịch vụ chết (không gọi dồn, không mất hàng đợi), không để meta rác, xoá file
  → xoá chỉ mục, tìm qua FULLTEXT thật có dấu + không dấu.
- OCR thật qua đường ống PHP (11 phép): 7 câu tìm đều ra đúng trang — kể cả
  "quán chiếu", "bình đẳng" mà OCR đọc sai dấu; hồi quy 17/08 trên trang đánh máy.
- `tools/ocr-pdf.php` với OCR thật: xếp hàng, `--chay`, chạy lại không OCR lại.

⚠️ **Chưa chạy qua FastAPI thật**: máy dev chưa dựng môi trường của
`tools/embed-service` (fastembed, onnxruntime, model CLIP ~350 MB). Hai endpoint
`/pdf/ocr`, `/ocr/khoe` trong `main.py` mới biên dịch, chưa chạy — phần thật sự
làm việc (`ocr.py`) đã chạy thật. Kiểm lại khi dựng dịch vụ trên staging.

## 6. Bẫy đã cắn

- **`--tessdata-dir "đường dẫn"` hỏng trên Windows**: pytesseract giữ nguyên dấu
  ngoặc kép → Tesseract tìm `...tessdata"/vie.traineddata`. Dùng biến môi trường
  `TESSDATA_PREFIX` thay cho tham số.
- **`get_languages()` có `vie` không có nghĩa là nạp được** — lần đầu `/ocr/khoe`
  báo sẵn sàng trong khi mọi trang đều lỗi. Giờ kiểm bằng OCR thử thật.
- **Lỗi nạp gói bị ghi là "quá thời gian"**: `TesseractError` là con của
  `RuntimeError` — phải bắt nó trước.
- `get_post_meta()` trả `''` khi chưa có meta, `(array) ''` là `['']` → đếm ra 1
  trang chờ ma. Dùng `nntm_search_ocr_mang()`.
- Local `.env` đang `NNTM_SEARCH_PDF_ENABLED=false` → OCR cũng tắt theo. Script
  thử đặt biến môi trường cho riêng tiến trình, **không sửa `.env`**.
