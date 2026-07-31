<?php
declare(strict_types=1);

namespace PhantomCore\Asset\CSS;

use PhantomCore\Asset\ResolvedTheme;
use PhantomCore\Components\ComponentInstance;

defined('ABSPATH') || exit;

class Variable_Generator {
    public function generate(ResolvedTheme $theme): array {
        $output = [];

        foreach ($theme->instances as $instance) {
            $component_name = $instance->component_name;
            $vars = [];

            foreach ($instance->overrides as $token => $value) {
                $var_name = $this->token_to_var($token);
                $vars[$var_name] = $value;
            }

            $output[$component_name] = [
                'vars' => $vars,
                'state_overrides' => $instance->state_overrides,
                'viewport_overrides' => $instance->viewport_overrides,
            ];
        }

        return $output;
    }

    public function token_to_var(string $token): string {
        return '--' . str_replace(['.', '_'], '-', $token);
    }

    public function render_vars(array $vars): string {
        if (empty($vars)) {
            return '';
        }
        $lines = [];
        foreach ($vars as $var_name => $value) {
            $lines[] = "\t{$var_name}: {$value};";
        }
        return implode("\n", $lines);
    }
}
