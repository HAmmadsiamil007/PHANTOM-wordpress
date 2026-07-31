<?php
declare(strict_types=1);

namespace PhantomCore\Asset\CSS;

use PhantomCore\Inspector\State_Manager;

defined('ABSPATH') || exit;

class Rule_Builder {
    private Variable_Generator $var_gen;

    public function __construct() {
        $this->var_gen = new Variable_Generator();
    }

    public function build(
        string $component_name,
        array $component_data,
        string $token_group = ''
    ): string {
        $selector = $token_group ?: str_replace('_', '-', $component_name);
        $vars = $component_data['vars'] ?? [];
        $state_overrides = $component_data['state_overrides'] ?? [];
        $viewport_overrides = $component_data['viewport_overrides'] ?? [];

        if (empty($vars) && empty($state_overrides) && empty($viewport_overrides)) {
            return '';
        }

        $parts = [];

        if (!empty($vars)) {
            $var_block = $this->var_gen->render_vars($vars);
            $parts[] = "[data-vc-instance=\"{$component_name}\"] {\n{$var_block}\n}";
        }

        $state_labels = [
            'hover' => 'hover',
            'focus' => 'focus',
            'active' => 'active',
            'disabled' => 'disabled',
        ];
        foreach ($state_overrides as $state => $state_vars) {
            if (empty($state_vars)) {
                continue;
            }
            $pseudo = $state_labels[$state] ?? $state;
            $var_block = $this->var_gen->render_vars($state_vars);
            $parts[] = "[data-vc-instance=\"{$component_name}\"]:{$pseudo} {\n{$var_block}\n}";
        }

        $breakpoints = State_Manager::BREAKPOINTS;
        uksort($viewport_overrides, function ($a, $b) use ($breakpoints) {
            $aw = $breakpoints[$a]['max_width'] ?? 9999;
            $bw = $breakpoints[$b]['max_width'] ?? 9999;
            return $aw <=> $bw;
        });
        foreach ($viewport_overrides as $viewport => $vp_vars) {
            if (empty($vp_vars) || 'desktop' === $viewport) {
                continue;
            }
            $max_width = $breakpoints[$viewport]['max_width'] ?? null;
            if (null === $max_width) {
                continue;
            }
            $var_block = $this->var_gen->render_vars($vp_vars);
            $parts[] = "@media (max-width: {$max_width}px) {\n[data-vc-instance=\"{$component_name}\"] {\n{$var_block}\n}\n}";
        }

        return implode("\n\n", $parts);
    }
}
