<?php
declare(strict_types=1);

namespace PhantomCore;

use PhantomCore\Settings_Registry;

defined( 'ABSPATH' ) || exit;

class Plugin {

	private static ?Plugin $instance = null;

	final public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$this->init_registries();
		$this->register_nav_menus();
		$this->register_widget_areas();
		\Phantom_Global_Palette::instance()->init();
		do_action( 'phantom_core/init' );
	}

	private function init_registries(): void {
		Settings_Registry::get_instance()->register();
	}

	private function register_nav_menus(): void {
		add_action(
			'after_setup_theme',
			function (): void {
				register_nav_menus(
					apply_filters( 'phantom_nav_menu_locations', array(
						'phantom_primary'   => __( 'Primary Menu', 'phantom-core' ),
						'phantom_secondary' => __( 'Secondary Menu', 'phantom-core' ),
						'phantom_footer'    => __( 'Footer Menu', 'phantom-core' ),
						'phantom_mobile'    => __( 'Mobile Menu', 'phantom-core' ),
						'primary'           => __( 'Primary Menu (Theme Compat)', 'phantom-core' ),
						'footer'            => __( 'Footer Menu (Theme Compat)', 'phantom-core' ),
					) )
				);
			}
		);
	}

	private function register_widget_areas(): void {
		add_action(
			'widgets_init',
			function (): void {
				$widget_areas = array(
					array(
						'id'          => 'phantom-sidebar-main',
						'name'        => __( 'Main Sidebar', 'phantom-core' ),
						'description' => __( 'Main blog sidebar', 'phantom-core' ),
					),
					array(
						'id'          => 'phantom-sidebar-shop',
						'name'        => __( 'Shop Sidebar', 'phantom-core' ),
						'description' => __( 'WooCommerce shop sidebar', 'phantom-core' ),
					),
					array(
						'id'          => 'phantom-sidebar-blog',
						'name'        => __( 'Blog Sidebar', 'phantom-core' ),
						'description' => __( 'Blog archive/single sidebar', 'phantom-core' ),
					),
				);

				foreach ( $widget_areas as $area ) {
					register_sidebar(
						array(
							'id'            => $area['id'],
							'name'          => $area['name'],
							'description'   => $area['description'],
							'before_widget' => '<section id="%1$s" class="widget %2$s">',
							'after_widget'  => '</section>',
							'before_title'  => '<h3 class="widget-title">',
							'after_title'   => '</h3>',
						)
					);
				}

				for ( $i = 1; $i <= 4; $i++ ) {
					register_sidebar(
						array(
							'id'            => 'phantom-footer-' . $i,
							'name'          => sprintf( __( 'Footer Widgets %d', 'phantom-core' ), $i ),
							'description'   => sprintf( __( 'Footer widget column %d', 'phantom-core' ), $i ),
							'before_widget' => '<div id="%1$s" class="col-lg-3 col-md-6 widget %2$s">',
							'after_widget'  => '</div>',
							'before_title'  => '<h3 class="widget-title">',
							'after_title'   => '</h3>',
						)
					);
				}
			}
		);
	}
}
