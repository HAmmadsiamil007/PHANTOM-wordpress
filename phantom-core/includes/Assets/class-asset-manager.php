<?php
declare(strict_types=1);

namespace PhantomCore\Assets;

use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

/**
 * Asset_Manager — orchestrator that integrates Asset_Registry, Resolver, Validator, and Optimizer.
 *
 * @package PhantomCore\Assets
 */
class Asset_Manager {

    private static ?self $instance = null;

    private Asset_Registry $registry;
    private Asset_Resolver $resolver;
    private Asset_Validator $validator;
    private Asset_Optimizer $optimizer;

    /** @var array<string, array> Last asset info cache. */
    private array $infoCache = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->registry  = Asset_Registry::get_instance();
        $this->resolver  = Asset_Resolver::get_instance();
        $this->validator = Asset_Validator::get_instance();
        $this->optimizer = Asset_Optimizer::get_instance();
    }

    /**
     * Initialize: register default assets.
     */
    public function init(): void {
        $this->registry->register_defaults();
    }

    /**
     * Get full asset info for a single handle.
     *
     * Returns the asset definition, resolved URL, validation status, and optimization hints.
     *
     * @param string $handle Asset handle.
     * @return array|null
     */
    public function get_asset_info(string $handle): ?array {
        if (isset($this->infoCache[$handle])) {
            return $this->infoCache[$handle];
        }

        $asset = $this->registry->get($handle);
        if (null === $asset) {
            return null;
        }

        $resolved  = $this->resolver->resolve($handle, $asset);
        $validated = $this->validator->validate($handle, $asset);

        $info = array_merge(
            ['handle' => $handle],
            $asset,
            [
                'resolved'  => $resolved,
                'validation' => $validated,
            ]
        );

        $this->infoCache[$handle] = $info;
        return $info;
    }

    /**
     * Get comprehensive asset info for all registered assets.
     *
     * @param string|null $type Filter by type ('css', 'js', or null for all).
     * @return array{assets: array, summary: array, performance: array}
     */
    public function get_all_asset_info(?string $type = null): array {
        $assets      = $this->registry->get_all($type);
        $assetInfos  = [];
        $cssCount    = 0;
        $jsCount     = 0;

        foreach ($assets as $handle => $asset) {
            $info = $this->get_asset_info($handle);
            if ($info) {
                $assetInfos[$handle] = $info;
                if ('css' === ($info['type'] ?? '')) $cssCount++;
                if ('js' === ($info['type'] ?? '')) $jsCount++;
            }
        }

        $health    = $this->validator->get_health_report($assets);
        $perf      = $this->optimizer->get_performance_summary();

        // Group analysis
        $analysis  = $this->optimizer->analyze($assets);
        $hints     = $this->optimizer->generate_hints($assets);

        return [
            'assets'   => $assetInfos,
            'summary'  => [
                'total'     => count($assetInfos),
                'css'       => $cssCount,
                'js'        => $jsCount,
                'health'    => $health,
                'cdn'       => $this->resolver->get_cdn_status(),
            ],
            'performance' => [
                'score'          => $perf,
                'optimization'   => $analysis,
                'htmlHints'      => $hints,
            ],
        ];
    }

    /**
     * Get assets grouped by their group membership.
     *
     * @return array<string, array>
     */
    public function get_asset_groups(): array {
        $registry  = Asset_Registry::get_instance();
        $allAssets = $registry->get_all();
        $groups    = [];

        foreach ($allAssets as $handle => $asset) {
            // Assets without explicit group go to 'ungrouped'
            $groups['ungrouped'][$handle] = $asset;
        }

        // Get group memberships from the registry
        $groupMap    = $registry->get_groups();

        $result = [];
        foreach ($groupMap as $group => $handles) {
            $result[$group] = [];
            foreach ($handles as $h) {
                if (isset($allAssets[$h])) {
                    $result[$group][$h] = $allAssets[$h];
                }
            }
        }

        return $result;
    }

    /**
     * Clear all caches.
     */
    public function clear_cache(): void {
        $this->infoCache = [];
        $this->resolver->clear_cache();
        $this->validator->clear_cache();
    }
}
