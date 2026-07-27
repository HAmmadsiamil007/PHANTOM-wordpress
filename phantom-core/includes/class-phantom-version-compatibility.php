<?php
declare(strict_types=1);

namespace PhantomCore;

use PhantomCore\Upgrade\Upgrade_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Version_Compatibility — now delegates to Upgrade_Manager.
 *
 * Kept as a thin wrapper for backward compatibility.
 * All migration logic lives in Upgrade_Manager.
 */
class Version_Compatibility {

	private static ?self $instance = null;

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		Upgrade_Manager::get_instance()->init();
	}

	public function is_upgraded(): bool {
		$version = Upgrade_Manager::get_instance()->get_current_db_version();
		return version_compare( $version, '1.5.0', '>=' );
	}
}
