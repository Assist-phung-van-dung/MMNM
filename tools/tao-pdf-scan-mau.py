"""Sinh một file PDF "scan" tiếng Việt để thử OCR (khảo sát câu 11–12).

PDF sinh ra CHỈ có ảnh, không có lớp chữ — giống bản chụp từ sách giấy: vẽ
chữ lên ảnh xám, xoay lệch nhẹ, rắc nhiễu, rồi đóng thành PDF. Trích chữ
thường (pypdf) sẽ ra rỗng, chỉ OCR mới đọc được.

Đây là DỮ LIỆU THỬ, không phải nội dung thật của khách. Chỉ cần Pillow:

    python tools/tao-pdf-scan-mau.py [duong-dan-font.ttf]

Ghi ra tools/test-assets/pdf/scan-mau.pdf. Câu trong NOI_DUNG đã biết trước nên
người test biết OCR phải ra chữ gì — ví dụ tìm "vô thường" phải ra trang 1,
tìm "tứ vô lượng tâm" phải ra trang 2.
"""

from __future__ import annotations

import pathlib
import random
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont

RA = pathlib.Path(__file__).parent / "test-assets" / "pdf" / "scan-mau.pdf"

# Font có đủ dấu tiếng Việt. Windows có sẵn Times New Roman; Linux dùng DejaVu.
FONT_UNG_VIEN = [
    r"C:\Windows\Fonts\times.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf",
    "/System/Library/Fonts/Supplemental/Times New Roman.ttf",
]

NOI_DUNG = [
    [
        "Bài học về vô thường",
        "",
        "Mọi pháp hữu vi đều vô thường, như mây trôi trên đỉnh núi,",
        "như sương mai đọng trên lá cỏ. Người tu học quán chiếu vô",
        "thường không phải để buồn bã, mà để trân quý từng khoảnh",
        "khắc đang có mặt, sống trọn vẹn với hơi thở và bước chân.",
        "",
        "Khi hiểu rằng tất cả đều thay đổi, ta bớt nắm giữ, bớt",
        "khổ đau, và mở lòng rộng rãi hơn với những người xung quanh.",
    ],
    [
        "Tứ vô lượng tâm",
        "",
        "Từ, bi, hỷ, xả là bốn tâm rộng lớn không giới hạn. Từ là",
        "đem niềm vui đến cho người; bi là làm vơi nỗi khổ của người;",
        "hỷ là vui với niềm vui của người khác; xả là buông bỏ mọi",
        "phân biệt thương ghét, bình đẳng với tất cả chúng sinh.",
        "",
        "Nuôi dưỡng tứ vô lượng tâm mỗi ngày là con đường chuyển",
        "hóa phiền não thành an lạc, ngay trong đời sống thường nhật.",
    ],
]

# A4 ở 200 DPI — độ phân giải máy scan văn phòng hay dùng.
RONG, CAO = 1654, 2339


def tim_font(tu_tham_so: str | None) -> str:
    for duong_dan in ([tu_tham_so] if tu_tham_so else []) + FONT_UNG_VIEN:
        if duong_dan and pathlib.Path(duong_dan).is_file():
            return duong_dan
    sys.exit("Khong tim thay font co dau tieng Viet — truyen duong dan .ttf lam tham so.")


def ve_trang(dong: list[str], font_path: str, hat_giong: int) -> Image.Image:
    ngau_nhien = random.Random(hat_giong)
    anh = Image.new("L", (RONG, CAO), 246)
    ve = ImageDraw.Draw(anh)

    tieu_de = ImageFont.truetype(font_path, 64)
    than = ImageFont.truetype(font_path, 42)

    y = 220
    for i, chu in enumerate(dong):
        ve.text((180, y), chu, fill=28, font=tieu_de if i == 0 else than)
        y += 110 if i == 0 else 66

    # Lệch trang nhẹ như đặt sách không thẳng trên máy scan.
    anh = anh.rotate(ngau_nhien.uniform(-0.8, 0.8), resample=Image.BICUBIC, fillcolor=246)

    # Nhiễu hạt + mờ nhẹ.
    diem = anh.load()
    for _ in range(RONG * CAO // 900):
        x, yy = ngau_nhien.randrange(RONG), ngau_nhien.randrange(CAO)
        diem[x, yy] = ngau_nhien.choice((120, 180, 255))
    return anh.filter(ImageFilter.GaussianBlur(0.6))


def main() -> None:
    font_path = tim_font(sys.argv[1] if len(sys.argv) > 1 else None)
    trang = [ve_trang(dong, font_path, i) for i, dong in enumerate(NOI_DUNG)]

    RA.parent.mkdir(parents=True, exist_ok=True)
    trang[0].save(RA, "PDF", resolution=200.0, save_all=True, append_images=trang[1:], quality=70)
    print(f"Da ghi {RA} ({len(trang)} trang, chi co anh, khong co lop chu)")


if __name__ == "__main__":
    main()
