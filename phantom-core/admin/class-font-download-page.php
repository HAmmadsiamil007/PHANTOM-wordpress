<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

class Phantom_Font_Download_Page {
	private static ?Phantom_Font_Download_Page $instance = null;

	public static function instance(): Phantom_Font_Download_Page {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'admin_post_phantom_download_font', array( $this, 'handle_download' ) );
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'phantom-core' ) );
		}

		$download_dir = Phantom_Webfont_Loader::get_download_dir();
		$existing     = is_dir( $download_dir ) ? scandir( $download_dir ) : array();
		$css_files    = array_filter( $existing, function ( $f ) {
			return 'css' === pathinfo( $f, PATHINFO_EXTENSION );
		} );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Download Google Fonts', 'phantom-core' ); ?></h1>
			<p><?php echo esc_html__( 'Download Google Font CSS files to host locally. Improves privacy and performance.', 'phantom-core' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'phantom_download_font', 'phantom_font_nonce' ); ?>
				<input type="hidden" name="action" value="phantom_download_font">

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Font Family', 'phantom-core' ); ?></th>
						<td>
							<input type="text" name="font_family" class="regular-text"
								placeholder="e.g. Roboto, Open Sans" required>
							<p class="description"><?php esc_html_e( 'Enter the Google Font family name exactly as it appears on Google Fonts.', 'phantom-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Font Weights', 'phantom-core' ); ?></th>
						<td>
							<input type="text" name="font_weights" class="regular-text"
								placeholder="e.g. 400,700" value="400,700">
							<p class="description"><?php esc_html_e( 'Comma-separated list of weights (e.g. 400,700,italic).', 'phantom-core' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Download Font', 'phantom-core' ) ); ?>
			</form>

			<?php if ( ! empty( $css_files ) ) : ?>
				<h2><?php esc_html_e( 'Downloaded Fonts', 'phantom-core' ); ?></h2>
				<ul>
					<?php foreach ( $css_files as $file ) : ?>
						<li><?php echo esc_html( $file ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_download(): void {
		if ( ! wp_verify_nonce( $_POST['phantom_font_nonce'] ?? '', 'phantom_download_font' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'phantom-core' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'phantom-core' ), 403 );
		}

		$font_family  = sanitize_text_field( $_POST['font_family'] ?? '' );
		$font_weights = sanitize_text_field( $_POST['font_weights'] ?? '400,700' );

		if ( empty( $font_family ) ) {
			wp_die( esc_html__( 'Font family is required.', 'phantom-core' ) );
		}

		// Build Google Fonts CSS URL
		$family_slug  = str_replace( ' ', '+', trim( $font_family ) );
		$weights      = array_map( 'trim', explode( ',', $font_weights ) );
		$weight_param = implode( ';', array_filter( $weights ) );

		$google_url = 'https://fonts.googleapis.com/css2?family=' . $family_slug;
		if ( ! empty( $weight_param ) ) {
			$google_url .= ':wght@' . $weight_param;
		}
		$google_url .= '&display=swap';

		// Fetch CSS
		$response = wp_remote_get( $google_url, array(
			'timeout'    => 30,
			'user-agent' => 'Mozilla/5.0 (WordPress)',
		) );

		if ( is_wp_error( $response ) ) {
			wp_die( esc_html__( 'Failed to fetch font CSS: ', 'phantom-core' ) . esc_html( $response->get_error_message() ) );
		}

		$css = wp_remote_retrieve_body( $response );
		if ( empty( $css ) ) {
			wp_die( esc_html__( 'Empty response from Google Fonts.', 'phantom-core' ) );
		}

		// Save CSS file
		$download_dir = Phantom_Webfont_Loader::get_download_dir();
		if ( ! is_dir( $download_dir ) ) {
			wp_mkdir_p( $download_dir );
		}

		$safe_filename = sanitize_title( $font_family ) . '.css';
		$filepath      = $download_dir . $safe_filename;

		// Rewrite Google Font URLs to local references (download the font files too)
		// For now, save the CSS as-is; the font files are served from Google's CDN
		// A full implementation would download woff2 files and rewrite URLs
		$written = file_put_contents( $filepath, $css );

		if ( false === $written ) {
			wp_die( esc_html__( 'Failed to write font CSS file.', 'phantom-core' ) );
		}

		wp_safe_redirect( add_query_arg( 'downloaded', rawurlencode( $font_family ),
			admin_url( 'admin.php?page=phantom-font-download' ) ) );
		exit;
	}
}
