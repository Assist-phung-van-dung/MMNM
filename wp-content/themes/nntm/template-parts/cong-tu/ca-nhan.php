<?php
/**
 * Nội dung dashboard cá nhân Cộng Tu.
 *
 * MÀN TỰ DỰNG — Figma chưa có thiết kế. CSS riêng ở
 * assets/css/pages/cong-tu-ca-nhan.css, chỉ dùng token.
 *
 * Bố cục: dải đầu tối (ảnh đại diện của chương trình nếu BQT đặt) → ba thẻ số
 * nổi lên mép dải → vòng cam kết → biểu đồ 14 ngày + thanh theo tuần → nhật ký
 * dạng dòng thời gian. Biểu đồ vẽ bằng HTML + CSS (không thư viện); nhật ký là
 * bản chữ đầy đủ cho trình đọc màn hình.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() || ! function_exists( 'nntm_kpi_bang_dieu_khien' ) ) {
	return;
}

$nntm_uid  = get_current_user_id();
$nntm_chon = nntm_congtu_chon_chuong_trinh( $nntm_uid );
$nntm_pid  = $nntm_chon['program_id'];

if ( ! $nntm_pid ) :
	?>
	<section class="nntm-ct-cn__dai nntm-ct-cn__dai--rong">
		<div class="nntm-ct-cn__khung">
			<p class="nntm-ct-cn__nhan-dai"><?php echo esc_html( get_the_title( get_queried_object_id() ) ); ?></p>
			<p class="nntm-ct-cn__dai-loi"><?php esc_html_e( 'Hiện chưa có chương trình cộng tu nào đang mở. Khi đạo tràng mở chương trình mới, hành trình của bạn sẽ bắt đầu tại đây.', 'nntm' ); ?></p>
		</div>
	</section>
	<?php
	return;
endif;

$nntm_ct    = get_post( $nntm_pid );
$nntm_d     = nntm_kpi_bang_dieu_khien( $nntm_pid, $nntm_uid );
$nntm_dv    = $nntm_d['don_vi'];
$nntm_tong  = $nntm_d['tong'];
$nntm_pt    = (int) round( $nntm_tong['tien_trinh'] * 100 );
$nntm_da_tg = function_exists( 'nntm_kpi_da_tham_gia' ) && nntm_kpi_da_tham_gia( $nntm_pid, $nntm_uid );
$nntm_anh   = get_the_post_thumbnail_url( $nntm_ct, 'full' );
$nntm_loi   = trim( (string) get_post_field( 'post_content', get_queried_object_id() ) );

/* Bảy ngày của tuần hiện tại cho dải chấm trong thẻ "Tuần này". */
$nntm_14    = array_column( $nntm_d['ngay'], null, 'ngay' );
$nntm_tuan7 = array();
for ( $nntm_i = 0; $nntm_i < 7; $nntm_i++ ) {
	$nntm_ng      = nntm_kpi_cong_ngay( $nntm_d['tuan_nay']['dau'], $nntm_i );
	$nntm_so_ng   = $nntm_14[ $nntm_ng ]['thuc_hien'] ?? 0;
	$nntm_tuan7[] = array(
		'ngay'  => $nntm_ng,
		'so'    => $nntm_so_ng,
		'trang' => $nntm_ng > $nntm_d['ngay_cuoi'] ? 'chua-den' : ( $nntm_so_ng > 0 ? 'co' : 'trong' ),
		'nay'   => $nntm_d['mo'] && $nntm_ng === $nntm_d['hom_nay'],
	);
}
?>

<section class="nntm-ct-cn__dai<?php echo $nntm_anh ? ' nntm-ct-cn__dai--anh' : ''; ?>"<?php echo $nntm_anh ? ' style="--nntm-ct-cn-anh:url(' . esc_url( $nntm_anh ) . ')"' : ''; ?>>
	<div class="nntm-ct-cn__khung nntm-ct-cn__dai-luoi">
		<div class="nntm-ct-cn__dai-chu">
			<p class="nntm-ct-cn__nhan-dai"><?php echo esc_html( get_the_title( get_queried_object_id() ) ); ?></p>
			<h1 class="nntm-ct-cn__ten-ct">
				<a href="<?php echo esc_url( (string) get_permalink( $nntm_ct ) ); ?>"><?php echo esc_html( get_the_title( $nntm_ct ) ); ?></a>
			</h1>
			<p class="nntm-ct-cn__chao">
				<?php echo get_avatar( $nntm_uid, 36, '', '', array( 'class' => 'nntm-ct-cn__avatar' ) ); ?>
				<span>
					<?php
					/* translators: %s: pháp danh */
					echo esc_html( sprintf( __( 'Xin chào, %s', 'nntm' ), nntm_congtu_phap_danh( $nntm_uid ) ) );
					?>
				</span>
				<span class="nntm-ct-cn__trang-thai<?php echo $nntm_d['mo'] ? ' is-mo' : ''; ?>"><?php echo $nntm_d['mo'] ? esc_html__( 'Đang mở', 'nntm' ) : esc_html__( 'Đã khép lại', 'nntm' ); ?></span>
			</p>

			<?php if ( '' !== $nntm_loi ) : ?>
				<div class="nntm-ct-cn__loi-mo"><?php echo wp_kses_post( apply_filters( 'the_content', $nntm_loi ) ); ?></div>
			<?php endif; ?>

			<?php if ( count( $nntm_chon['ds'] ) > 1 ) : ?>
				<nav class="nntm-ct-cn__chon" aria-label="<?php esc_attr_e( 'Chương trình khác', 'nntm' ); ?>">
					<?php foreach ( $nntm_chon['ds'] as $nntm_id ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'chuong-trinh', $nntm_id ) ); ?>"<?php echo $nntm_id === $nntm_pid ? ' aria-current="page"' : ''; ?>><?php echo esc_html( get_the_title( $nntm_id ) ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>

		<?php if ( $nntm_d['mo'] ) : ?>
			<div class="nntm-ct-cn__hom-nay">
				<p class="nntm-ct-cn__hom-nay-ngay"><?php echo esc_html( nntm_congtu_nhan_ngay( $nntm_d['hom_nay'], true ) ); ?></p>
				<?php if ( $nntm_d['co_so'] ) : ?>
					<p class="nntm-ct-cn__hom-nay-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['hom_nay_th'] ) ); ?></p>
					<p class="nntm-ct-cn__hom-nay-nhan"><?php echo esc_html( sprintf( __( '%s hôm nay', 'nntm' ), $nntm_dv ) ); ?></p>
					<p class="nntm-ct-cn__hom-nay-loi"><?php echo $nntm_d['hom_nay_th'] > 0 ? esc_html__( 'Tùy hỷ công phu của bạn.', 'nntm' ) : esc_html__( 'Trì xong, bạn ghi lại tại đây.', 'nntm' ); ?></p>
				<?php else : ?>
					<p class="nntm-ct-cn__hom-nay-nhan"><?php esc_html_e( 'Phát nguyện cùng đạo tràng', 'nntm' ); ?></p>
					<p class="nntm-ct-cn__hom-nay-loi"><?php esc_html_e( 'Chọn mức cam kết của bạn để bắt đầu.', 'nntm' ); ?></p>
				<?php endif; ?>

				<?php if ( $nntm_chon['co_the_ghi'] ) : ?>
					<p class="nntm-ct-cn__hanh-dong">
						<?php if ( $nntm_da_tg ) : ?>
							<a class="nntm-ct-cn__nut nntm-ct-cn__nut--chinh" href="<?php echo esc_url( nntm_chuoi_tri_url( 'khai-bao' ) ); ?>" data-nntm-chuoi-tri="cap-nhat"><?php esc_html_e( 'Khai báo hôm nay', 'nntm' ); ?></a>
							<a class="nntm-ct-cn__nut" href="<?php echo esc_url( nntm_chuoi_tri_url( 'tham-gia' ) ); ?>" data-nntm-chuoi-tri="tham-gia"><?php esc_html_e( 'Cam kết thêm', 'nntm' ); ?></a>
						<?php else : ?>
							<a class="nntm-ct-cn__nut nntm-ct-cn__nut--chinh" href="<?php echo esc_url( nntm_chuoi_tri_url( 'tham-gia' ) ); ?>" data-nntm-chuoi-tri="tham-gia"><?php esc_html_e( 'Tham gia chương trình', 'nntm' ); ?></a>
						<?php endif; ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<div class="nntm-ct-cn__khung nntm-ct-cn__than">

<?php if ( ! $nntm_d['co_so'] ) : ?>
	<div class="nntm-ct-cn__the nntm-ct-cn__chao-mung">
		<span class="nntm-ct-cn__hoa-van" aria-hidden="true"></span>
		<p class="nntm-ct-cn__chao-mung-chinh">
			<?php
			/* translators: %s: tên chương trình */
			echo esc_html( sprintf( __( 'Bạn chưa phát nguyện cho %s.', 'nntm' ), get_the_title( $nntm_ct ) ) );
			?>
		</p>
		<p class="nntm-ct-cn__phu"><?php esc_html_e( 'Khi bạn cam kết và khai báo, hành trình công phu sẽ hiện ở đây.', 'nntm' ); ?></p>
	</div>
</div>
	<?php
	return;
endif;
?>

	<section class="nntm-ct-cn__o-luoi" aria-label="<?php esc_attr_e( 'Tổng quan', 'nntm' ); ?>">
		<div class="nntm-ct-cn__the nntm-ct-cn__o">
			<p class="nntm-ct-cn__o-nhan"><?php esc_html_e( 'Tuần này', 'nntm' ); ?></p>
			<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['tuan_nay']['thuc_hien'] ) ); ?> <span><?php echo esc_html( $nntm_dv ); ?></span></p>
			<ol class="nntm-ct-cn__tuan7" aria-label="<?php echo esc_attr( sprintf( __( '%1$d trên %2$d ngày có khai báo', 'nntm' ), $nntm_d['tuan_nay']['ngay_co_khai'], $nntm_d['tuan_nay']['so_ngay'] ) ); ?>">
				<?php foreach ( $nntm_tuan7 as $nntm_n ) : ?>
					<li class="is-<?php echo esc_attr( $nntm_n['trang'] ); ?><?php echo $nntm_n['nay'] ? ' is-nay' : ''; ?>" title="<?php echo esc_attr( nntm_congtu_nhan_ngay( $nntm_n['ngay'] ) . ( 'chua-den' === $nntm_n['trang'] ? '' : ': ' . nntm_congtu_so( $nntm_n['so'] ) . ' ' . $nntm_dv ) ); ?>">
						<span class="nntm-ct-cn__tuan7-cham" aria-hidden="true"></span>
						<span class="nntm-ct-cn__tuan7-thu"><?php echo esc_html( strtok( nntm_congtu_nhan_ngay( $nntm_n['ngay'] ), ',' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<div class="nntm-ct-cn__the nntm-ct-cn__o">
			<p class="nntm-ct-cn__o-nhan"><?php esc_html_e( 'Nhịp công phu', 'nntm' ); ?></p>
			<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['nhip'] ) ); ?> <span><?php esc_html_e( 'ngày liền', 'nntm' ); ?></span></p>
			<p class="nntm-ct-cn__phu">
				<?php
				echo $nntm_d['nhip'] > 0
					? esc_html__( 'Giữ nhịp đều đặn mỗi ngày.', 'nntm' )
					: esc_html__( 'Một ngày khai báo là nhịp bắt đầu lại.', 'nntm' );
				?>
			</p>
		</div>
		<div class="nntm-ct-cn__the nntm-ct-cn__o">
			<p class="nntm-ct-cn__o-nhan"><?php esc_html_e( 'Tổng đã trì', 'nntm' ); ?></p>
			<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_tong['thuc_hien'] ) ); ?> <span><?php echo esc_html( $nntm_dv ); ?></span></p>
			<p class="nntm-ct-cn__phu">
				<?php
				/* translators: %d: số ngày */
				echo esc_html( sprintf( __( 'Trong %d ngày có khai báo', 'nntm' ), $nntm_tong['so_ngay_co_khai'] ) );
				?>
			</p>
		</div>
	</section>

	<section class="nntm-ct-cn__the nntm-ct-cn__cam-ket" aria-labelledby="nntm-ct-cn-cam-ket">
		<?php $nntm_vong = min( 100, max( 0, $nntm_pt ) ); ?>
		<div
			class="nntm-ct-cn__vong<?php echo $nntm_pt >= 100 ? ' is-tron' : ''; ?>"
			style="--nntm-vong:<?php echo esc_attr( (string) $nntm_vong ); ?>"
			role="progressbar"
			aria-label="<?php esc_attr_e( 'Tiến trình cam kết', 'nntm' ); ?>"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo esc_attr( (string) $nntm_vong ); ?>"
			aria-valuetext="<?php echo esc_attr( $nntm_tong['cam_ket'] > 0 ? sprintf( '%s / %s %s (%d%%)', nntm_congtu_so( $nntm_tong['thuc_hien'] ), nntm_congtu_so( $nntm_tong['cam_ket'] ), $nntm_dv, $nntm_pt ) : __( 'Chưa đặt cam kết', 'nntm' ) ); ?>"
		>
			<span class="nntm-ct-cn__vong-so"><?php echo $nntm_tong['cam_ket'] > 0 ? esc_html( $nntm_pt . '%' ) /* không dấu chấm nghìn: "2.600%" đọc như 2,6% */ : '—'; ?></span>
		</div>
		<div class="nntm-ct-cn__cam-ket-chu">
			<h2 id="nntm-ct-cn-cam-ket" class="nntm-ct-cn__muc"><?php esc_html_e( 'Cam kết của bạn', 'nntm' ); ?></h2>
			<?php if ( $nntm_tong['cam_ket'] > 0 ) : ?>
				<p class="nntm-ct-cn__phan-so">
					<strong><?php echo esc_html( nntm_congtu_so( $nntm_tong['thuc_hien'] ) ); ?></strong>
					<span>/ <?php echo esc_html( nntm_congtu_so( $nntm_tong['cam_ket'] ) . ' ' . $nntm_dv ); ?></span>
				</p>
				<p class="nntm-ct-cn__phu">
					<?php
					if ( $nntm_tong['con_lai'] > 0 ) {
						/* translators: 1: số còn lại, 2: đơn vị */
						echo esc_html( sprintf( __( 'Còn %1$s %2$s nữa là tròn cam kết.', 'nntm' ), nntm_congtu_so( $nntm_tong['con_lai'] ), $nntm_dv ) );
						if ( $nntm_d['nhip_can'] ) {
							echo ' ' . esc_html(
								sprintf(
									/* translators: 1: số ngày còn lại, 2: số mỗi ngày, 3: đơn vị */
									__( 'Còn %1$d ngày — khoảng %2$s %3$s mỗi ngày.', 'nntm' ),
									$nntm_d['nhip_can']['so_ngay_con'],
									nntm_congtu_so( $nntm_d['nhip_can']['moi_ngay'] ),
									$nntm_dv
								)
							);
						}
					} else {
						/* translators: 1: số vượt, 2: đơn vị */
						echo esc_html( sprintf( __( 'Đã tròn cam kết · vượt %1$s %2$s. Tùy hỷ!', 'nntm' ), nntm_congtu_so( $nntm_tong['thuc_hien'] - $nntm_tong['cam_ket'] ), $nntm_dv ) );
					}
					?>
				</p>
			<?php else : ?>
				<p class="nntm-ct-cn__phu">
					<?php
					/* translators: 1: số đã trì, 2: đơn vị */
					echo esc_html( sprintf( __( 'Bạn đã trì %1$s %2$s nhưng chưa đặt mức cam kết.', 'nntm' ), nntm_congtu_so( $nntm_tong['thuc_hien'] ), $nntm_dv ) );
					?>
				</p>
			<?php endif; ?>
		</div>
	</section>

	<div class="nntm-ct-cn__bieu-do-luoi">
		<?php
		$nntm_14_tong = array_sum( array_column( $nntm_d['ngay'], 'thuc_hien' ) );
		$nntm_14_co   = count( array_filter( $nntm_d['ngay'], static fn( $n ) => $n['thuc_hien'] > 0 ) );
		$nntm_14_max  = max( 1, max( array_column( $nntm_d['ngay'], 'thuc_hien' ) ) );
		?>
		<figure class="nntm-ct-cn__the nntm-ct-cn__bieu-do" aria-labelledby="nntm-ct-cn-14">
			<h2 id="nntm-ct-cn-14" class="nntm-ct-cn__muc"><?php esc_html_e( 'Mười bốn ngày gần nhất', 'nntm' ); ?></h2>
			<?php if ( 0 === $nntm_14_tong ) : ?>
				<p class="nntm-ct-cn__bieu-do-rong"><?php esc_html_e( 'Chưa có khai báo nào trong 14 ngày qua.', 'nntm' ); ?></p>
			<?php else : ?>
				<ol class="nntm-ct-cn__cot-ds" aria-hidden="true">
					<?php foreach ( $nntm_d['ngay'] as $nntm_n ) : ?>
						<li class="nntm-ct-cn__cot<?php echo $nntm_n['hom_nay'] ? ' is-nay' : ''; ?><?php echo $nntm_n['truoc_dau'] ? ' is-truoc' : ''; ?><?php echo 0 === $nntm_n['thuc_hien'] ? ' is-khong' : ''; ?>" title="<?php echo esc_attr( nntm_congtu_nhan_ngay( $nntm_n['ngay'] ) . ': ' . nntm_congtu_so( $nntm_n['thuc_hien'] ) . ' ' . $nntm_dv ); ?>">
							<span class="nntm-ct-cn__cot-so"><?php echo $nntm_n['thuc_hien'] > 0 ? esc_html( nntm_congtu_so( $nntm_n['thuc_hien'] ) ) : ''; ?></span>
							<span class="nntm-ct-cn__cot-ong"><span class="nntm-ct-cn__cot-thanh" style="--nntm-cot-cao:<?php echo esc_attr( (string) round( $nntm_n['thuc_hien'] / $nntm_14_max * 100, 1 ) ); ?>%"></span></span>
							<span class="nntm-ct-cn__cot-thu"><?php echo esc_html( $nntm_n['hom_nay'] ? __( 'Nay', 'nntm' ) : strtok( nntm_congtu_nhan_ngay( $nntm_n['ngay'] ), ',' ) ); ?></span>
							<span class="nntm-ct-cn__cot-ngay"><?php echo esc_html( substr( $nntm_n['ngay'], 8, 2 ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<figcaption class="nntm-ct-cn__mo-ta">
				<?php
				/* translators: 1: tổng, 2: đơn vị, 3: số ngày có khai báo */
				echo esc_html( sprintf( __( '14 ngày qua: %1$s %2$s · %3$d/14 ngày có khai báo', 'nntm' ), nntm_congtu_so( $nntm_14_tong ), $nntm_dv, $nntm_14_co ) );
				?>
			</figcaption>
		</figure>

		<?php if ( $nntm_d['tuan'] ) : ?>
			<?php $nntm_tuan_max = max( 1, max( array_column( $nntm_d['tuan'], 'thuc_hien' ) ) ); ?>
			<figure class="nntm-ct-cn__the nntm-ct-cn__bieu-do" aria-labelledby="nntm-ct-cn-tuan">
				<h2 id="nntm-ct-cn-tuan" class="nntm-ct-cn__muc"><?php esc_html_e( 'Theo tuần', 'nntm' ); ?></h2>
				<ol class="nntm-ct-cn__ngang-ds">
					<?php foreach ( array_reverse( $nntm_d['tuan'] ) as $nntm_t ) : ?>
						<li class="nntm-ct-cn__ngang<?php echo $nntm_t['dang_dien_ra'] ? ' is-nay' : ''; ?>">
							<span class="nntm-ct-cn__ngang-nhan"><?php echo esc_html( $nntm_t['dang_dien_ra'] ? __( 'Tuần này', 'nntm' ) : nntm_congtu_nhan_tuan( $nntm_t['dau'], $nntm_t['cuoi'] ) ); ?></span>
							<span class="nntm-ct-cn__ngang-ong" aria-hidden="true"><span class="nntm-ct-cn__ngang-thanh" style="--nntm-cot-cao:<?php echo esc_attr( (string) round( $nntm_t['thuc_hien'] / $nntm_tuan_max * 100, 1 ) ); ?>%"></span></span>
							<span class="nntm-ct-cn__ngang-so"><?php echo esc_html( nntm_congtu_so( $nntm_t['thuc_hien'] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
				<?php if ( $nntm_d['tuan_truoc']['thuc_hien'] > 0 ) : ?>
					<figcaption class="nntm-ct-cn__mo-ta">
						<?php
						/* translators: 1: tuần này, 2: cùng kỳ tuần trước, 3: đơn vị */
						echo esc_html( sprintf( __( 'Tuần này %1$s · cùng kỳ tuần trước %2$s %3$s', 'nntm' ), nntm_congtu_so( $nntm_d['tuan_nay']['thuc_hien'] ), nntm_congtu_so( $nntm_d['tuan_truoc']['thuc_hien'] ), $nntm_dv ) );
						?>
					</figcaption>
				<?php endif; ?>
			</figure>
		<?php endif; ?>
	</div>

	<section class="nntm-ct-cn__nhat-ky" aria-labelledby="nntm-ct-cn-nhat-ky">
		<h2 id="nntm-ct-cn-nhat-ky" class="nntm-ct-cn__muc nntm-ct-cn__muc--giua"><?php esc_html_e( 'Nhật ký khai báo', 'nntm' ); ?></h2>
		<?php foreach ( $nntm_d['lich_su'] as $nntm_i => $nntm_w ) : ?>
			<details class="nntm-ct-cn__the nntm-ct-cn__tuan"<?php echo $nntm_i < 3 ? ' open' : ''; ?>>
				<summary class="nntm-ct-cn__tuan-dau">
					<span class="nntm-ct-cn__tuan-ten">
						<?php
						/* translators: %s: khoảng ngày */
						echo esc_html( sprintf( __( 'Tuần %s', 'nntm' ), nntm_congtu_nhan_tuan( $nntm_w['dau'], $nntm_w['cuoi'] ) ) );
						?>
					</span>
					<span class="nntm-ct-cn__tuan-tom">
						<?php
						/* translators: 1: số ngày, 2: tổng, 3: đơn vị */
						echo esc_html( sprintf( __( '%1$d ngày · %2$s %3$s', 'nntm' ), $nntm_w['ngay_co_khai'], nntm_congtu_so( $nntm_w['thuc_hien'] ), $nntm_dv ) );
						?>
					</span>
				</summary>
				<ul class="nntm-ct-cn__ngay-ds">
					<?php foreach ( $nntm_w['ngay'] as $nntm_r ) : ?>
						<li class="nntm-ct-cn__ngay">
							<span class="nntm-ct-cn__ngay-ten"><?php echo esc_html( nntm_congtu_nhan_ngay( $nntm_r['ngay'] ) ); ?></span>
							<span class="nntm-ct-cn__ngay-the">
								<?php if ( $nntm_r['cam_ket'] > 0 ) : ?>
									<span class="nntm-ct-cn__nhan-nho nntm-ct-cn__nhan-nho--nguyen"><?php echo esc_html( sprintf( __( 'Phát nguyện +%s', 'nntm' ), nntm_congtu_so( $nntm_r['cam_ket'] ) ) ); ?></span>
								<?php endif; ?>
								<?php if ( $nntm_r['so_lan'] > 1 ) : ?>
									<span class="nntm-ct-cn__nhan-nho"><?php echo esc_html( sprintf( __( '%d lần ghi', 'nntm' ), $nntm_r['so_lan'] ) ); ?></span>
								<?php endif; ?>
							</span>
							<span class="nntm-ct-cn__ngay-so"><?php echo $nntm_r['thuc_hien'] > 0 ? esc_html( nntm_congtu_so( $nntm_r['thuc_hien'] ) . ' ' . $nntm_dv ) : '—'; ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</details>
		<?php endforeach; ?>
	</section>

</div>
