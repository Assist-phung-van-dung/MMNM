NNTM Zen Track Manager 2.1.0

Mỗi post nntm_zen_track:
- Tên bài: Post Title.
- Hình bài: Featured Image.
- Nhạc: Media Library attachment ID lưu ở _nntm_track_audio.
- Lượt nghe: bảng wp_nntm_zen_track_stats (prefix thay đổi theo site).

Plugin có:
- Sidebar Gutenberg + fallback Classic Editor để chọn audio.
- Cột Ảnh / Tệp âm thanh / Lượt nghe trong danh sách Nhạc Thiền.
- Submenu Nhạc Thiền > Thống kê lượt nghe.
- AJAX nntm_track_listen cho player Thiền Đường.
- Migration số cũ từ _nntm_track_listen_count sang bảng thống kê khi Activate.

== Changelog ==

= 2.1.0 =
* Fix Gutenberg: enable custom-fields support so _nntm_track_audio persists through REST when updating a zen track.


== Realtime Thiền Đường ==
Vào Nhạc Thiền > Realtime Thiền Đường, nhập App key, App secret, WebSocket host/port trùng với app trên Soketi rồi bật realtime.

Kênh:
* presence-nntm-thien-duong: tổng user đăng nhập đang ở trang.
* presence-nntm-thien-duong-track-{POST_ID}: user đang thực sự phát bài đó.

Frontend dùng pusher-js qua CDN chính thức. Có thể thay URL bằng filter nntm_zen_track_pusher_js_src. App secret không bao giờ gửi ra frontend.

Lượt nghe: cộng sau khi audio thực sự phát liên tục ít nhất 5 giây. Auto-next tạo phiên nghe mới cho bài kế tiếp.

