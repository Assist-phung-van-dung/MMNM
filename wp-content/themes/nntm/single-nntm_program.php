<?php
/**
 * Trang giới thiệu một chương trình trì tụng (Lễ Đàn).
 *
 * Chỉ người đã đăng nhập xem được — cổng gác nằm ở
 * nntm_congtu_yeu_cau_dang_nhap() trong inc/cong-tu.php.
 *
 * Nội dung lấy nguyên từ ô soạn thảo trong admin. Cuối trang có nút Tham Gia
 * dùng lại đúng hộp thoại mà nút trên banner đang dùng.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$nntm_ct_id      = (int) get_the_ID();
	$nntm_ct_mo_dau  = trim( (string) get_the_excerpt() );
	$nntm_ct_nguoi   = get_current_user_id();

	$nntm_ct_trang_thai = function_exists( 'nntm_congtu_trang_thai_nut_banner' )
		? nntm_congtu_trang_thai_nut_banner()
		: 'chua-tham-gia';

	$nntm_ct_da_tham_gia = 'da-tham-gia' === $nntm_ct_trang_thai;

	$nntm_ct_tong = function_exists( 'nntm_kpi_tong_cua_nguoi' )
		? (array) nntm_kpi_tong_cua_nguoi( $nntm_ct_id, $nntm_ct_nguoi )
		: array(
			'cam_ket'   => 0,
			'thuc_hien' => 0,
		);

	$nntm_ct_nhan_nut = $nntm_ct_da_tham_gia
		? __( 'Cập nhật chuỗi trì', 'nntm' )
		: __( 'Tham Gia', 'nntm' );

	$nntm_ct_khoa_modal = $nntm_ct_da_tham_gia ? 'cap-nhat' : 'tham-gia';
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-chuong-trinh">

		<header class="nntm-chuong-trinh__dau">
			<div class="nntm-chuong-trinh__khung">
				<p class="nntm-chuong-trinh__nhan"><?php esc_html_e( 'Chương trình trì tụng', 'nntm' ); ?></p>

				<h1 class="nntm-chuong-trinh__tieu-de"><?php the_title(); ?></h1>

				<?php if ( '' !== $nntm_ct_mo_dau ) : ?>
					<p class="nntm-chuong-trinh__mo-dau"><?php echo esc_html( $nntm_ct_mo_dau ); ?></p>
				<?php endif; ?>

				<?php if ( $nntm_ct_da_tham_gia && function_exists( 'nntm_congtu_cau_da_cam_ket' ) ) : ?>
					<p class="nntm-chuong-trinh__hien-trang">
						<?php
						echo esc_html(
							nntm_congtu_cau_da_cam_ket(
								(int) $nntm_ct_tong['cam_ket'],
								(int) $nntm_ct_tong['thuc_hien']
							)
						);
						?>
					</p>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="nntm-chuong-trinh__anh">
				<?php the_post_thumbnail( 'full', array( 'loading' => 'eager' ) ); ?>
			</figure>
		<?php endif; ?>

		<article class="nntm-chuong-trinh__than">
			<div class="nntm-chuong-trinh__khung nntm-chuong-trinh__noi-dung">
				<?php the_content(); ?>
			</div>
		</article>

		<section class="nntm-chuong-trinh__keu-goi" aria-labelledby="nntm-ct-keu-goi-tieu-de">
			<div class="nntm-chuong-trinh__khung">
				<h2 id="nntm-ct-keu-goi-tieu-de" class="nntm-chuong-trinh__keu-goi-tieu-de">
					<?php
					echo esc_html(
						$nntm_ct_da_tham_gia
							? __( 'Cùng tiếp tục thời khoá', 'nntm' )
							: __( 'Phát tâm cùng đại chúng', 'nntm' )
					);
					?>
				</h2>

				<p class="nntm-chuong-trinh__keu-goi-chu">
					<?php
					echo esc_html(
						$nntm_ct_da_tham_gia
							? __( 'Ghi nhận thêm số chuỗi bạn vừa trì, hoặc cam kết thêm cho thời khoá tới.', 'nntm' )
							: __( 'Ghi danh số chuỗi bạn phát tâm sẽ trì trong chương trình này.', 'nntm' )
					);
					?>
				</p>

				<button
					type="button"
					class="nntm-chuong-trinh__nut"
					data-nntm-chuoi-tri="<?php echo esc_attr( $nntm_ct_khoa_modal ); ?>"
				><?php echo esc_html( $nntm_ct_nhan_nut ); ?></button>

				<?php if ( $nntm_ct_da_tham_gia && function_exists( 'nntm_congtu_cau_tong_ket' ) ) : ?>
					<p class="nntm-chuong-trinh__tong-ket">
						<?php echo esc_html( nntm_congtu_cau_tong_ket( $nntm_ct_tong ) ); ?>
					</p>
				<?php endif; ?>
			</div>
		</section>

	</main>
	<?php
endwhile;

get_footer();
