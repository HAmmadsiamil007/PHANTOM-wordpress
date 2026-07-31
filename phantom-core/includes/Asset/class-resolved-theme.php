<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

use PhantomCore\Components\ComponentInstance;

defined('ABSPATH') || exit;

class ResolvedTheme {
    public array $instances = [];
    public array $design_tokens = [];
    public array $preset = [];
    public string $active_preset_name = '';
    public array $plugin_overrides = [];
    public string $component_registry_version = '';

    public function __construct(
        array $instances = [],
        array $design_tokens = [],
        array $preset = [],
        string $active_preset_name = '',
        array $plugin_overrides = [],
        string $component_registry_version = ''
    ) {
        $this->instances = $instances;
        $this->design_tokens = $design_tokens;
        $this->preset = $preset;
        $this->active_preset_name = $active_preset_name;
        $this->plugin_overrides = $plugin_overrides;
        $this->component_registry_version = $component_registry_version;
    }
}
