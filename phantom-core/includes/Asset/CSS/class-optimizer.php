<?php
declare(strict_types=1);

namespace PhantomCore\Asset\CSS;

defined('ABSPATH') || exit;

class CSS_Optimizer {
    public function optimize(string $css, string $profile = 'production'): string {
        if (empty(trim($css))) {
            return '';
        }

        $css = $this->merge_media_queries($css);

        $css = preg_replace('/[^{]*\{\s*\}/', '', $css);

        $css = $this->deduplicate_properties($css);

        if ('production' === $profile || 'export' === $profile) {
            $css = $this->minify($css);
        }

        return trim($css);
    }

    private function merge_media_queries(string $css): string {
        preg_match_all('/@media[^{]+\{(?:[^{}]*|\{[^{}]*\})*\}/s', $css, $matches);
        $media_blocks = $matches[0] ?? [];

        if (count($media_blocks) <= 1) {
            return $css;
        }

        $grouped = [];
        foreach ($media_blocks as $block) {
            preg_match('/@media\s*([^{]+)\s*\{/', $block, $query_match);
            $query = trim($query_match[1] ?? '');
            $body = preg_replace('/@media[^{]+\{/', '', $block);
            $body = substr($body, 0, -1);

            if (!isset($grouped[$query])) {
                $grouped[$query] = [];
            }
            $grouped[$query][] = trim($body);
        }

        $merged = '';
        foreach ($grouped as $query => $bodies) {
            $merged .= "@media {$query} {\n" . implode("\n", $bodies) . "\n}\n";
        }

        $css = preg_replace('/@media[^{]+\{(?:[^{}]*|\{[^{}]*\})*\}/s', '', $css);
        return trim($css) . "\n\n" . trim($merged);
    }

    private function deduplicate_properties(string $css): string {
        $lines = explode("\n", $css);
        $result = [];
        $current_block_props = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*--[\w-]+:\s*[^;]+;/', $line)) {
                $prop_name = trim(explode(':', $line)[0]);
                $current_block_props[$prop_name] = $line;
            } else {
                if (!empty($current_block_props)) {
                    $result[] = implode("\n", $current_block_props);
                    $current_block_props = [];
                }
                $result[] = $line;
            }
        }
        if (!empty($current_block_props)) {
            $result[] = implode("\n", $current_block_props);
        }

        return implode("\n", $result);
    }

    private function minify(string $css): string {
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        $css = preg_replace('/;\}/', '}', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        return trim($css);
    }
}
