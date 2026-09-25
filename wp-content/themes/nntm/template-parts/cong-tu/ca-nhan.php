<?php
/**
 * Nội dung dashboard cá nhân Cộng Tu.
 *
 * MÀN TỰ DỰNG — Figma chưa có thiết kế. CSS riêng ở
 * assets/css/pages/cong-tu-ca-nhan.css, chỉ dùng token.
 *
 * Biểu đồ vẽ bằng HTML + CSS (không thư viện, không SVG co giãn): chữ nhãn giữ
 * đúng cỡ trên điện thoại, và số liệu là chữ thật — bảng nhật ký bên dưới là
 * bản thay thế đầy đủ cho người dùng trình đọc màn hình.
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
	<p class="nntm-ct-cn__rong"><?php esc_html_e( 'Hiện chưa có chương trình cộng tu nào đang mở. Khi đạo tràng mở chương trình mới, hành trình của bạn sẽ bắt đầu tại đây.', 'nntm' ); ?></p>
	<?php
	return;
endif;

$nntm_ct     = get_post( $nntm_pid );
$nntm_d      = nntm_kpi_bang_dieu_khien( $nntm_pid, $nntm_uid );
$nntm_dv     = $nntm_d['don_vi'];
$nntm_tong   = $nntm_d['tong'];
$nntm_pt     = (int) round( $nntm_tong['tien_trinh'] * 100 );
$nntm_da_tg  = function_exists( 'nntm_kpi_da_tham_gia' ) && nntm_kpi_da_tham_gia( $nntm_pid, $nntm_uid );
$nntm_url_kb = nntm_chuoi_tri_url( 'khai-bao' );
$nntm_url_tg = nntm_chuoi_tri_url( 'tham-gia' );
?>

<section class="nntm-ct-cn__dau" aria-label="<?php esc_attr_e( 'Chương trình', 'nntm' ); ?>">
	<p class="nntm-ct-cn__chao">
		<?php echo get_avatar( $nntm_uid, 40, '', '', array( 'class' => 'nntm-ct-cn__avatar' ) ); ?>
		<span><?php echo esc_html( sprintf( __( 'Xin chào, %s', 'nntm' ), nntm_congtu_phap_danh( $nntm_uid ) ) ); ?></span>
	</p>
	<p class="nntm-ct-cn__chuong-trinh">
		<a href="<?php echo esc_url( (string) get_permalink( $nntm_ct ) ); ?>"><?php echo esc_html( get_the_title( $nntm_ct ) ); ?></a>
		<span class="nntm-ct-cn__trang-thai<?php echo $nntm_d['mo'] ? ' is-mo' : ''; ?>"><?php echo $nntm_d['mo'] ? esc_html__( 'Đang mở', 'nntm' ) : esc_html__( 'Đã khép lại', 'nntm' ); ?></span>
	</p>
	<?php if ( $nntm_d['mo'] ) : ?>
		<p class="nntm-ct-cn__hom-nay-la">
			<?php
			/* translators: %s: ngày hôm nay */
			echo esc_html( sprintf( __( 'Hôm nay: %s', 'nntm' ), nntm_congtu_nhan_ngay( $nntm_d['hom_nay'], true ) ) );
			?>
		</p>
	<?php endif; ?>

	<?php if ( count( $nntm_chon['ds'] ) > 1 ) : ?>
		<nav class="nntm-ct-cn__chon" aria-label="<?php esc_attr_e( 'Chương trình khác', 'nntm' ); ?>">
			<?php foreach ( $nntm_chon['ds'] as $nntm_id ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'chuong-trinh', $nntm_id ) ); ?>"<?php echo $nntm_id === $nntm_pid ? ' aria-current="page"' : ''; ?>><?php echo esc_html( get_the_title( $nntm_id ) ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
</section>

<?php if ( $nntm_chon['co_the_ghi'] ) : ?>
	<p class="nntm-ct-cn__hanh-dong">
		<?php if ( $nntm_da_tg ) : ?>
			<a class="nntm-ct-cn__nut nntm-ct-cn__nut--chinh" href="<?php echo esc_url( $nntm_url_kb ); ?>" data-nntm-chuoi-tri="cap-nhat"><?php esc_html_e( 'Khai báo hôm nay', 'nntm' ); ?></a>
			<a class="nntm-ct-cn__nut" href="<?php echo esc_url( $nntm_url_tg ); ?>" data-nntm-chuoi-tri="tham-gia"><?php esc_html_e( 'Cam kết thêm', 'nntm' ); ?></a>
		<?php else : ?>
			<a class="nntm-ct-cn__nut nntm-ct-cn__nut--chinh" href="<?php echo esc_url( $nntm_url_tg ); ?>" data-nntm-chuoi-tri="tham-gia"><?php esc_html_e( 'Tham gia chương trình', 'nntm' ); ?></a>
		<?php endif; ?>
	</p>
<?php endif; ?>

<?php if ( ! $nntm_d['co_so'] ) : ?>
	<p class="nntm-ct-cn__rong">
		<?php
		/* translators: %s: tên chương trình */
		echo esc_html( sprintf( __( 'Bạn chưa phát nguyện cho %s. Khi bạn cam kết và khai báo, hành trình công phu sẽ hiện ở đây.', 'nntm' ), get_the_title( $nntm_ct ) ) );
		?>
	</p>
	<?php
	return;
endif;
?>

<section class="nntm-ct-cn__o-luoi" aria-label="<?php esc_attr_e( 'Tổng quan', 'nntm' ); ?>">
	<?php if ( $nntm_d['mo'] ) : ?>
		<div class="nntm-ct-cn__o nntm-ct-cn__o--noi-bat">
			<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['hom_nay_th'] ) ); ?></p>
			<p class="nntm-ct-cn__o-nhan"><?php echo esc_html( sprintf( __( '%s hôm nay', 'nntm' ), $nntm_dv ) ); ?></p>
			<p class="nntm-ct-cn__o-phu"><?php echo $nntm_d['hom_nay_th'] > 0 ? esc_html__( 'Tùy hỷ công phu của bạn.', 'nntm' ) : esc_html__( 'Hôm nay bạn chưa ghi.', 'nntm' ); ?></p>
		</div>
	<?php endif; ?>
	<div class="nntm-ct-cn__o">
		<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['tuan_nay']['thuc_hien'] ) ); ?></p>
		<p class="nntm-ct-cn__o-nhan"><?php echo esc_html( sprintf( __( '%s tuần này', 'nntm' ), $nntm_dv ) ); ?></p>
		<p class="nntm-ct-cn__o-phu">
			<?php
			/* translators: 1: số ngày có khai báo, 2: số ngày đã qua trong tuần */
			echo esc_html( sprintf( __( '%1$d/%2$d ngày có khai báo', 'nntm' ), $nntm_d['tuan_nay']['ngay_co_khai'], $nntm_d['tuan_nay']['so_ngay'] ) );
			?>
		</p>
	</div>
	<div class="nntm-ct-cn__o">
		<p class="nntm-ct-cn__o-so"><?php echo esc_html( nntm_congtu_so( $nntm_d['nhip'] ) ); ?></p>
		<p class="nntm-ct-cn__o-nhan"><?php esc_html_e( 'ngày liền nhịp công phu', 'nntm' ); ?></p>
		<p class="nntm-ct-cn__o-phu">
			<?php
			/* translators: %d: tổng số ngày có khai báo */
			echo esc_html( sprintf( __( 'Tổng cộng %d ngày có khai báo', 'nntm' ), $nntm_tong['so_ngay_co_khai'] ) );
			?>
		</p>
	</div>
</section>

<section class="nntm-ct-cn__cam-ket" aria-labelledby="nntm-ct-cn-cam-ket">
	<h2 id="nntm-ct-cn-cam-ket" class="nntm-ct-cn__muc"><?php esc_html_e( 'Cam kết của bạn', 'nntm' ); ?></h2>
	<?php if ( $nntm_tong['cam_ket'] > 0 ) : ?>
		<p class="nntm-ct-cn__phan-so">
			<strong><?php echo esc_html( nntm_congtu_so( $nntm_tong['thuc_hien'] ) ); ?></strong>
			/ <?php echo esc_html( nntm_congtu_so( $nntm_tong['cam_ket'] ) . ' ' . $nntm_dv ); ?>
			<span class="nntm-ct-cn__phan-tram"><?php echo esc_html( $nntm_pt . '%' ); ?></span>
		</p>
		<div
			class="nntm-ct-cn__thanh<?php echo $nntm_pt > 100 ? ' nntm-ct-cn__thanh--vuot' : ''; ?>"
			role="progressbar"
			aria-label="<?php esc_attr_e( 'Tiến trình cam kết', 'nntm' ); ?>"
			aria-valuemin="0"
			aria-valuemax="100"
			aria-valuenow="<?php echo esc_attr( (string) min( 100, max( 0, $nntm_pt ) ) ); ?>"
			aria-valuetext="<?php echo esc_attr( sprintf( '%s / %s %s (%d%%)', nntm_congtu_so( $nntm_tong['thuc_hien'] ), nntm_congtu_so( $nntm_tong['cam_ket'] ), $nntm_dv, $nntm_pt ) ); ?>"
		>
			<span class="nntm-ct-cn__thanh-day" style="width:<?php echo esc_attr( (string) min( 100, max( 0, $nntm_pt ) ) ); ?>%"></span>
		</div>
		<p class="nntm-ct-cn__ghi-chu">
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
		<p class="nntm-ct-cn__ghi-chu">
			<?php
			/* translators: 1: số đã trì, 2: đơn vị */
			echo esc_html( sprintf( __( 'Bạn đã trì %1$s %2$s nhưng chưa đặt mức cam kết.', 'nntm' ), nntm_congtu_so( $nntm_tong['thuc_hien'] ), $nntm_dv ) );
			?>
		</p>
	<?php endif; ?>
</section>

<?php
/**
 * Một biểu đồ cột. $cot: [ [ 'so' => int, 'nhan' => string, 'tieu_de' => string, 'lop' => string ], … ].
 */
$nntm_ve_cot = static function ( string $id, string $tieu_de, string $mo_ta, array $cot ): void {
	$max = max( 1, max( array_column( $cot, 'so' ) ) );
	?>
	<figure class="nntm-ct-cn__bieu-do" aria-labelledby="<?php echo esc_attr( $id ); ?>">
		<h2 id="<?php echo esc_attr( $id ); ?>" class="nntm-ct-cn__muc"><?php echo esc_html( $tieu_de ); ?></h2>
		<ol class="nntm-ct-cn__cot-ds<?php echo count( $cot ) > 10 ? ' nntm-ct-cn__cot-ds--day' : ''; ?>" aria-hidden="true" style="--nntm-so-cot:<?php echo (int) count( $cot ); ?>">
			<?php foreach ( $cot as $c ) : ?>
				<li class="nntm-ct-cn__cot <?php echo esc_attr( $c['lop'] ); ?>" title="<?php echo esc_attr( $c['tieu_de'] ); ?>">
					<span class="nntm-ct-cn__cot-so"><?php echo $c['so'] > 0 ? esc_html( nntm_congtu_so( $c['so'] ) ) : ''; ?></span>
					<span class="nntm-ct-cn__cot-thanh" style="--nntm-cot-cao:<?php echo esc_attr( (string) round( $c['so'] / $max * 100, 1 ) ); ?>%"></span>
					<span class="nntm-ct-cn__cot-nhan"><?php echo esc_html( $c['nhan'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
		<figcaption class="nntm-ct-cn__mo-ta"><?php echo esc_html( $mo_ta ); ?></figcaption>
	</figure>
	<?php
};

$nntm_cot_ngay = array();
$nntm_14_tong  = 0;
$nntm_14_co    = 0;
foreach ( $nntm_d['ngay'] as $nntm_n ) {
	$nntm_14_tong += $nntm_n['thuc_hien'];
	$nntm_14_co   += $nntm_n['thuc_hien'] > 0 ? 1 : 0;
	$nntm_nhan     = $nntm_n['hom_nay'] ? __( 'Nay', 'nntm' ) : substr( $nntm_n['ngay'], 8, 2 );
	$nntm_cot_ngay[] = array(
		'so'      => $nntm_n['thuc_hien'],
		'nhan'    => $nntm_nhan,
		'tieu_de' => nntm_congtu_nhan_ngay( $nntm_n['ngay'] ) . ': ' . nntm_congtu_so( $nntm_n['thuc_hien'] ) . ' ' . $nntm_dv,
		'lop'     => trim( ( $nntm_n['hom_nay'] ? 'is-hom-nay ' : '' ) . ( $nntm_n['truoc_dau'] ? 'is-truoc' : '' ) ),
	);
}
if ( 0 === $nntm_14_tong ) :
	?>
	<section class="nntm-ct-cn__bieu-do" aria-labelledby="nntm-ct-cn-14-ngay">
		<h2 id="nntm-ct-cn-14-ngay" class="nntm-ct-cn__muc"><?php esc_html_e( 'Mười bốn ngày gần nhất', 'nntm' ); ?></h2>
		<p class="nntm-ct-cn__bieu-do-rong"><?php esc_html_e( 'Chưa có khai báo nào trong 14 ngày qua.', 'nntm' ); ?></p>
	</section>
	<?php
else :
	$nntm_ve_cot(
	'nntm-ct-cn-14-ngay',
	__( 'Mười bốn ngày gần nhất', 'nntm' ),
	/* translators: 1: tổng, 2: đơn vị, 3: số ngày có khai báo */
	sprintf( __( '14 ngày qua: %1$s %2$s · %3$d/14 ngày có khai báo.', 'nntm' ), nntm_congtu_so( $nntm_14_tong ), $nntm_dv, $nntm_14_co ),
	$nntm_cot_ngay
);
endif;

$nntm_cot_tuan = array();
$nntm_tuan_max = null;
foreach ( $nntm_d['tuan'] as $nntm_t ) {
	if ( null === $nntm_tuan_max || $nntm_t['thuc_hien'] > $nntm_tuan_max['thuc_hien'] ) {
		$nntm_tuan_max = $nntm_t;
	}
	$nntm_cot_tuan[] = array(
		'so'      => $nntm_t['thuc_hien'],
		// Tuần hiện tại vẫn ghi ngày đầu tuần (chữ "Tuần này" bị cắt trên điện thoại) —
		// màu và tooltip đã cho biết đó là tuần đang diễn ra.
		'nhan'    => substr( $nntm_t['dau'], 8, 2 ) . '/' . substr( $nntm_t['dau'], 5, 2 ),
		'tieu_de' => __( 'Tuần', 'nntm' ) . ' ' . nntm_congtu_nhan_tuan( $nntm_t['dau'], $nntm_t['cuoi'] ) . ': ' . nntm_congtu_so( $nntm_t['thuc_hien'] ) . ' ' . $nntm_dv . ( $nntm_t['dang_dien_ra'] ? ' ' . __( '(đang diễn ra)', 'nntm' ) : '' ),
		'lop'     => $nntm_t['dang_dien_ra'] ? 'is-hom-nay' : '',
	);
}
if ( $nntm_cot_tuan ) {
	$nntm_so_sanh = $nntm_d['tuan_truoc']['thuc_hien'] > 0
		? ' ' . sprintf(
			/* translators: 1: tổng cùng kỳ tuần trước, 2: đơn vị */
			__( 'Cùng kỳ tuần trước: %1$s %2$s.', 'nntm' ),
			nntm_congtu_so( $nntm_d['tuan_truoc']['thuc_hien'] ),
			$nntm_dv
		)
		: '';
	$nntm_ve_cot(
		'nntm-ct-cn-theo-tuan',
		__( 'Theo tuần', 'nntm' ),
		sprintf(
			/* translators: 1: số tuần, 2: tuần cao nhất, 3: tổng tuần cao nhất, 4: đơn vị */
			__( '%1$d tuần gần nhất · cao nhất tuần %2$s với %3$s %4$s.', 'nntm' ),
			count( $nntm_cot_tuan ),
			nntm_congtu_nhan_tuan( $nntm_tuan_max['dau'], $nntm_tuan_max['cuoi'] ),
			nntm_congtu_so( $nntm_tuan_max['thuc_hien'] ),
			$nntm_dv
		) . $nntm_so_sanh,
		$nntm_cot_tuan
	);
}
?>

<section class="nntm-ct-cn__nhat-ky" aria-labelledby="nntm-ct-cn-nhat-ky">
	<h2 id="nntm-ct-cn-nhat-ky" class="nntm-ct-cn__muc"><?php esc_html_e( 'Nhật ký khai báo', 'nntm' ); ?></h2>
	<div class="nntm-ct-cn__bang-cuon">
		<table class="nntm-ct-cn__bang">
			<caption class="nntm-sr-only"><?php esc_html_e( 'Nhật ký khai báo theo tuần, mới nhất trước', 'nntm' ); ?></caption>
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Ngày', 'nntm' ); ?></th>
					<th scope="col"><?php echo esc_html( sprintf( __( 'Đã trì (%s)', 'nntm' ), $nntm_dv ) ); ?></th>
					<th scope="col"><?php esc_html_e( 'Số lần ghi', 'nntm' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Cam kết thêm', 'nntm' ); ?></th>
				</tr>
			</thead>
			<?php foreach ( $nntm_d['lich_su'] as $nntm_w ) : ?>
				<tbody>
					<tr class="nntm-ct-cn__bang-tuan">
						<th scope="rowgroup" colspan="4">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: khoảng ngày, 2: tổng, 3: đơn vị, 4: số ngày */
									__( 'Tuần %1$s · %2$s %3$s · %4$d ngày có khai báo', 'nntm' ),
									nntm_congtu_nhan_tuan( $nntm_w['dau'], $nntm_w['cuoi'] ),
									nntm_congtu_so( $nntm_w['thuc_hien'] ),
									$nntm_dv,
									$nntm_w['ngay_co_khai']
								)
							);
							?>
						</th>
					</tr>
					<?php foreach ( $nntm_w['ngay'] as $nntm_r ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( nntm_congtu_nhan_ngay( $nntm_r['ngay'] ) ); ?></th>
							<td><?php echo esc_html( nntm_congtu_so( $nntm_r['thuc_hien'] ) ); ?></td>
							<td><?php echo esc_html( (string) $nntm_r['so_lan'] ); ?></td>
							<td><?php echo $nntm_r['cam_ket'] > 0 ? esc_html( '+' . nntm_congtu_so( $nntm_r['cam_ket'] ) ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			<?php endforeach; ?>
		</table>
	</div>
</section>
