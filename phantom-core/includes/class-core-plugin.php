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
		do_action( 'phantom_core/init' );
	}

	private function init_registries(): void {
		Settings_Registry::get_instance()->register();
	}
}
