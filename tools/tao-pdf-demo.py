"""Sinh vài file PDF tiếng Việt để thử tính năng tìm trong nội dung PDF.

Đây là DỮ LIỆU THỬ, không phải nội dung thật của khách. Sinh ra để chứng minh
đường ống chạy: tải PDF lên Thư viện → trích chữ từng trang → tìm ra đúng trang.

    C:\\Users\\Admin\\nntm-embed\\venv\\Scripts\\python.exe tools/tao-pdf-demo.py

File ghi vào tools/pdf-demo/. Xoá thư mục đó là sạch.
"""

from __future__ import annotations

import pathlib

from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import PageBreak, Paragraph, SimpleDocTemplate, Spacer

THU_MUC = pathlib.Path(__file__).parent / "pdf-demo"

# Font mặc định của reportlab (Helvetica) KHÔNG có dấu tiếng Việt — chữ sẽ ra
# ô vuông hoặc mất dấu, và lúc trích chữ ra để lập chỉ mục sẽ sai luôn. Phải
# nhúng một font TrueType có đủ dấu; Windows nào cũng có sẵn mấy font này.
FONT_UNG_VIEN = [
    ("SegoeUI", r"C:\Windows\Fonts\segoeui.ttf"),
    ("Arial", r"C:\Windows\Fonts\arial.ttf"),
    ("Tahoma", r"C:\Windows\Fonts\tahoma.ttf"),
]


def dang_ky_font() -> str:
    """Nạp font đầu tiên tìm được, trả về tên đã đăng ký."""
    for ten, duong_dan in FONT_UNG_VIEN:
        if pathlib.Path(duong_dan).is_file():
            pdfmetrics.registerFont(TTFont(ten, duong_dan))
            return ten
    raise SystemExit("Khong tim thay font TrueType nao co dau tieng Viet.")


# Mỗi cuốn: (tên file, tiêu đề, [ (tiêu đề trang, [đoạn văn...]) ... ])
# Từ khoá được rải CÓ CHỦ Ý vào những trang khác nhau để chứng minh phần
# "tìm ra đúng trang mấy": ví dụ "mặt trời" chỉ xuất hiện ở trang 3 cuốn 1.
SACH = [
    (
        "nghi-quy-tung-niem-hang-ngay.pdf",
        "Nghi Quỹ Tụng Niệm Hằng Ngày",
        [
            (
                "Lời mở",
                [
                    "Nghi quỹ này dành cho hành giả tụng niệm mỗi sớm mai và mỗi chiều tối, "
                    "giữ cho tâm được liên tục, không đứt quãng giữa hai thời công phu.",
                    "Người mới bắt đầu nên đọc chậm, hiểu nghĩa từng câu trước khi thuộc lòng.",
                ],
            ),
            (
                "Chuẩn bị đàn tràng",
                [
                    "Bàn thờ đặt nơi cao ráo, sạch sẽ. Thắp một nén hương, một ngọn nến, "
                    "dâng một chén nước trong.",
                    "Chuông nhỏ đặt bên tay phải. Chuỗi hạt để bên tay trái.",
                ],
            ),
            (
                "Thời công phu buổi sớm",
                [
                    "Khi mặt trời vừa lên khỏi rặng núi, hành giả ngồi thiền, lưng thẳng, "
                    "mắt khép hờ, theo dõi hơi thở vào ra.",
                    "Ánh mặt trời buổi sớm chiếu qua khung cửa là dấu hiệu bắt đầu thời khoá. "
                    "Tụng ba biến, mỗi biến dứt bằng một tiếng chuông.",
                ],
            ),
            (
                "Thời công phu buổi chiều",
                [
                    "Buổi chiều, khi hoàng hôn xuống, tụng phẩm hồi hướng. "
                    "Hồi hướng cho cha mẹ nhiều đời, cho chúng sinh trong sáu nẻo.",
                    "Kết thúc bằng ba lạy, thân tâm đều lặng.",
                ],
            ),
            (
                "Ngày rằm và mồng một",
                [
                    "Hai ngày này tụng thêm phẩm sám hối. Đạo tràng tập trung tại chánh điện "
                    "của ngôi chùa, chư tăng và cư sĩ cùng tụng.",
                    "Hoa sen dâng cúng nên chọn búp còn khép, để nở dần trong suốt buổi lễ.",
                ],
            ),
            (
                "Phụ lục — cách gõ chuông",
                [
                    "Ba tiếng đầu báo hiệu nhập đàn. Một tiếng giữa mỗi biến. "
                    "Ba tiếng cuối báo xả đàn.",
                    "Tiếng chuông phải để ngân hết mới gõ tiếng kế tiếp.",
                ],
            ),
        ],
    ),
    (
        "luan-ve-tu-dieu-de.pdf",
        "Luận Về Tứ Diệu Đế",
        [
            (
                "Dẫn nhập",
                [
                    "Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên thuyết tại vườn Lộc Uyển, "
                    "sau khi thành đạo dưới cội cây bồ đề.",
                    "Bốn sự thật ấy không phải là tín điều để tin, mà là điều để tự mình kiểm chứng.",
                ],
            ),
            (
                "Khổ đế",
                [
                    "Sự thật thứ nhất nói về khổ. Sinh là khổ, già là khổ, bệnh là khổ, chết là khổ.",
                    "Cái khổ vi tế nhất là khổ do các hành biến đổi không ngừng, không có gì đứng yên để nắm giữ.",
                ],
            ),
            (
                "Tập đế",
                [
                    "Sự thật thứ hai chỉ ra nguyên nhân: ái, thủ, hữu. Càng nắm chặt càng đau.",
                    "Nguyên nhân của khổ nằm bên trong, không nằm ở hoàn cảnh bên ngoài.",
                ],
            ),
            (
                "Diệt đế",
                [
                    "Sự thật thứ ba khẳng định khổ có thể chấm dứt. Đây là điểm khiến giáo pháp "
                    "khác với một lời than thở về cuộc đời.",
                    "Niết bàn không phải một nơi chốn để đi tới, mà là trạng thái vắng mặt của tham sân si.",
                ],
            ),
            (
                "Đạo đế",
                [
                    "Sự thật thứ tư là con đường tám nhánh: chánh kiến, chánh tư duy, chánh ngữ, "
                    "chánh nghiệp, chánh mạng, chánh tinh tấn, chánh niệm, chánh định.",
                    "Tám nhánh này không đi tuần tự mà nuôi dưỡng lẫn nhau.",
                ],
            ),
            (
                "Thiền quán trong rừng",
                [
                    "Người xưa thường vào rừng thiền quán, nương bóng cây mà nhìn lại tâm mình.",
                    "Rừng thông buổi sớm, sương còn đọng trên lá, là nơi thuận tiện cho việc quán niệm hơi thở.",
                ],
            ),
            (
                "Ứng dụng trong đời thường",
                [
                    "Không phải ai cũng vào rừng được. Ngay giữa phố xá, mỗi lần dừng lại "
                    "nhận biết hơi thở cũng là một thời công phu ngắn.",
                    "Bố thí ba-la-mật bắt đầu từ việc nhường một chỗ ngồi, không cần đợi có nhiều tiền.",
                ],
            ),
            (
                "Kết",
                [
                    "Bốn sự thật này soi sáng lẫn nhau. Hiểu một cách trọn vẹn thì ba cái kia tự sáng.",
                ],
            ),
        ],
    ),
    (
        "kinh-phap-cu-trich-giang.pdf",
        "Kinh Pháp Cú — Trích Giảng",
        [
            (
                "Phẩm Song Yếu",
                [
                    "Tâm dẫn đầu các pháp. Tâm làm chủ, tâm tạo tác.",
                    "Nói hay làm với tâm ô nhiễm, khổ đau sẽ theo sau như bánh xe theo chân con vật kéo.",
                ],
            ),
            (
                "Phẩm Không Phóng Dật",
                [
                    "Không phóng dật là con đường bất tử. Phóng dật là con đường đưa đến cái chết.",
                    "Người tỉnh giác không chết, kẻ phóng dật như đã chết rồi.",
                ],
            ),
            (
                "Phẩm Hoa",
                [
                    "Như từ một đống hoa có thể kết thành nhiều tràng hoa, "
                    "người sinh ra ở đời cũng nên làm nhiều việc lành.",
                    "Hương của hoa sen bay theo chiều gió, nhưng hương của người đức hạnh "
                    "bay ngược cả chiều gió.",
                ],
            ),
            (
                "Phẩm Ngàn",
                [
                    "Dù tụng ngàn câu vô nghĩa, không bằng một câu có nghĩa nghe xong được an tịnh.",
                    "Thắng ngàn quân địch ngoài trận mạc không bằng tự thắng chính mình.",
                ],
            ),
            (
                "Phẩm Đường Đi",
                [
                    "Con đường tám nhánh là con đường cao thượng nhất.",
                    "Hãy tự mình nỗ lực, Như Lai chỉ là bậc chỉ đường.",
                ],
            ),
        ],
    ),
]


def tao_mot_cuon(ten_file: str, tieu_de: str, trang: list, font: str) -> pathlib.Path:
    """Dựng một file PDF, mỗi mục là một trang riêng."""
    duong_dan = THU_MUC / ten_file

    doc = SimpleDocTemplate(
        str(duong_dan),
        pagesize=A4,
        leftMargin=25 * mm,
        rightMargin=25 * mm,
        topMargin=25 * mm,
        bottomMargin=25 * mm,
        title=tieu_de,
        author="Nẵng Nhân Tịch Mặc",
    )

    goc = getSampleStyleSheet()
    kieu_tieu_de = ParagraphStyle(
        "NntmTieuDe", parent=goc["Heading1"], fontName=font, fontSize=18, leading=24, spaceAfter=12
    )
    kieu_doan = ParagraphStyle(
        "NntmDoan", parent=goc["BodyText"], fontName=font, fontSize=12, leading=19, spaceAfter=10
    )

    khoi = []

    for i, (tieu_de_trang, doan_van) in enumerate(trang):
        khoi.append(Paragraph(tieu_de_trang, kieu_tieu_de))
        for d in doan_van:
            khoi.append(Paragraph(d, kieu_doan))
        khoi.append(Spacer(1, 6 * mm))
        if i < len(trang) - 1:
            khoi.append(PageBreak())

    doc.build(khoi)
    return duong_dan


def main() -> None:
    """Sinh toàn bộ file demo."""
    font = dang_ky_font()
    THU_MUC.mkdir(parents=True, exist_ok=True)

    for ten_file, tieu_de, trang in SACH:
        duong_dan = tao_mot_cuon(ten_file, tieu_de, trang, font)
        print(f"{duong_dan.name:40s} {len(trang):2d} trang  {duong_dan.stat().st_size:>8,} byte")

    print(f"\nFont dung: {font}")
    print(f"Thu muc  : {THU_MUC}")


if __name__ == "__main__":
    main()
