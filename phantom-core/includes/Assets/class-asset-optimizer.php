<?php
declare(strict_types=1);

namespace PhantomCore\Assets;

defined('ABSPATH') || exit;

/**
 * Asset_Optimizer — generates optimization hints and performance recommendations.
 *
 * @package PhantomCore\Assets
 */
class Asset_Optimizer {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get optimization recommendations for a set of assets.
     *
     * @param array<string, array> $assets Keyed by handle.
     * @return array{hints: array, score: int, recommendations: string[]}
     */
    public function analyze(array $assets): array {
        $deferCandidates = [];
        $asyncCandidates = [];
        $preloadCandidates = [];
        $preconnectDomains = [];
        $score = 100;
        $recommendations = [];

        foreach ($assets as $handle => $asset) {
            $type = $asset['type'] ?? 'js';
            $src  = $asset['src'] ?? '';
            $lazy = $asset['lazy'] ?? false;

            // JS analysis
            if ('js' === $type) {
                // Non-critical JS should be deferred
                if (!$lazy && !$this->is_critical_js($handle)) {
                    $deferCandidates[] = $handle;
                }
            }

            // CSS analysis
            if ('css' === $type) {
                $footer = $asset['footer'] ?? false;
                if ($footer) {
                    $asyncCandidates[] = $handle;
                }
                // Render-blocking CSS that could be preloaded
                if (!$footer && !$this->is_critical_css($handle)) {
                    $preloadCandidates[] = $handle;
                }
            }

            // Extract preconnect domains
            $host = parse_url($src, PHP_URL_HOST);
            if ($host && !in_array($host, [parse_url(home_url(), PHP_URL_HOST), 'localhost'], true)) {
                $preconnectDomains[] = $host;
            }
        }

        // Score deductions
        if (!empty($deferCandidates)) {
            $score -= min(15, count($deferCandidates) * 3);
            $recommendations[] = sprintf(
                'Defer %d non-critical JavaScript file(s): %s.',
                count($deferCandidates),
                implode(', ', array_slice($deferCandidates, 0, 5))
            );
        }

        if (!empty($asyncCandidates)) {
            $score -= min(10, count($asyncCandidates) * 3);
            $recommendations[] = sprintf(
                'Load %d CSS file(s) asynchronously: %s.',
                count($asyncCandidates),
                implode(', ', array_slice($asyncCandidates, 0, 3))
            );
        }

        if (!empty($preconnectDomains)) {
            $unique = array_unique($preconnectDomains);
            $score -= min(5, count($unique));
            $recommendations[] = sprintf(
                'Add preconnect hints for %d third-party domain(s): %s.',
                count($unique),
                implode(', ', array_slice($unique, 0, 5))
            );
        }

        return [
            'hints' => [
                'defer'       => $deferCandidates,
                'async'       => $asyncCandidates,
                'preload'     => $preloadCandidates,
                'preconnect'  => array_unique($preconnectDomains),
            ],
            'score'          => max(0, $score),
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate HTML optimization tags for a set of assets.
     *
     * @param array<string, array> $assets Keyed by handle.
     * @return array{preload: string[], preconnect: string[], defer: string[], async: string[]}
     */
    public function generate_hints(array $assets): array {
        $analysis  = $this->analyze($assets);
        $hints     = $analysis['hints'];
        $generated = [
            'preload'    => [],
            'preconnect' => [],
            'defer'      => [],
            'async'      => [],
        ];

        // Preload hints
        foreach ($hints['preload'] as $handle) {
            if (isset($assets[$handle])) {
                $src       = $assets[$handle]['src'] ?? '';
                $as        = ('css' === ($assets[$handle]['type'] ?? 'js')) ? 'style' : 'script';
                $generated['preload'][] = sprintf(
                    '<link rel="preload" href="%s" as="%s" />',
                    esc_url($src),
                    $as
                );
            }
        }

        // Preconnect hints
        foreach ($hints['preconnect'] as $domain) {
            $generated['preconnect'][] = sprintf(
                '<link rel="preconnect" href="%s" crossorigin />',
                esc_url((str_starts_with($domain, 'http') ? '' : 'https://') . $domain)
            );
        }

        return $generated;
    }

    /**
     * Get a performance score summary.
     *
     * @return array{score: int, grade: string, recommendations: string[]}
     */
    public function get_performance_summary(): array {
		$registry  = \PhantomCore\Registry\Asset_Registry::get_instance();
		$assets    = $registry->get_all();
        $analysis  = $this->analyze($assets);

        $grade = 'A';
        if ($analysis['score'] < 50) {
            $grade = 'F';
        } elseif ($analysis['score'] < 70) {
            $grade = 'D';
        } elseif ($analysis['score'] < 80) {
            $grade = 'C';
        } elseif ($analysis['score'] < 90) {
            $grade = 'B';
        }

        return [
            'score'          => $analysis['score'],
            'grade'          => $grade,
            'recommendations' => $analysis['recommendations'],
        ];
    }

    /**
     * Check if a JS handle is considered critical (should not be deferred).
     */
    private function is_critical_js(string $handle): bool {
        $critical = [
            'phantom-injector-js',
            'phantom-bridge-js',
            'phantom-nonces',
            'phantom-main-js',
            'bootstrap-js',
            'jquery',
        ];
        return in_array($handle, $critical, true);
    }

    /**
     * Check if a CSS handle is considered critical (should not be deferred).
     */
    private function is_critical_css(string $handle): bool {
        $critical = [
            'phantom-theme-style',
            'bootstrap-css',
            'phantom-theme-responsive',
            'phantom-a11y-css',
        ];
        return in_array($handle, $critical, true);
    }
}
