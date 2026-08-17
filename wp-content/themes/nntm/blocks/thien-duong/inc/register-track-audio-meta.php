<?php
/**
 * ĐÃ CHUYỂN SANG PLUGIN — file này giữ lại rỗng để không vỡ require cũ.
 *
 * Trường `_nntm_track_audio` nay đăng ký tại
 * wp-content/plugins/nntm-zen-track-audio-admin/nntm-zen-track-audio-admin.php.
 *
 * Lý do chuyển (docs/04-kien-truc.md mục 1): dữ liệu thuộc plugin, không
 * thuộc theme — đổi theme không được mất dữ liệu. Cách đăng ký cũ còn phải
 * nhờ WordPress require file editor.asset.php để chạy sớm, tức là bám vào
 * chi tiết nội bộ của lõi; lõi đổi là hỏng âm thầm.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;
