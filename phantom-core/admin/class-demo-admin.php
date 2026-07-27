<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Demo\Demo_Registry;
use PhantomCore\Demo\Demo_Switcher;
use PhantomCore\Demo\Demo_Installer;

defined( 'ABSPATH' ) || exit;

class Demo_Admin {

	private static ?self $instance = null;

	private Demo_Registry $registry;
	private Demo_Switcher $switcher;
	private Demo_Installer $installer;

	public function __construct(
		Demo_Registry $registry,
		Demo_Switcher $switcher,
		Demo_Installer $installer
	) {
		$this->registry  = $registry;
		$this->switcher  = $switcher;
		$this->installer = $installer;
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			$registry  = Demo_Registry::get_instance();
			$switcher  = new Demo_Switcher( $registry );
			$installer = new Demo_Installer( $registry );
			self::$instance = new self( $registry, $switcher, $installer );
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_phantom_activate_demo', array( $this, 'ajax_activate' ) );
		add_action( 'wp_ajax_phantom_deactivate_demo', array( $this, 'ajax_deactivate' ) );
		add_action( 'wp_ajax_phantom_activate_precheck', array( $this, 'ajax_activate_precheck' ) );
		add_action( 'wp_ajax_phantom_delete_demo', array( $this, 'ajax_delete' ) );
		add_action( 'admin_post_phantom_install_demo', array( $this, 'handle_install' ) );
	}

	public function register_menu(): void {
		add_submenu_page(
			'phantom-dashboard',
			__( 'Demo Manager', 'phantom-core' ),
			__( 'Demo Manager', 'phantom-core' ),
			'manage_options',
			'phantom-demo-manager',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		$expected_hooks = array(
			'appearance_page_phantom-demo-manager',
			'admin_page_phantom-demo-manager',
		);
		if ( ! in_array( $hook, $expected_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'phantom-demo-admin',
			PHANTOM_CORE_URL . 'admin/css/demo-admin.css',
			array(),
			PHANTOM_CORE_VERSION
		);

		wp_enqueue_script(
			'phantom-demo-admin',
			PHANTOM_CORE_URL . 'admin/js/demo-admin.js',
			array( 'jquery' ),
			PHANTOM_CORE_VERSION,
			true
		);

		wp_localize_script(
			'phantom-demo-admin',
			'phantomDemo',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'phantom_demo_nonce' ),
				'i18n'    => array(
					'activateConfirm'  => __( 'Are you sure you want to activate this demo?', 'phantom-core' ),
					'deactivateConfirm' => __( 'Are you sure you want to deactivate this demo?', 'phantom-core' ),
					'deleteConfirm'    => __( 'Are you sure you want to delete this demo?', 'phantom-core' ),
					'activateSuccess'  => __( 'Demo activated successfully!', 'phantom-core' ),
					'deactivateSuccess' => __( 'Demo deactivated successfully!', 'phantom-core' ),
					'deleteSuccess'    => __( 'Demo deleted successfully!', 'phantom-core' ),
					'checkingCompat'   => __( 'Checking compatibility...', 'phantom-core' ),
					'error'            => __( 'An error occurred.', 'phantom-core' ),
				),
			)
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'phantom-core' ) );
		}

		$active_slug = $this->switcher->get_active_slug();
		$demos       = $this->registry->get_all();

		if ( isset( $_GET['demo_installed'] ) && '1' === $_GET['demo_installed'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Demo installed successfully!', 'phantom-core' ) . '</p></div>';
		}
		?>
		<div class="wrap phantom-demo-wrap">
			<h1><?php echo esc_html__( 'Demo Manager', 'phantom-core' ); ?></h1>
			<p><?php echo esc_html__( 'Manage your Phantom Core template packs. Activate, deactivate, install, or delete demos.', 'phantom-core' ); ?></p>

			<div class="phantom-demo-upload">
				<h2><?php echo esc_html__( 'Install New Demo', 'phantom-core' ); ?></h2>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'phantom_install_demo', 'phantom_install_nonce' ); ?>
					<input type="hidden" name="action" value="phantom_install_demo" />
					<input type="file" name="demo_zip" accept=".zip" required />
					<p class="description"><?php echo esc_html__( 'Upload a .zip file containing a demo.json manifest and html/ directory.', 'phantom-core' ); ?></p>
					<?php submit_button( __( 'Install Demo', 'phantom-core' ), 'secondary', 'install_demo' ); ?>
				</form>
			</div>

			<div class="phantom-demo-grid">
				<?php foreach ( $demos as $demo ) :
					$is_active     = $demo->slug === $active_slug;
					$can_activate  = $this->switcher->can_activate( $demo->slug );
				?>
				<div class="phantom-demo-card <?php echo $is_active ? 'active' : ''; ?>">
					<div class="phantom-demo-preview">
						<?php if ( $demo->has_screenshot ) : ?>
							<img src="<?php echo esc_url( $this->get_screenshot_url( $demo->slug ) ); ?>" alt="<?php echo esc_attr( $demo->name ); ?>" loading="lazy" />
						<?php else : ?>
							<div class="phantom-demo-preview-placeholder">
								<span class="dashicons dashicons-layout"></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="phantom-demo-info">
						<h3><?php echo esc_html( $demo->name ); ?></h3>
						<span class="phantom-demo-version">v<?php echo esc_html( $demo->version ); ?></span>
						<?php if ( $is_active ) : ?>
							<span class="phantom-demo-badge active"><?php esc_html_e( 'Active', 'phantom-core' ); ?></span>
						<?php elseif ( ! $demo->is_compatible ) : ?>
							<span class="phantom-demo-badge incompatible"><?php esc_html_e( 'Incompatible', 'phantom-core' ); ?></span>
						<?php elseif ( ! $can_activate['pass'] ) : ?>
							<span class="phantom-demo-badge warning"><?php esc_html_e( 'Issues', 'phantom-core' ); ?></span>
						<?php else : ?>
							<span class="phantom-demo-badge inactive"><?php esc_html_e( 'Inactive', 'phantom-core' ); ?></span>
						<?php endif; ?>
						<p><?php echo esc_html( $demo->description ); ?></p>
						<div class="phantom-demo-actions">
							<?php if ( $is_active ) : ?>
								<button class="button deactivate-demo" data-slug="<?php echo esc_attr( $demo->slug ); ?>">
									<?php esc_html_e( 'Deactivate', 'phantom-core' ); ?>
								</button>
							<?php else : ?>
								<button class="button button-primary activate-demo" data-slug="<?php echo esc_attr( $demo->slug ); ?>" <?php echo ! $demo->is_compatible ? 'disabled' : ''; ?>>
									<?php esc_html_e( 'Activate', 'phantom-core' ); ?>
								</button>
							<?php endif; ?>
							<button class="button delete-demo" data-slug="<?php echo esc_attr( $demo->slug ); ?>" <?php echo $is_active ? 'disabled' : ''; ?>>
								<?php esc_html_e( 'Delete', 'phantom-core' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<div id="phantom-demo-modal" class="phantom-modal" style="display:none;">
				<div class="phantom-modal-backdrop"></div>
				<div class="phantom-modal-content">
					<div class="phantom-modal-header">
						<h2 id="phantom-modal-title"><?php esc_html_e( 'Activate Demo', 'phantom-core' ); ?></h2>
						<button class="phantom-modal-close button">&times;</button>
					</div>
					<div class="phantom-modal-body" id="phantom-modal-body"></div>
					<div class="phantom-modal-footer">
						<button class="button phantom-modal-cancel"><?php esc_html_e( 'Cancel', 'phantom-core' ); ?></button>
						<button class="button button-primary phantom-modal-confirm" id="phantom-modal-confirm">
							<?php esc_html_e( 'Activate Now', 'phantom-core' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function ajax_activate(): void {
		check_admin_referer( 'phantom_demo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'phantom-core' ) ) );
		}

		$slug = sanitize_key( $_POST['slug'] ?? '' );
		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid demo slug.', 'phantom-core' ) ) );
		}

		$result = $this->switcher->activate( $slug );

		if ( $result->success ) {
			wp_send_json_success(
				array(
					'message' => $result->message,
					'slug'    => $slug,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => $result->message,
					'errors'  => $result->errors,
				)
			);
		}
	}

	public function ajax_activate_precheck(): void {
		check_admin_referer( 'phantom_demo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'phantom-core' ) ) );
		}

		$slug = sanitize_key( $_POST['slug'] ?? '' );
		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid demo slug.', 'phantom-core' ) ) );
		}

		$checks = $this->switcher->can_activate( $slug );
		wp_send_json_success( $checks );
	}

	public function ajax_deactivate(): void {
		check_admin_referer( 'phantom_demo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'phantom-core' ) ) );
		}

		$result = $this->switcher->deactivate();

		if ( $result->success ) {
			wp_send_json_success( array( 'message' => $result->message ) );
		} else {
			wp_send_json_error( array( 'message' => $result->message ) );
		}
	}

	public function ajax_delete(): void {
		check_admin_referer( 'phantom_demo_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'phantom-core' ) ) );
		}

		$slug = sanitize_key( $_POST['slug'] ?? '' );
		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid demo slug.', 'phantom-core' ) ) );
		}

		$result = $this->installer->delete( $slug );

		if ( $result->success ) {
			wp_send_json_success( array( 'message' => $result->message ) );
		} else {
			wp_send_json_error(
				array(
					'message' => $result->message,
					'errors'  => $result->errors,
				)
			);
		}
	}

	public function handle_install(): void {
		if ( ! isset( $_POST['phantom_install_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['phantom_install_nonce'] ) ), 'phantom_install_demo' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'phantom-core' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'phantom-core' ) );
		}

		if ( ! isset( $_FILES['demo_zip'] ) || UPLOAD_ERR_OK !== $_FILES['demo_zip']['error'] ) {
			wp_die( esc_html__( 'Upload failed.', 'phantom-core' ) );
		}

		$result = $this->installer->install( sanitize_text_field( $_FILES['demo_zip']['tmp_name'] ) );

		if ( $result->success ) {
			wp_safe_redirect( add_query_arg( 'demo_installed', '1', wp_get_referer() ) );
			exit;
		}

		wp_die( esc_html( implode( '<br>', $result->errors ) ) );
	}

	private function get_screenshot_url( string $slug ): string {
		$screenshot_path = PHANTOM_CORE_PATH . 'frontend/templates/' . $slug . '/preview.jpg';
		if ( file_exists( $screenshot_path ) ) {
			return PHANTOM_CORE_URL . 'frontend/templates/' . $slug . '/preview.jpg';
		}
		return PHANTOM_CORE_URL . 'admin/images/no-preview.svg';
	}
}
