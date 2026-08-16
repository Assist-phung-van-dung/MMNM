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

import io
import logging

import numpy as np
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastembed import ImageEmbedding, TextEmbedding
from PIL import Image
from pydantic import BaseModel
from pypdf import PdfReader

import tu_khoa

logger = logging.getLogger("nntm-embed")

# Hai model của cùng một bộ CLIP — vector sinh ra nằm chung một không gian
# nên so sánh chéo ảnh ↔ chữ được. Đổi model thì PHẢI đổi cả hai cùng lúc,
# và phải chạy lại chỉ mục cho toàn bộ thư viện (vector cũ không so được với
# vector mới).
MODEL_ANH = "Qdrant/clip-ViT-B-32-vision"
MODEL_CHU = "Qdrant/clip-ViT-B-32-text"

# Ảnh lớn hơn mức này bị từ chối. CLIP dù sao cũng thu ảnh về 224x224 nên
# nhận ảnh 50MB chỉ tổ tốn RAM và mở đường cho tấn công làm cạn bộ nhớ.
GIOI_HAN_BYTE = 8 * 1024 * 1024

app = FastAPI(title="NNTM Embed", docs_url=None, redoc_url=None)

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
    """Kiểm tra dịch vụ còn sống — dùng cho màn hình quản trị của plugin."""
    return {"ok": True, "model_anh": MODEL_ANH, "model_chu": MODEL_CHU}


@app.post("/embed/image")
async def embed_image(anh: UploadFile = File(...)) -> dict:
    """Sinh vector cho một ảnh."""
    du_lieu = await anh.read()

    if not du_lieu:
        raise HTTPException(status_code=400, detail="anh rong")

    if len(du_lieu) > GIOI_HAN_BYTE:
        raise HTTPException(status_code=413, detail="anh qua lon")

    try:
        # Mở bằng Pillow để xác minh đây thật sự là ảnh, không phải file khác
        # đổi đuôi. convert("RGB") bỏ luôn kênh trong suốt và mọi khối metadata
        # (EXIF, vị trí GPS) — dữ liệu đó không cần cho việc nhúng.
        img = Image.open(io.BytesIO(du_lieu))
        img.verify()
        img = Image.open(io.BytesIO(du_lieu)).convert("RGB")
    except Exception:  # noqa: BLE001 — mọi lỗi giải mã đều quy về một câu trả lời.
        raise HTTPException(status_code=400, detail="khong doc duoc anh") from None

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

    try:
        img = Image.open(io.BytesIO(du_lieu))
        img.verify()
        img = Image.open(io.BytesIO(du_lieu)).convert("RGB")
    except Exception:  # noqa: BLE001
        raise HTTPException(status_code=400, detail="khong doc duoc anh") from None

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
