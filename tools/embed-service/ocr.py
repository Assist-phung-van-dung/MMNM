"""Nhận dạng chữ (OCR) tiếng Việt cho trang PDF scan — khảo sát câu 11–12.

Chạy Tesseract tại chỗ, không gửi file ra dịch vụ ngoài (chủ dự án đã chốt
KHÔNG dùng Google Vision — docs/10-ban-giao-tim-kiem.md mục 10).

Hai thư viện, nạp LƯỜI (chỉ khi gọi tới) để dịch vụ vẫn khởi động được trên máy
chưa cài OCR — tìm bằng ảnh và trích chữ PDF thường vẫn chạy, OCR chỉ báo
"chưa sẵn sàng":

- pypdfium2: vẽ trang PDF thành ảnh. Giấy phép Apache/BSD, có sẵn binary
  PDFium trong wheel — không phải cài poppler, không gọi shell. KHÔNG dùng
  PyMuPDF: giấy phép AGPL.
- pytesseract: gọi chương trình `tesseract` (phải cài riêng, kèm gói ngôn ngữ
  `vie`). Linux: `apt install tesseract-ocr tesseract-ocr-vie`.

Biến môi trường:
    NNTM_TESSERACT_CMD   đường dẫn tesseract nếu không nằm trong PATH
                         (Windows: C:\\Program Files\\Tesseract-OCR\\tesseract.exe)
    NNTM_TESSDATA_DIR    thư mục chứa vie.traineddata nếu không để trong thư
                         mục cài Tesseract (không cần quyền admin để thêm gói)
    NNTM_OCR_LANG        mặc định "vie"
    NNTM_OCR_DPI         mặc định 300 — thấp hơn thì dấu tiếng Việt dễ sai
"""

from __future__ import annotations

import logging
import math
import os
import shutil
import time
import unicodedata

logger = logging.getLogger("nntm-embed.ocr")

NGON_NGU = os.environ.get("NNTM_OCR_LANG", "vie")
THU_MUC_TESSDATA = os.environ.get("NNTM_TESSDATA_DIR", "").strip()

# Chỉ đường tới gói ngôn ngữ bằng biến môi trường TESSDATA_PREFIX (tiến trình
# tesseract con thừa hưởng) chứ KHÔNG bằng tham số --tessdata-dir: trên Windows
# pytesseract tách tham số mà giữ nguyên dấu ngoặc kép, đường dẫn thành
# `...tessdata"/vie.traineddata` và Tesseract không nạp được gói — đã gặp thật.
if THU_MUC_TESSDATA:
    os.environ["TESSDATA_PREFIX"] = THU_MUC_TESSDATA

# Kết quả kiểm tra trạng thái được nhớ ít lâu: mỗi lần kiểm có chạy thử OCR.
_NHO_TRANG_THAI: dict = {"luc": 0.0, "kq": None}
THOI_GIAN_NHO_TRANG_THAI = 60
DPI = int(os.environ.get("NNTM_OCR_DPI", "300"))

# Mỗi lần gọi tối đa bấy nhiêu trang: WordPress gọi theo lô nhỏ từ cron, một
# lô quá dài sẽ vượt thời gian chờ của PHP.
TOI_DA_TRANG_MOI_LAN = 10

# Trang A4 ở 300 DPI ≈ 8,7 triệu điểm ảnh. Trang khổ lớn bất thường (bản vẽ,
# poster) thì hạ độ phân giải cho vừa ngưỡng thay vì ăn hết RAM.
GIOI_HAN_DIEM_ANH = 40_000_000

# Tesseract kẹt trên một trang (ảnh nhiễu nặng) thì bỏ trang đó, không treo cả lô.
THOI_GIAN_TOI_DA_MOI_TRANG = 120


def _duong_dan_tesseract() -> str | None:
    tu_cau_hinh = os.environ.get("NNTM_TESSERACT_CMD", "").strip()
    if tu_cau_hinh:
        return tu_cau_hinh if os.path.isfile(tu_cau_hinh) else None

    tim = shutil.which("tesseract")
    if tim:
        return tim

    # Bộ cài Windows (UB Mannheim) mặc định không thêm vào PATH.
    mac_dinh_win = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
    return mac_dinh_win if os.path.isfile(mac_dinh_win) else None


def trang_thai() -> dict:
    """OCR có dùng được không, và vì sao nếu không (nhớ kết quả 60 giây)."""
    if _NHO_TRANG_THAI["kq"] is not None and time.monotonic() - _NHO_TRANG_THAI["luc"] < THOI_GIAN_NHO_TRANG_THAI:
        return _NHO_TRANG_THAI["kq"]

    kq = _kiem_tra()
    _NHO_TRANG_THAI.update(luc=time.monotonic(), kq=kq)
    return kq


def _kiem_tra() -> dict:
    kq: dict = {"san_sang": False, "ngon_ngu": NGON_NGU, "dpi": DPI}

    try:
        import pypdfium2  # noqa: F401
    except ImportError:
        kq["ly_do"] = "thieu goi pypdfium2 (pip install pypdfium2)"
        return kq

    try:
        import pytesseract
    except ImportError:
        kq["ly_do"] = "thieu goi pytesseract (pip install pytesseract)"
        return kq

    lenh = _duong_dan_tesseract()
    if not lenh:
        kq["ly_do"] = "chua cai tesseract hoac khong tim thay (dat NNTM_TESSERACT_CMD)"
        return kq

    pytesseract.pytesseract.tesseract_cmd = lenh

    try:
        kq["phien_ban"] = str(pytesseract.get_tesseract_version())
        co_san = sorted(pytesseract.get_languages(config=""))
    except Exception as loi:  # noqa: BLE001
        kq["ly_do"] = f"khong chay duoc tesseract: {loi}"
        return kq

    kq["ngon_ngu_co_san"] = co_san

    if NGON_NGU not in co_san:
        kq["ly_do"] = f"thieu goi ngon ngu '{NGON_NGU}' (Linux: apt install tesseract-ocr-vie)"
        return kq

    # Có tên trong danh sách chưa chắc NẠP được (tệp hỏng, sai đường dẫn) — chạy
    # thử thật trên một ảnh nhỏ.
    try:
        from PIL import Image

        pytesseract.image_to_string(Image.new("L", (64, 32), 255), lang=NGON_NGU, timeout=30)
    except Exception as loi:  # noqa: BLE001
        kq["ly_do"] = f"khong nap duoc goi '{NGON_NGU}': {str(loi)[:200]}"
        return kq

    kq["san_sang"] = True
    return kq


def _ghep_chu(du_lieu: dict) -> tuple[str, float, int]:
    """Ghép kết quả image_to_data thành văn bản giữ xuống dòng, kèm độ tin cậy.

    Dùng image_to_data (chữ + độ tin cậy trong MỘT lần chạy) thay vì gọi
    image_to_string rồi image_to_data — chạy Tesseract hai lần là gấp đôi thời gian.
    """
    dong: dict[tuple, list[str]] = {}
    thu_tu: list[tuple] = []
    tong_diem = 0.0
    tong_ky_tu = 0
    so_tu = 0

    for i, tu in enumerate(du_lieu["text"]):
        tu = (tu or "").strip()
        try:
            diem = float(du_lieu["conf"][i])
        except (TypeError, ValueError):
            diem = -1.0

        if not tu or diem < 0:
            continue

        khoa = (du_lieu["block_num"][i], du_lieu["par_num"][i], du_lieu["line_num"][i])
        if khoa not in dong:
            dong[khoa] = []
            thu_tu.append(khoa)
        dong[khoa].append(tu)

        # Trọng số theo độ dài: một chữ "a" sai không nên kéo điểm cả trang
        # xuống ngang một từ dài sai.
        tong_diem += diem * len(tu)
        tong_ky_tu += len(tu)
        so_tu += 1

    dong_chu: list[str] = []
    doan_truoc = None
    for khoa in thu_tu:
        doan = khoa[:2]
        if doan_truoc is not None and doan != doan_truoc:
            dong_chu.append("")  # Dòng trống giữa hai đoạn.
        dong_chu.append(" ".join(dong[khoa]))
        doan_truoc = doan

    chu = unicodedata.normalize("NFC", "\n".join(dong_chu).strip())
    tin_cay = (tong_diem / tong_ky_tu) if tong_ky_tu else 0.0

    return chu, tin_cay, so_tu


def nhan_dang(du_lieu_pdf: bytes, cac_trang: list[int]) -> list[dict]:
    """OCR các trang (đánh số từ 1). Một trang lỗi không làm hỏng cả lô."""
    import pypdfium2 as pdfium
    import pytesseract

    pytesseract.pytesseract.tesseract_cmd = _duong_dan_tesseract() or "tesseract"

    tai_lieu = pdfium.PdfDocument(du_lieu_pdf)
    ket_qua: list[dict] = []

    try:
        tong = len(tai_lieu)

        for so in cac_trang:
            if so < 1 or so > tong:
                ket_qua.append({"trang": so, "chu": "", "nguon": "ocr", "loi": "trang khong ton tai"})
                continue

            trang = tai_lieu[so - 1]
            try:
                rong, cao = trang.get_size()  # Đơn vị point (1/72 inch).
                ty_le = DPI / 72.0
                if rong * cao * ty_le * ty_le > GIOI_HAN_DIEM_ANH:
                    ty_le = math.sqrt(GIOI_HAN_DIEM_ANH / (rong * cao))

                anh = trang.render(scale=ty_le, grayscale=True).to_pil()

                du_lieu = pytesseract.image_to_data(
                    anh,
                    lang=NGON_NGU,
                    # --oem 1: chỉ dùng mạng LSTM (gói vie của tessdata_best chỉ có LSTM).
                    # --psm 3: tự phân tích bố cục trang — trang sách nhiều đoạn, có tiêu đề.
                    config="--oem 1 --psm 3",
                    output_type=pytesseract.Output.DICT,
                    timeout=THOI_GIAN_TOI_DA_MOI_TRANG,
                )
                chu, tin_cay, so_tu = _ghep_chu(du_lieu)

                ket_qua.append(
                    {
                        "trang": so,
                        "chu": chu,
                        "nguon": "ocr",
                        "do_tin_cay": round(tin_cay, 1),
                        "so_tu": so_tu,
                    }
                )
            except pytesseract.TesseractError as loi:
                logger.warning("OCR trang %d: tesseract bao loi: %s", so, loi)
                ket_qua.append({"trang": so, "chu": "", "nguon": "ocr", "loi": "tesseract bao loi"})
            except RuntimeError as loi:
                # pytesseract báo quá thời gian bằng RuntimeError trần (TesseractError
                # cũng là RuntimeError nên phải bắt nó trước, ở trên).
                la_qua_gio = "timeout" in str(loi).lower()
                logger.warning("OCR trang %d %s: %s", so, "qua thoi gian" if la_qua_gio else "loi", loi)
                ket_qua.append({"trang": so, "chu": "", "nguon": "ocr", "loi": "qua thoi gian" if la_qua_gio else "khong nhan dang duoc"})
            except Exception as loi:  # noqa: BLE001
                logger.warning("OCR trang %d loi: %s", so, loi)
                ket_qua.append({"trang": so, "chu": "", "nguon": "ocr", "loi": "khong nhan dang duoc"})
            finally:
                trang.close()
    finally:
        tai_lieu.close()

    return ket_qua
