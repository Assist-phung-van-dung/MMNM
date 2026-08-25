<?php

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-contact-page">
	<div class="nntm-contact-page__inner">
		<section class="nntm-auth-card nntm-auth-card--rong nntm-contact-card" aria-labelledby="nntm-contact-title">
			<h1 id="nntm-contact-title" class="nntm-auth-card__title nntm-auth-card__title--dang-ky">
				<?php esc_html_e( 'Liên hệ', 'nntm' ); ?>
			</h1>

			<div class="nntm-contact-form__alert nntm-auth-alert nntm-auth-alert--loi" role="alert" hidden></div>

			<form id="nntm-contact-form" class="nntm-auth-form" novalidate>
				<div class="nntm-contact-honeypot" aria-hidden="true">
					<label for="nntm-contact-website">Website</label>
					<input type="text" id="nntm-contact-website" name="website" value="" tabindex="-1" autocomplete="off" />
				</div>

				<div class="nntm-auth-field">
					<label for="nntm-contact-name"><?php esc_html_e( 'Họ và tên', 'nntm' ); ?></label>
					<div class="nntm-auth-field__control">
						<input type="text" id="nntm-contact-name" name="ho_ten" maxlength="120" autocomplete="name" required />
					</div>
				</div>

				<div class="nntm-auth-field">
					<label for="nntm-contact-phone"><?php esc_html_e( 'Điện thoại (optional)', 'nntm' ); ?></label>
					<div class="nntm-auth-field__control">
						<input type="tel" id="nntm-contact-phone" name="dien_thoai" maxlength="30" autocomplete="tel" />
					</div>
				</div>

				<div class="nntm-auth-field">
					<label for="nntm-contact-email"><?php esc_html_e( 'Email', 'nntm' ); ?></label>
					<div class="nntm-auth-field__control">
						<input type="email" id="nntm-contact-email" name="email" maxlength="254" autocomplete="email" required />
					</div>
				</div>

				<div class="nntm-auth-field">
					<label for="nntm-contact-question"><?php esc_html_e( 'Câu hỏi', 'nntm' ); ?></label>
					<div class="nntm-auth-field__control">
						<textarea id="nntm-contact-question" name="cau_hoi" rows="7" maxlength="5000" required></textarea>
					</div>
				</div>

				<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac nntm-auth-btn--full" data-nntm-contact-submit>
					<?php esc_html_e( 'Gửi', 'nntm' ); ?>
				</button>
			</form>
		</section>
	</div>
</main>

<div id="nntm-contact-success-modal" class="nntm-contact-modal" hidden>
	<div class="nntm-contact-modal__overlay" data-nntm-contact-close></div>
	<div class="nntm-contact-modal__panel" role="dialog" aria-modal="true" aria-labelledby="nntm-contact-success-title">
		<button type="button" class="nntm-contact-modal__close" data-nntm-contact-close aria-label="<?php esc_attr_e( 'Đóng', 'nntm' ); ?>">&times;</button>
		<h2 id="nntm-contact-success-title"><?php esc_html_e( 'Cảm ơn quý vị', 'nntm' ); ?></h2>
		<p data-nntm-contact-success-message>
			<?php esc_html_e( 'Cảm ơn quý vị đã liên hệ với chúng tôi. Chúng tôi đã nhận được thông tin và sẽ phản hồi đến quý vị trong thời gian sớm nhất.', 'nntm' ); ?>
		</p>
		<button type="button" class="nntm-auth-btn nntm-auth-btn--dac" data-nntm-contact-close>
			<?php esc_html_e( 'Đóng', 'nntm' ); ?>
		</button>
	</div>
</div>

<?php
get_footer();
