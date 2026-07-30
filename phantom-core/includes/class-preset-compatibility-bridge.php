<?php
declare(strict_types=1);

namespace PhantomCore;

defined('ABSPATH') || exit;

/**
 * Temporary bridge from Design System presets to legacy Phantom_Global_Palette.
 *
 * Maps 7 core preset slugs to 3 legacy palette slugs so the old global palette
 * continues working during the migration.
 *
 * @deprecated Will be removed in Phase 10 when Phantom_Global_Palette is retired.
 */
class Preset_Compatibility_Bridge {

    private static array $preset_to_legacy = [
        'core:light'   => 'light',
        'core:dark'    => 'dark',
        'core:minimal' => 'light',
        'core:modern'  => 'vibrant',
        'core:luxury'  => 'dark',
        'core:classic' => 'light',
        'core:glass'   => 'vibrant',
    ];

    public static function sync(string $preset_slug): void {
        if (!isset(self::$preset_to_legacy[$preset_slug])) {
            return;
        }
        $legacy = self::$preset_to_legacy[$preset_slug];
        update_option('phantom_global_palette', [
            'current' => $legacy,
            'overrides' => [],
        ]);
    }
}
