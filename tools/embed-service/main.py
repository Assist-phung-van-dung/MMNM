"""Dịch vụ sinh vector cho ảnh và chữ — dùng cho tìm kiếm bằng hình ảnh.

Chạy tại chỗ bằng ONNX Runtime (CLIP ViT-B/32). Không dùng PyTorch: bản ONNX
tốn ~300MB RAM thay vì ~1GB, và khởi động khoảng 1 giây thay vì 8 giây. Trên
VPS 8GB đã cõng Soketi + MySQL + PHP-FPM thì khoản đó là đáng kể — xem
docs/04-kien-truc.md mục 5.

CLIP đưa ảnh và chữ về CÙNG một không gian vector, nên cùng một dịch vụ phục
vụ được cả "thả ảnh tìm ảnh" lẫn "gõ chữ tìm ảnh".

CHẠY:
    C:\\Users\\Admin\\nntm-embed\\venv\\Scripts\\python.exe -m uvicorn main:app \\
        --host 127.0.0.1 --port 8765

⚠️ CHỈ ĐƯỢC NGHE TRÊN 127.0.0.1. Đổi sang 0.0.0.0 là bất kỳ ai trong mạng
   gọi được; trên VPS thì thành phơi thẳng ra Internet, người ngoài dùng CPU
   của khách miễn phí và không có gì chặn. Dịch vụ này KHÔNG có xác thực vì
   nó không cần — chỉ WordPress trên cùng máy gọi tới.
"""

from __future__ import annotations

import hashlib
import io
import logging
import time

import numpy as np
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from fastembed import ImageEmbedding, TextEmbedding
from PIL import Image, ImageOps
from pydantic import BaseModel
from pypdf import PdfReader

import tu_khoa

logger = logging.getLogger("nntm-embed")

# Không có basicConfig thì logger "nntm-embed" chảy lên root logger, root logger
# mặc định chỉ in từ WARNING trở lên và không có handler nào cả — mọi
# logger.info() trong file này (kể cả các dòng đã có từ trước: "Nap model anh",
# "Nap model chu", "Ma hoa N tu khoa") ĐANG bị nuốt hoàn toàn, không in ra đâu
# cả. Cấu hình một lần ở đây để log thật sự xuất hiện trên stdout, cùng luồng
# uvicorn đang ghi ra — không cần thêm handler/file riêng.
logging.basicConfig(level=logging.INFO, format="%(asctime)s %(name)s %(levelname)s %(message)s")

# Hai model của cùng một bộ CLIP — vector sinh ra nằm chung một không gian
# nên so sánh chéo ảnh ↔ chữ được. Đổi model thì PHẢI đổi cả hai cùng lúc,
# và phải chạy lại chỉ mục cho toàn bộ thư viện (vector cũ không so được với
# vector mới).
MODEL_ANH = "Qdrant/clip-ViT-B-32-vision"
MODEL_CHU = "Qdrant/clip-ViT-B-32-text"

# Khớp với giá trị mặc định của filter `nntm_search_model` bên plugin PHP
# (includes/embed.php) — cùng một cái tên cho cùng một cặp model, để hai bên
# không lệch nhau khi có ai đó chỉ đổi một bên.
MODEL_VERSION = "clip-vit-b32-onnx"

# Ảnh lớn hơn mức này bị từ chối. CLIP dù sao cũng thu ảnh về 224x224 nên
# nhận ảnh 50MB chỉ tổ tốn RAM và mở đường cho tấn công làm cạn bộ nhớ.
GIOI_HAN_BYTE = 8 * 1024 * 1024

# Giới hạn theo PIXEL, tách khỏi giới hạn byte ở trên: một ảnh nén tốt (PNG
# phẳng màu, ảnh vector rasterize) có thể chỉ vài trăm KB nhưng giải mã ra
# hàng chục triệu pixel — kiểm dung lượng file không bắt được kiểu này.
# Ngưỡng khởi điểm, có thể chỉnh khi biết rõ hơn nhu cầu thật.
GIOI_HAN_CANH = 10_000
GIOI_HAN_MEGAPIXEL = 25_000_000

app = FastAPI(title="NNTM Embed", docs_url=None, redoc_url=None)


@app.middleware("http")
async def ghi_log_co_cau_truc(request, goi_tiep):
    """Một dòng log JSON cho mỗi request, khớp `request_id` với phía WordPress.

    `X-Request-Id` do includes/embed.php (nntm_search_post_file) gắn vào mỗi
    lời gọi — cùng một giá trị xuất hiện ở dòng log bên WordPress
    (nntm_search_log_python_call) và dòng log ở đây, nên đọc log chỉ cần lọc
    theo request_id là ra đủ cả hai phía. Gọi trực tiếp (không qua WordPress,
    ví dụ test tay bằng curl) thì tự sinh id riêng, không lỗi.

    Đặt ở tầng middleware (không phải trong từng endpoint) để áp dụng đều cho
    mọi route hiện tại lẫn route thêm sau, không phải sửa từng hàm. Không ghi
    log thân request/response (ảnh, vector) — chỉ đường dẫn, mã trạng thái, và
    thời gian.
    """
    request_id = request.headers.get("x-request-id", "-")
    bat_dau = time.perf_counter()

    response = await goi_tiep(request)

    thoi_gian_ms = round((time.perf_counter() - bat_dau) * 1000)
    logger.info(
        "request_id=%s method=%s path=%s status=%d duration_ms=%d",
        request_id,
        request.method,
        request.url.path,
        response.status_code,
        thoi_gian_ms,
    )

    return response


_bo_ma_anh: ImageEmbedding | None = None
_bo_ma_chu: TextEmbedding | None = None


def bo_ma_anh() -> ImageEmbedding:
    """Nạp model ảnh một lần rồi dùng lại.

    Lần gọi đầu tiên sẽ tải model (~350MB) về bộ nhớ đệm của máy.
    """
    global _bo_ma_anh
    if _bo_ma_anh is None:
        logger.info("Nap model anh: %s", MODEL_ANH)
        _bo_ma_anh = ImageEmbedding(model_name=MODEL_ANH)
    return _bo_ma_anh


def bo_ma_chu() -> TextEmbedding:
    """Nạp model chữ một lần rồi dùng lại."""
    global _bo_ma_chu
    if _bo_ma_chu is None:
        logger.info("Nap model chu: %s", MODEL_CHU)
        _bo_ma_chu = TextEmbedding(model_name=MODEL_CHU)
    return _bo_ma_chu


class YeuCauChu(BaseModel):
    """Thân yêu cầu cho /embed/text."""

    chu: str


@app.get("/khoe")
def khoe() -> dict:
    """Kiểm tra dịch vụ còn sống — dùng cho màn hình quản trị của plugin.

    Cố ý không đụng vào ý nghĩa cũ của endpoint này (còn được gọi từ nơi khác,
    xem docs/10-ban-giao-tim-kiem.md): trả 200 ngay khi tiến trình còn chạy,
    KỂ CẢ khi model chưa nạp xong. "Còn sống" khác với "sẵn sàng suy luận" —
    xem /ready.
    """
    return {"ok": True, "model_anh": MODEL_ANH, "model_chu": MODEL_CHU}


# _san_sang chỉ thành True sau khi startup() nạp xong CẢ HAI model và chạy thử
# thành công. Không dùng None làm giá trị lỗi — biến rõ ràng để /ready đọc.
_san_sang = False
_loi_khoi_dong: str | None = None


def _khoa_phien_ban() -> str:
    """Hash bảng từ khoá — đổi bảng từ khoá (thêm/sửa/xoá dòng) thì đổi luôn giá
    trị này, phòng trường hợp cache phía sau (Phase 2) đối chiếu theo giá trị
    này mà không hay biết bảng đã đổi.
    """
    tho = repr(tu_khoa.TU_KHOA).encode("utf-8")
    return hashlib.sha256(tho).hexdigest()[:12]


@app.on_event("startup")
def khoi_dong_va_lam_nong() -> None:
    """Nạp cả hai model và chạy thử một lượt nhỏ ngay khi service khởi động.

    KHÔNG để request thật đầu tiên của người dùng gánh trọn chi phí cold-start
    (tải + khởi tạo phiên ONNX Runtime). Nạp model ảnh, nạp model chữ, rồi mã
    hoá luôn bảng từ khoá — vector_tu_khoa() bên dưới vốn cũng chỉ chạy một
    lần rồi giữ lại, nên gọi ở đây coi như làm nóng cả bảng từ khoá lẫn model
    chữ trong cùng một bước.

    Bắt lỗi RỘNG có chủ đích: bất kỳ lỗi nạp model nào (thiếu RAM, model
    không tải được, phiên bản ONNX Runtime không tương thích...) đều phải làm
    /ready báo sai, không được để dịch vụ báo "khoẻ" trong lúc chưa suy luận
    được. Log rõ để người vận hành thấy ngay trong log, không nuốt lỗi.
    """
    global _san_sang, _loi_khoi_dong

    try:
        anh_nong = Image.new("RGB", (8, 8), color=(128, 128, 128))
        next(iter(bo_ma_anh().embed([anh_nong])))
        vector_tu_khoa()  # nạp bo_ma_chu() bên trong, mã hoá luôn 61 từ khoá.
        _san_sang = True
        logger.info("Khoi dong xong, model da san sang nhan yeu cau that.")
    except Exception as loi:  # noqa: BLE001 — mọi lỗi khởi động đều phải chặn readiness.
        _loi_khoi_dong = str(loi)
        logger.error("Khoi dong that bai, /ready se bao chua san sang: %s", loi)


@app.get("/ready")
def san_sang() -> JSONResponse:
    """Sẵn sàng suy luận thật hay chưa — dùng cho health check tự động.

    Khác /khoe: chỉ trả 200 sau khi model đã nạp xong VÀ chạy thử thành công.
    Không lộ đường dẫn nội bộ hay traceback — chỉ nói đã sẵn sàng hay chưa.
    """
    if not _san_sang:
        return JSONResponse(
            status_code=503,
            content={
                "ok": False,
                "ready": False,
                "model_loaded": False,
                "model_version": MODEL_VERSION,
                "keyword_version": _khoa_phien_ban(),
            },
        )

    return JSONResponse(
        status_code=200,
        content={
            "ok": True,
            "ready": True,
            "model_loaded": True,
            "model_version": MODEL_VERSION,
            "keyword_version": _khoa_phien_ban(),
        },
    )


def _giai_ma_anh_an_toan(du_lieu: bytes) -> Image.Image:
    """Mở, xác minh, giới hạn kích thước, và sửa hướng EXIF cho một ảnh tải lên.

    Dùng chung cho /embed/image và /anh/tu-khoa — trước đây hai endpoint chép
    y hệt khối mở-ảnh này; sửa một chỗ mà quên chỗ kia là để hai đường xử lý
    lệch nhau.

    Kiểm KÍCH THƯỚC ngay sau `Image.open()` (Pillow chỉ đọc header, chưa giải
    mã hết pixel) — TRƯỚC `convert("RGB")` (giải mã đầy đủ). Giới hạn byte ở
    trên (GIOI_HAN_BYTE) không chặn được ảnh nén tốt nhưng khai kích thước
    khổng lồ; việc đó phải chặn ở đây, trước khi tốn công giải mã.
    """
    try:
        # Mở bằng Pillow để xác minh đây thật sự là ảnh, không phải file khác
        # đổi đuôi.
        img = Image.open(io.BytesIO(du_lieu))
        img.verify()
        img = Image.open(io.BytesIO(du_lieu))
    except Exception:  # noqa: BLE001 — mọi lỗi giải mã đều quy về một câu trả lời.
        raise HTTPException(status_code=400, detail="khong doc duoc anh") from None

    rong, cao = img.size

    if rong > GIOI_HAN_CANH or cao > GIOI_HAN_CANH or rong * cao > GIOI_HAN_MEGAPIXEL:
        raise HTTPException(status_code=413, detail="anh qua lon")

    # Ảnh chụp bằng điện thoại thường lưu pixel nằm ngang kèm cờ EXIF "xoay
    # 90°/180°" — bỏ qua cờ này thì model nhúng ảnh sai hướng so với ảnh
    # người dùng thấy trên máy họ. Phải làm TRƯỚC convert("RGB"), vì
    # convert() không tự đọc EXIF.
    img = ImageOps.exif_transpose(img)

    # convert("RGB") bỏ luôn kênh trong suốt và mọi khối metadata còn lại
    # (EXIF, vị trí GPS) — dữ liệu đó không cần cho việc nhúng.
    return img.convert("RGB")


@app.post("/embed/image")
async def embed_image(anh: UploadFile = File(...)) -> dict:
    """Sinh vector cho một ảnh."""
    du_lieu = await anh.read()

    if not du_lieu:
        raise HTTPException(status_code=400, detail="anh rong")

    if len(du_lieu) > GIOI_HAN_BYTE:
        raise HTTPException(status_code=413, detail="anh qua lon")

    img = _giai_ma_anh_an_toan(du_lieu)

    vector = next(iter(bo_ma_anh().embed([img])))

    return {"vector": [float(x) for x in vector], "dim": len(vector), "model": MODEL_ANH}


@app.post("/embed/text")
def embed_text(yeu_cau: YeuCauChu) -> dict:
    """Sinh vector cho một đoạn chữ, cùng không gian với vector ảnh."""
    chu = yeu_cau.chu.strip()

    if not chu:
        raise HTTPException(status_code=400, detail="chu rong")

    if len(chu) > 500:
        chu = chu[:500]

    vector = next(iter(bo_ma_chu().embed([chu])))

    return {"vector": [float(x) for x in vector], "dim": len(vector), "model": MODEL_CHU}


# ---------------------------------------------------------------------------
# Đọc nội dung ảnh ra từ khoá.
# ---------------------------------------------------------------------------

_vector_tu_khoa: np.ndarray | None = None


def vector_tu_khoa() -> np.ndarray:
    """Mã hoá toàn bộ bảng từ khoá MỘT LẦN rồi giữ lại.

    Bảng có ~60 mục; mã hoá lại cho mỗi lượt tìm là lãng phí gấp 60 lần.
    """
    global _vector_tu_khoa
    if _vector_tu_khoa is None:
        logger.info("Ma hoa %d tu khoa", len(tu_khoa.TU_KHOA))
        v = np.array(list(bo_ma_chu().embed(tu_khoa.cau_tieng_anh())), dtype=np.float32)
        # Chuẩn hoá sẵn để lúc so chỉ cần nhân ma trận.
        _vector_tu_khoa = v / np.linalg.norm(v, axis=1, keepdims=True)
    return _vector_tu_khoa


@app.post("/anh/tu-khoa")
async def anh_tu_khoa(anh: UploadFile = File(...), so_luong: int = 5) -> dict:
    """Đọc ảnh và trả về những từ khoá tiếng Việt mô tả nó.

    Đây là cách "xem ảnh nói gì rồi đi tìm bằng chữ": ảnh mặt trời cho ra từ
    khoá "mặt trời", rồi WordPress tìm như thể người dùng tự gõ từ đó.
    """
    du_lieu = await anh.read()

    if not du_lieu:
        raise HTTPException(status_code=400, detail="anh rong")

    if len(du_lieu) > GIOI_HAN_BYTE:
        raise HTTPException(status_code=413, detail="anh qua lon")

    img = _giai_ma_anh_an_toan(du_lieu)

    v_anh = np.array(next(iter(bo_ma_anh().embed([img]))), dtype=np.float32)
    v_anh = v_anh / np.linalg.norm(v_anh)

    # Cả hai đã chuẩn hoá nên tích vô hướng chính là cosine.
    diem = vector_tu_khoa() @ v_anh

    # Softmax để biết mức độ ÁP ĐẢO của từ đứng đầu so với phần còn lại.
    # Cosine trần không nói lên điều đó: ảnh nào cũng ra 0,2–0,3 với mọi nhãn.
    # Nhân 100 là hệ số nhiệt độ chuẩn của CLIP.
    mu = np.exp((diem - diem.max()) * 100.0)
    xac_suat = mu / mu.sum()

    thu_tu = np.argsort(-xac_suat)[: max(1, min(so_luong, 10))]
    vi = tu_khoa.tu_tieng_viet()

    # Chỉ giữ từ khoá đủ nổi trội. Ngưỡng tương đối (so với từ đứng đầu) thay
    # vì ngưỡng tuyệt đối: ảnh rõ ràng thì một từ chiếm gần hết xác suất, ảnh
    # mơ hồ thì phân tán — lấy tương đối mới lọc đúng cả hai trường hợp.
    dan_dau = float(xac_suat[thu_tu[0]])
    ket_qua = [
        {
            "tu": vi[i],
            "diem": round(float(xac_suat[i]), 4),
            "cosine": round(float(diem[i]), 4),
        }
        for i in thu_tu
        if float(xac_suat[i]) >= dan_dau * 0.25
    ]

    return {"tu_khoa": ket_qua, "tong_nhan": len(tu_khoa.TU_KHOA)}


# ---------------------------------------------------------------------------
# Đọc chữ trong file PDF.
# ---------------------------------------------------------------------------


@app.post("/pdf/text")
async def pdf_text(tep: UploadFile = File(...)) -> dict:
    """Trích chữ của từng trang trong một file PDF.

    Làm ở đây chứ không gọi pdftotext bằng shell_exec() từ PHP: bỏ được hẳn
    một lệnh gọi shell (bề mặt tấn công, và phải cấu hình đường dẫn binary
    khác nhau cho Windows với Linux), lại không phải cài thêm poppler.

    Trang nào trả về gần như rỗng là trang ảnh scan — chỗ đó về sau cắm
    Tesseract vào, đánh dấu bằng "nguon": "trong".
    """
    du_lieu = await tep.read()

    if not du_lieu:
        raise HTTPException(status_code=400, detail="tep rong")

    try:
        doc = PdfReader(io.BytesIO(du_lieu))
    except Exception:  # noqa: BLE001
        raise HTTPException(status_code=400, detail="khong doc duoc pdf") from None

    trang = []

    for so, t in enumerate(doc.pages, start=1):
        try:
            chu = (t.extract_text() or "").strip()
        except Exception:  # noqa: BLE001 — một trang hỏng không được giết cả cuốn.
            logger.warning("Trang %d khong trich duoc", so)
            chu = ""

        trang.append(
            {
                "trang": so,
                "chu": chu,
                "nguon": "text" if len(chu) >= 40 else "trong",
            }
        )

    return {"so_trang": len(trang), "trang": trang}
