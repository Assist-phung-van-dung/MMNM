=== NNTM Core ===
Contributors: nntm
Tags: custom post type, taxonomy, roles, buddhism
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Nền tảng dữ liệu và nghiệp vụ cho website Phật pháp "Nẵng Nhân Tịch Mặc": Custom Post Type, Taxonomy, vai trò thành viên, bảng dữ liệu riêng.

== Description ==

Plugin nền tảng (Phase 1) của dự án Nẵng Nhân Tịch Mặc. Đăng ký:

* 7 Custom Post Type: nntm_article, nntm_publication, nntm_talk, nntm_retreat, nntm_abode, nntm_video, nntm_zen_track.
* 3 Taxonomy: nntm_section, nntm_topic, nntm_series.
* 2 vai trò thành viên nâng thủ công: Đại Sĩ, Kim Cương Hành Giả.
* 5 bảng dữ liệu riêng: tiến độ đọc, ghi chú, yêu thích, đăng ký khóa tu, khai báo công phu (KPI).

Xem chi tiết kiến trúc tại docs/04-kien-truc.md trong repo dự án.

== Installation ==

1. Tải plugin lên wp-content/plugins/nntm-core.
2. Kích hoạt trong wp-admin → Plugins.
3. Lúc kích hoạt, plugin tự tạo bảng, vai trò và 6 term phân mục mặc định.

== Changelog ==

= 0.1.0 =
* Phiên bản đầu tiên: CPT, taxonomy, vai trò, bảng dữ liệu riêng.
