<?php
/**
 * Plugin Name: Page Porter for Elementor
 * Description: Bulk export and import Elementor pages with smart media detection and URL migration.
 * Version: 1.0
 * Author: Erick Villeta
 * Plugin URI: https://ericksonvilleta.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: page-porter-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Page_Porter_For_Elementor
 * Handles the logic for exporting and importing Elementor pages.
 */
class Page_Porter_For_Elementor {

	/**
	 * Constructor: Initialize hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'handle_export' ] );
		add_action( 'admin_init', [ $this, 'handle_import' ] );
	}

	/**
	 * Add submenu under Elementor.
	 */
	public function add_menu_page() {
		add_submenu_page(
			'elementor',
			__( 'Page Porter', 'page-porter-for-elementor' ),
			__( 'Page Porter', 'page-porter-for-elementor' ),
			'manage_options',
			'page-porter-for-elementor',
			[ $this, 'render_admin_page' ]
		);
	}

	/**
	 * Render the admin dashboard.
	 */
	public function render_admin_page() {
		$pages = get_pages();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Page Porter for Elementor', 'page-porter-for-elementor' ); ?></h1>
			<p><?php echo esc_html__( 'Bulk move your Elementor pages with ease. Author: ', 'page-porter-for-elementor' ) . '<strong>Erick Villeta</strong>'; ?></p>
			<hr>
			<div style="display: flex; gap: 40px; margin-top: 20px;">
				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
					<h2><?php esc_html_e( 'Export Pages', 'page-porter-for-elementor' ); ?></h2>
					<form method="post">
						<?php wp_nonce_field( 'porter_export_nonce', 'porter_nonce' ); ?>
						<input type="hidden" name="action" value="porter_export">
						<select name="export_ids[]" multiple style="width: 100%; height: 250px; margin-bottom: 10px;">
							<?php foreach ( $pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>">
									<?php echo esc_html( $page->post_title ); ?> (ID: <?php echo esc_attr( $page->ID ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
						<label><input type="checkbox" name="export_all"> <?php esc_html_e( 'Export All Pages', 'page-porter-for-elementor' ); ?></label>
						<?php submit_button( __( 'Generate Export File', 'page-porter-for-elementor' ) ); ?>
					</form>
				</div>

				<div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
					<h2><?php esc_html_e( 'Import Pages', 'page-porter-for-elementor' ); ?></h2>
					<form method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( 'porter_import_nonce', 'porter_nonce' ); ?>
						<input type="hidden" name="action" value="porter_import">
						<input type="file" name="import_file" accept=".json" required>
						<p style="font-size: 12px; color: #666;"><?php esc_html_e( 'Importer uses existing media files if found to avoid duplicates.', 'page-porter-for-elementor' ); ?></p>
						<?php submit_button( __( 'Run Smart Import', 'page-porter-for-elementor' ) ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle the Export action.
	 */
	public function handle_export() {
		if ( ! isset( $_POST['action'] ) || 'porter_export' !== $_POST['action'] ) {
			return;
		}

		check_admin_referer( 'porter_export_nonce', 'porter_nonce' );

		$ids = [];
		if ( ! empty( $_POST['export_all'] ) ) {
			$ids = wp_list_pluck( get_pages(), 'ID' );
		} elseif ( isset( $_POST['export_ids'] ) && is_array( $_POST['export_ids'] ) ) {
			$ids = array_map( 'intval', wp_unslash( $_POST['export_ids'] ) );
		}

		if ( empty( $ids ) ) {
			return;
		}

		$export_data = [
			'source_url' => get_site_url(),
			'pages'      => [],
		];

		foreach ( $ids as $id ) {
			$thumb_id               = get_post_thumbnail_id( $id );
			$export_data['pages'][] = [
				'title'          => get_the_title( $id ),
				'content'        => get_post_field( 'post_content', $id ),
				'elementor_data' => get_post_meta( $id, '_elementor_data', true ),
				'edit_mode'      => get_post_meta( $id, '_elementor_edit_mode', true ),
				'template'       => get_post_meta( $id, '_wp_page_template', true ),
				'featured_img'   => $thumb_id ? wp_get_attachment_url( $thumb_id ) : null,
			];
		}

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="page-porter-export-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $export_data );
		exit;
	}

	/**
	 * Handle the Import action.
	 */
	public function handle_import() {
		if ( ! isset( $_POST['action'] ) || 'porter_import' !== $_POST['action'] || empty( $_FILES['import_file']['tmp_name'] ) ) {
			return;
		}

		check_admin_referer( 'porter_import_nonce', 'porter_nonce' );

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$file_path = sanitize_text_field( wp_unslash( $_FILES['import_file']['tmp_name'] ) );
		
		if ( ! file_exists( $file_path ) ) {
			return;
		}

		$file_content = file_get_contents( $file_path );
		$decoded_data = json_decode( $file_content, true );

		if ( ! isset( $decoded_data['pages'] ) || ! is_array( $decoded_data['pages'] ) ) {
			add_settings_error( 'porter_msg', 'porter_error', __( 'Invalid export file.', 'page-porter-for-elementor' ), 'error' );
			return;
		}

		$source_url  = $decoded_data['source_url'] ?? '';
		$current_url = get_site_url();

		foreach ( $decoded_data['pages'] as $item ) {
			$clean_content = str_replace( $source_url, $current_url, $item['content'] );
			$clean_meta    = $this->recursive_url_replace( $source_url, $current_url, $item['elementor_data'] );

			$new_id = wp_insert_post( [
				'post_title'   => $item['title'] . ' (Imported)',
				'post_status'  => 'draft',
				'post_type'    => 'page',
				'post_content' => $clean_content,
			] );

			if ( $new_id ) {
				update_post_meta( $new_id, '_elementor_data', $clean_meta );
				update_post_meta( $new_id, '_elementor_edit_mode', $item['edit_mode'] );
				update_post_meta( $new_id, '_wp_page_template', $item['template'] );

				if ( ! empty( $item['featured_img'] ) ) {
					$image_url   = $item['featured_img'];
					$existing_id = $this->get_attachment_id_by_url( $image_url );

					if ( $existing_id ) {
						set_post_thumbnail( $new_id, $existing_id );
					} else {
						$att_id = media_sideload_image( $image_url, $new_id, $item['title'], 'id' );
						if ( ! is_wp_error( $att_id ) ) {
							set_post_thumbnail( $new_id, (int) $att_id );
						}
					}
				}
			}
		}
		add_settings_error( 'porter_msg', 'porter_success', __( 'Import successful! Pages are in Drafts.', 'page-porter-for-elementor' ), 'updated' );
	}

	/**
	 * Recursive URL replacement in mixed data types.
	 */
	private function recursive_url_replace( $search, $replace, $data ) {
		if ( empty( $search ) ) {
			return $data;
		}
		if ( is_string( $data ) ) {
			return str_replace( $search, $replace, $data );
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->recursive_url_replace( $search, $replace, $value );
			}
		}
		return $data;
	}

	/**
	 * Find attachment ID by URL/filename using WordPress API to avoid DirectQuery warning.
	 */
	private function get_attachment_id_by_url( $url ) {
		$file_name = basename( $url );
		$id        = attachment_url_to_postid( $url );

		if ( $id ) {
			return $id;
		}

		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => '_wp_attached_file',
					'value'   => $file_name,
					'compare' => 'LIKE',
				],
			],
		];

		$attachments = get_posts( $args );

		return ! empty( $attachments ) ? (int) $attachments[0] : false;
	}
}

new Page_Porter_For_Elementor();
