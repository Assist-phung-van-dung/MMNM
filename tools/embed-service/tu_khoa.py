"""Bảng từ khoá song ngữ để đọc nội dung ảnh.

VÌ SAO PHẢI SONG NGỮ: CLIP được huấn luyện gần như toàn bộ bằng tiếng Anh.
Đưa thẳng câu tiếng Việt ("một tấm ảnh mặt trời") vào bộ mã hoá chữ thì kết
quả rất kém — thử rồi mới biết thì mất công. Nên nhận diện bằng câu TIẾNG ANH,
còn từ hiển thị và từ đem đi tìm kiếm là TIẾNG VIỆT.

Thêm từ khoá mới: chỉ cần thêm một dòng (en, vi). Không phải sửa code.
Danh sách nghiêng về chủ đề của trang: cảnh chùa chiền, thiên nhiên, sinh hoạt
tu tập — chứ không phải bộ nhãn tổng quát kiểu "xe hơi, bóng đá".
"""

from __future__ import annotations

# (câu mô tả tiếng Anh cho CLIP, từ khoá tiếng Việt để hiển thị và đem đi tìm)
TU_KHOA: list[tuple[str, str]] = [
    # Trời đất, thời khắc
    ("the sun", "mặt trời"),
    ("a sunset", "hoàng hôn"),
    ("a sunrise", "bình minh"),
    ("the moon at night", "mặt trăng"),
    ("a starry night sky", "trời đêm"),
    ("clouds in the sky", "mây"),
    ("rain", "mưa"),
    ("fog and mist", "sương mù"),
    ("snow", "tuyết"),
    # Cảnh vật
    ("a mountain", "núi"),
    ("a forest", "rừng"),
    ("the sea", "biển"),
    ("a river", "sông"),
    ("a lake", "hồ"),
    ("a waterfall", "thác nước"),
    ("a rice field", "ruộng lúa"),
    ("a garden", "khu vườn"),
    ("a bamboo grove", "rừng tre"),
    ("a pine forest", "rừng thông"),
    ("a road or path", "con đường"),
    ("a bridge", "cây cầu"),
    ("a boat on water", "con thuyền"),
    ("rocks and stones", "đá"),
    ("sand", "cát"),
    # Cây cỏ
    ("a lotus flower", "hoa sen"),
    ("a bodhi tree", "cây bồ đề"),
    ("a tree", "cây"),
    ("flowers", "hoa"),
    ("green leaves", "lá cây"),
    # Chùa chiền, thờ tự
    ("a Buddhist temple", "ngôi chùa"),
    ("a pagoda tower", "ngôi tháp"),
    ("a statue of Buddha", "tượng Phật"),
    ("an altar with offerings", "bàn thờ"),
    ("burning incense sticks", "nén hương"),
    ("a lit candle", "ngọn nến"),
    ("a large bell", "chuông"),
    ("prayer beads", "chuỗi hạt"),
    ("a lantern", "đèn lồng"),
    ("a shrine gate", "cổng chùa"),
    # Con người, sinh hoạt tu tập
    ("a Buddhist monk in robes", "nhà sư"),
    ("many monks gathered together", "chư tăng"),
    ("a person meditating", "ngồi thiền"),
    ("hands pressed together in prayer", "chắp tay"),
    ("a person bowing in worship", "lễ lạy"),
    ("a person reading a book", "đọc sách"),
    ("a teacher speaking to an audience", "thuyết pháp"),
    ("a crowd of people", "đông người"),
    ("a child", "trẻ em"),
    ("an elderly person", "người già"),
    # Đồ vật
    ("an open book", "sách"),
    ("old manuscript scriptures", "kinh sách"),
    ("calligraphy writing", "thư pháp"),
    ("a cup of tea", "chén trà"),
    ("a bowl of food", "bát cơm"),
    ("a musical instrument", "nhạc cụ"),
    # Không gian
    ("the interior of a building", "trong nhà"),
    ("an outdoor landscape", "ngoài trời"),
    ("a city street", "phố"),
    ("a village", "làng quê"),
    ("a wooden house", "nhà gỗ"),
    ("a stone staircase", "bậc thang đá"),
]

# Mẫu câu đưa vào CLIP. "a photo of ..." là mẫu chuẩn của bài báo gốc, cho kết
# quả tốt hơn hẳn so với đưa trơ mỗi danh từ.
MAU_CAU = "a photo of {}"


def cau_tieng_anh() -> list[str]:
    """Danh sách câu tiếng Anh đã ghép mẫu, đúng thứ tự với TU_KHOA."""
    return [MAU_CAU.format(en) for en, _ in TU_KHOA]


def tu_tieng_viet() -> list[str]:
    """Danh sách từ khoá tiếng Việt, đúng thứ tự với TU_KHOA."""
    return [vi for _, vi in TU_KHOA]
