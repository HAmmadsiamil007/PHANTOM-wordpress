<?php
declare(strict_types=1);

namespace PhantomCore\Asset\CSS;

use PhantomCore\Asset\Theme_State_Engine;

defined('ABSPATH') || exit;

class CSS_Compiler {
    private Variable_Generator $var_gen;
    private Rule_Builder $rule_builder;

    public function __construct() {
        $this->var_gen = new Variable_Generator();
        $this->rule_builder = new Rule_Builder();
    }

    public function compile(ResolvedTheme $theme, string $profile = 'development'): array {
        $sections = [
            'theme'       => '',
            'components'  => '',
            'woocommerce' => '',
            'responsive'  => '',
        ];

        $component_data = $this->var_gen->generate($theme);

        $component_rules = [];
        $wc_rules = [];
        $media_rules = [];

        foreach ($component_data as $component_name => $data) {
            $component = null;
            foreach ($theme->instances as $inst) {
                if ($inst->component_name === $component_name) {
                    $component = $inst;
                    break;
                }
            }
            $token_group = $component ? $component->token_group : '';

            $css = $this->rule_builder->build($component_name, $data, $token_group);

            if (empty($css)) {
                continue;
            }

            $media_pattern = '/@media[^{]+\{(?:[^{}]*|\{[^{}]*\})*\}/s';
            preg_match_all($media_pattern, $css, $media_matches);
            $media_blocks = $media_matches[0] ?? [];
            $css_without_media = preg_replace($media_pattern, '', $css);

            $media_rules = array_merge($media_rules, $media_blocks);

            if (str_starts_with($component_name, 'woocommerce') || str_starts_with($component_name, 'product')) {
                $wc_rules[] = trim($css_without_media);
            } else {
                $component_rules[] = trim($css_without_media);
            }
        }

        $token_css = $this->compile_design_tokens($theme);
        if ($token_css) {
            $sections['theme'] = $token_css;
        }

        $sections['components'] = implode("\n\n", array_filter($component_rules));
        $sections['woocommerce'] = implode("\n\n", array_filter($wc_rules));
        $sections['responsive'] = implode("\n\n", array_filter($media_rules));

        return array_filter($sections);
    }

    private function compile_design_tokens(ResolvedTheme $theme): string {
        if (empty($theme->design_tokens)) {
            return '';
        }
        $lines = [':root {'];
        foreach ($theme->design_tokens as $token_name => $token_value) {
            $var_name = '--' . str_replace(['.', '_'], '-', $token_name);
            $lines[] = "\t{$var_name}: {$token_value};";
        }
        $lines[] = '}';
        return implode("\n", $lines);
    }
}
