<?php
/**
 * Ảnh đại diện cho term của taxonomy nntm_section.
 *
 * Mô tả term dùng luôn trường "description" có sẵn của WordPress — không đẻ
 * thêm trường riêng. File này chỉ thêm MỘT trường mới: ảnh đại diện, lưu ở
 * term meta "_nntm_term_image" (ID ảnh, kiểu integer), dùng làm nền thẻ ở
 * block nntm/term-list (xem wp-content/themes/nntm/blocks/term-list/).
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Term_Meta
 */
class Term_Meta {

	/**
	 * Taxonomy áp dụng trường ảnh đại diện.
	 */
	const TAXONOMY = 'nntm_section';

	/**
	 * Tên term meta lưu ID ảnh đại diện.
	 */
	const META_KEY = '_nntm_term_image';

	/**
	 * Tên field trên form admin (input hidden chứa ID ảnh).
	 */
	const FIELD_NAME = 'nntm_term_image';

	/**
	 * Term meta lưu thứ tự hiển thị (số nhỏ đứng trước).
	 */
	const ORDER_META_KEY = '_nntm_term_order';

	/**
	 * Tên ô nhập thứ tự trên form quản trị.
	 */
	const ORDER_FIELD_NAME = 'nntm_term_order';

	/**
	 * Action dùng cho nonce.
	 */
	const NONCE_ACTION = 'nntm_term_image_action';

	/**
	 * Tên field nonce trên form.
	 */
	const NONCE_NAME = 'nntm_term_image_nonce';

	/**
	 * Instance duy nhất (singleton).
	 *
	 * @var Term_Meta|null
	 */
	private static ?Term_Meta $instance = null;

	/**
	 * Lấy instance duy nhất.
	 */
	public static function instance(): Term_Meta {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gắn hook: đăng ký meta, vẽ form thêm/sửa, lưu dữ liệu, nạp script trình chọn ảnh.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );

		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'render_add_form_field' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'render_edit_form_field' ), 10, 2 );

		add_action( 'created_' . self::TAXONOMY, array( $this, 'save_term_image' ) );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save_term_image' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );

		// In script JS thuần trực tiếp ở footer của đúng 2 màn quản trị taxonomy
		// (danh sách + sửa term) — tránh phải tạo thêm file .js riêng ngoài
		// phạm vi được giao cho file này.
		add_action( 'admin_footer-edit-tags.php', array( $this, 'print_media_picker_script' ) );
		add_action( 'admin_footer-term.php', array( $this, 'print_media_picker_script' ) );
	}

	/**
	 * Đăng ký term meta "_nntm_term_image" cho taxonomy nntm_section.
	 * show_in_rest để REST API (block nntm/term-list, ServerSideRender) đọc được.
	 */
	public function register_meta(): void {
		register_term_meta(
			self::TAXONOMY,
			self::META_KEY,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
				'description'       => __( 'ID ảnh đại diện dùng làm nền thẻ của phân mục này ở trang phân mục.', 'nntm' ),
			)
		);

		/*
		 * Thứ tự hiển thị. WordPress không có sẵn cách sắp xếp chuyên mục —
		 * mặc định chỉ xếp theo bảng chữ cái, mà thiết kế Figma lại có thứ tự
		 * riêng (Nguyên Thuỷ → Đại Thừa → Tịnh Độ → Mật Tông). Không có trường
		 * này thì ban quản trị buộc phải đổi tên chuyên mục mới đổi được thứ tự.
		 */
		register_term_meta(
			self::TAXONOMY,
			self::ORDER_META_KEY,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'manage_categories' );
				},
				'description'       => __( 'Số thứ tự hiển thị của phân mục. Số nhỏ đứng trước.', 'nntm' ),
			)
		);
	}

	/**
	 * Kiểm tra màn hình hiện tại có đúng là màn quản trị taxonomy nntm_section không.
	 */
	private function is_taxonomy_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && isset( $screen->taxonomy ) && self::TAXONOMY === $screen->taxonomy;
	}

	/**
	 * Nạp trình chọn media của WordPress (wp_enqueue_media) đúng lúc cần —
	 * chỉ trên màn danh sách/sửa term của nntm_section, tránh nạp thừa toàn admin.
	 *
	 * @param string $hook_suffix Tên file màn admin hiện tại (do WordPress truyền vào).
	 */
	public function enqueue_media_uploader( string $hook_suffix ): void {
		if ( 'edit-tags.php' !== $hook_suffix && 'term.php' !== $hook_suffix ) {
			return;
		}

		if ( ! $this->is_taxonomy_screen() ) {
			return;
		}

		wp_enqueue_media();
	}

	/**
	 * Ô chọn ảnh đại diện trên form "Thêm phân mục mới".
	 *
	 * @param string $taxonomy Tên taxonomy hiện tại (WordPress tự truyền vào).
	 */
	public function render_add_form_field( string $taxonomy ): void {
		if ( self::TAXONOMY !== $taxonomy ) {
			return;
		}
		?>
		<div class="form-field term-group nntm-term-image-field">
			<label for="nntm-term-image-id"><?php esc_html_e( 'Ảnh đại diện phân mục', 'nntm' ); ?></label>
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input
				type="hidden"
				id="nntm-term-image-id"
				class="nntm-term-image-input"
				name="<?php echo esc_attr( self::FIELD_NAME ); ?>"
				value=""
			/>
			<div class="nntm-term-image-preview"></div>
			<p>
				<button type="button" class="button nntm-term-image-select"><?php esc_html_e( 'Chọn ảnh', 'nntm' ); ?></button>
				<button type="button" class="button nntm-term-image-remove" style="display:none;"><?php esc_html_e( 'Bỏ ảnh', 'nntm' ); ?></button>
			</p>
			<p class="description">
				<?php esc_html_e( 'Ảnh này dùng làm nền thẻ hiển thị phân mục ở trang phân mục (khối "Danh sách phân mục con"). Nên chọn ảnh chiều dọc, đủ tối để chữ đè lên vẫn đọc được.', 'nntm' ); ?>
			</p>
		</div>
		<div class="form-field term-group nntm-term-order-field">
			<label for="nntm-term-order"><?php esc_html_e( 'Thứ tự hiển thị', 'nntm' ); ?></label>
			<input type="number" id="nntm-term-order" name="<?php echo esc_attr( self::ORDER_FIELD_NAME ); ?>" value="0" min="0" step="1" />
			<p class="description">
				<?php esc_html_e( 'Số nhỏ đứng trước. Để 0 ở tất cả thì xếp theo bảng chữ cái.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Ô chọn ảnh đại diện trên form "Sửa phân mục".
	 *
	 * @param \WP_Term $term     Term đang sửa.
	 * @param string   $taxonomy Tên taxonomy hiện tại (WordPress tự truyền vào).
	 */
	public function render_edit_form_field( $term, string $taxonomy ): void {
		if ( self::TAXONOMY !== $taxonomy ) {
			return;
		}

		$image_id     = absint( get_term_meta( $term->term_id, self::META_KEY, true ) );
		$preview_html = $image_id > 0 ? wp_get_attachment_image( $image_id, 'medium' ) : '';
		?>
		<tr class="form-field term-group-wrap nntm-term-image-field">
			<th scope="row">
				<label for="nntm-term-image-id"><?php esc_html_e( 'Ảnh đại diện phân mục', 'nntm' ); ?></label>
			</th>
			<td>
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<input
					type="hidden"
					id="nntm-term-image-id"
					class="nntm-term-image-input"
					name="<?php echo esc_attr( self::FIELD_NAME ); ?>"
					value="<?php echo esc_attr( (string) $image_id ); ?>"
				/>
				<div class="nntm-term-image-preview">
					<?php echo wp_kses_post( $preview_html ); ?>
				</div>
				<p>
					<button type="button" class="button nntm-term-image-select"><?php esc_html_e( 'Chọn ảnh', 'nntm' ); ?></button>
					<button
						type="button"
						class="button nntm-term-image-remove"
						<?php echo $image_id > 0 ? '' : 'style="display:none;"'; ?>
					><?php esc_html_e( 'Bỏ ảnh', 'nntm' ); ?></button>
				</p>
				<p class="description">
					<?php esc_html_e( 'Ảnh này dùng làm nền thẻ hiển thị phân mục ở trang phân mục (khối "Danh sách phân mục con"). Nên chọn ảnh chiều dọc, đủ tối để chữ đè lên vẫn đọc được.', 'nntm' ); ?>
				</p>
			</td>
		</tr>
		<tr class="form-field term-group-wrap nntm-term-order-field">
			<th scope="row">
				<label for="nntm-term-order"><?php esc_html_e( 'Thứ tự hiển thị', 'nntm' ); ?></label>
			</th>
			<td>
				<input
					type="number"
					id="nntm-term-order"
					name="<?php echo esc_attr( self::ORDER_FIELD_NAME ); ?>"
					value="<?php echo esc_attr( (string) absint( get_term_meta( $term->term_id, self::ORDER_META_KEY, true ) ) ); ?>"
					min="0"
					step="1"
				/>
				<p class="description">
					<?php esc_html_e( 'Số nhỏ đứng trước. Để 0 ở tất cả thì xếp theo bảng chữ cái.', 'nntm' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Lưu ID ảnh đại diện khi tạo mới / sửa term của nntm_section.
	 * Bắt buộc kiểm tra nonce + capability manage_categories trước khi ghi (quy ước code mục 9).
	 *
	 * @param int $term_id ID term vừa tạo/sửa (WordPress tự truyền vào qua hook created_… / edited_…).
	 */
	public function save_term_image( int $term_id ): void {
		if (
			! isset( $_POST[ self::NONCE_NAME ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( isset( $_POST[ self::FIELD_NAME ] ) ) {
			$image_id = absint( wp_unslash( $_POST[ self::FIELD_NAME ] ) );

			if ( $image_id > 0 ) {
				update_term_meta( $term_id, self::META_KEY, $image_id );
			} else {
				delete_term_meta( $term_id, self::META_KEY );
			}
		}

		// Thứ tự hiển thị — nằm cùng form nên dùng chung nonce đã kiểm tra ở trên.
		if ( isset( $_POST[ self::ORDER_FIELD_NAME ] ) ) {
			$order = absint( wp_unslash( $_POST[ self::ORDER_FIELD_NAME ] ) );

			if ( $order > 0 ) {
				update_term_meta( $term_id, self::ORDER_META_KEY, $order );
			} else {
				delete_term_meta( $term_id, self::ORDER_META_KEY );
			}
		}
	}

	/**
	 * In script JS thuần (không thư viện ngoài) điều khiển trình chọn media
	 * cho ô ảnh đại diện — chỉ in ra khi đúng màn quản trị nntm_section.
	 */
	public function print_media_picker_script(): void {
		if ( ! $this->is_taxonomy_screen() ) {
			return;
		}
		?>
		<script>
		( function () {
			'use strict';

			var FRAME_TITLE  = <?php echo wp_json_encode( __( 'Chọn ảnh đại diện phân mục', 'nntm' ) ); ?>;
			var FRAME_BUTTON = <?php echo wp_json_encode( __( 'Dùng ảnh này', 'nntm' ) ); ?>;

			/**
			 * Gắn trình chọn media cho một ô input ẩn chứa ID ảnh.
			 *
			 * @param {HTMLElement} wrap Khối bọc ngoài (.form-field hoặc tr.form-field).
			 */
			function setupPicker( wrap ) {
				if ( ! wrap || wrap.getAttribute( 'data-nntm-bound' ) === '1' ) {
					return;
				}
				wrap.setAttribute( 'data-nntm-bound', '1' );

				var input      = wrap.querySelector( '.nntm-term-image-input' );
				var preview    = wrap.querySelector( '.nntm-term-image-preview' );
				var selectBtn  = wrap.querySelector( '.nntm-term-image-select' );
				var removeBtn  = wrap.querySelector( '.nntm-term-image-remove' );
				var form       = input ? input.closest( 'form' ) : null;
				var frame      = null;

				if ( ! input || ! preview || ! selectBtn || ! removeBtn ) {
					return;
				}

				function renderPreview( url ) {
					if ( url ) {
						preview.innerHTML = '';
						var img = document.createElement( 'img' );
						img.src = url;
						img.style.maxWidth = '150px';
						img.style.height = 'auto';
						img.style.display = 'block';
						preview.appendChild( img );
						removeBtn.style.display = '';
					} else {
						preview.innerHTML = '';
						removeBtn.style.display = 'none';
					}
				}

				selectBtn.addEventListener( 'click', function ( event ) {
					event.preventDefault();

					if ( ! window.wp || ! window.wp.media ) {
						return;
					}

					if ( frame ) {
						frame.open();
						return;
					}

					frame = window.wp.media( {
						title: FRAME_TITLE,
						button: { text: FRAME_BUTTON },
						library: { type: 'image' },
						multiple: false
					} );

					frame.on( 'select', function () {
						var selection = frame.state().get( 'selection' ).first();
						if ( ! selection ) {
							return;
						}
						var attachment = selection.toJSON();
						var url = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;

						input.value = attachment.id;
						renderPreview( url );
					} );

					frame.open();
				} );

				removeBtn.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					input.value = '';
					renderPreview( '' );
				} );

				// Form "Thêm phân mục mới" được WordPress gửi qua ajax rồi tự
				// form.reset() sau khi thêm thành công — input được reset về
				// giá trị mặc định (rỗng) nhưng khối xem trước ảnh thì không
				// tự xóa vì nó không phải form control. Lắng nghe sự kiện
				// 'reset' của form để đồng bộ lại khối xem trước.
				if ( form ) {
					form.addEventListener( 'reset', function () {
						window.setTimeout( function () {
							renderPreview( '' );
						}, 0 );
					} );
				}
			}

			document.addEventListener( 'DOMContentLoaded', function () {
				var inputs = document.querySelectorAll( '.nntm-term-image-input' );
				for ( var i = 0; i < inputs.length; i++ ) {
					setupPicker( inputs[ i ].closest( '.form-field' ) );
				}
			} );
		} )();
		</script>
		<?php
	}
}
