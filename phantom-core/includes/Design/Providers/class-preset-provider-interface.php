<?php
declare(strict_types=1);

namespace PhantomCore\Design\Providers;

use PhantomCore\Design\Preset;

defined('ABSPATH') || exit;

interface PresetProviderInterface {
    public function get_presets(): array;
    public function get_preset(string $id): ?Preset;
    public function exists(string $id): bool;
    public function source(): string;
}
