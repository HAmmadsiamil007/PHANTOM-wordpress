<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

defined('ABSPATH') || exit;

class Version_Manager {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function version(string $css, ResolvedTheme $theme): string {
        return substr(
            md5($css . PHANTOM_CORE_VERSION . $theme->active_preset_name . $theme->component_registry_version),
            0,
            12
        );
    }
}
