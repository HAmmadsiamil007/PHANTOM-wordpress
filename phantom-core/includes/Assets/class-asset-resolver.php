<?php
declare(strict_types=1);

namespace PhantomCore\Assets;

defined('ABSPATH') || exit;

/**
 * Asset_Resolver — resolves asset paths with CDN fallback chains and versioning.
 *
 * @package PhantomCore\Assets
 */
class Asset_Resolver {

    private static ?self $instance = null;

    /** @var array<string, array> Cached resolved URLs by handle. */
    private array $resolvedCache = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Resolve a single asset handle to its final URL.
     *
     * Applies CDN prefix, versioning, and fallback chain.
     *
     * @param string $handle   Asset handle.
     * @param array  $asset    Asset definition (src, version, etc.).
     * @return array{url: string, resolved: bool, cdn: bool, version: string}
     */
    public function resolve(string $handle, array $asset): array {
        if (isset($this->resolvedCache[$handle])) {
            return $this->resolvedCache[$handle];
        }

        $originalSrc = $asset['src'] ?? '';
        $version     = $asset['version'] ?? PHANTOM_CORE_VERSION;
        $cdnEnabled  = '1' === get_option('phantom_asset_cdn_enabled', '0');
        $cdnUrl      = untrailingslashit(get_option('phantom_asset_cdn_url', ''));
        $useVersion  = '1' === get_option('phantom_asset_version_param', '1');

        $url       = $originalSrc;
        $usingCdn  = false;
        $resolved  = true;

        // Step 1: Replace local plugin URL with CDN if enabled
        if ($cdnEnabled && $cdnUrl && str_starts_with($url, PHANTOM_CORE_URL)) {
            $relativePath = substr($url, strlen(PHANTOM_CORE_URL));
            $cdnPath      = $cdnUrl . '/phantom-core/' . $relativePath;

            // Only use CDN if the file exists locally (confirming it's a valid asset)
            $localFile = PHANTOM_CORE_PATH . $relativePath;
            if (file_exists($localFile)) {
                $url      = $cdnPath;
                $usingCdn = true;
            }
        }

        // Step 2: Optionally replace CDN URLs (jsdelivr, cdnjs, unpkg) with custom CDN
        if ($cdnEnabled && $cdnUrl && !$usingCdn) {
            $externalCdnPatterns = [
                '//cdn.jsdelivr.net',
                '//cdnjs.cloudflare.com',
                '//unpkg.com',
            ];
            foreach ($externalCdnPatterns as $pattern) {
                if (str_contains($url, $pattern)) {
                    $relative  = parse_url($url, PHP_URL_PATH);
                    if ($relative) {
                        $proxyUrl = $cdnUrl . '/proxy' . $relative;
                        $url      = $proxyUrl;
                        $usingCdn = true;
                    }
                    break;
                }
            }
        }

        // Step 3: Append version query string
        if ($useVersion && !str_contains($url, '?v=') && !str_contains($url, '?ver=')) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url      .= $separator . 'v=' . $version;
        }

        $result = [
            'url'      => $url,
            'resolved' => $resolved,
            'cdn'      => $usingCdn,
            'version'  => (string) $version,
        ];

        $this->resolvedCache[$handle] = $result;
        return $result;
    }

    /**
     * Resolve multiple assets at once.
     *
     * @param array<string, array> $assets Keyed by handle.
     * @return array<string, array{url: string, resolved: bool, cdn: bool, version: string}>
     */
    public function resolve_bulk(array $assets): array {
        $results = [];
        foreach ($assets as $handle => $asset) {
            $results[$handle] = $this->resolve($handle, $asset);
        }
        return $results;
    }

    /**
     * Get the resolved CDN configuration status.
     *
     * @return array{enabled: bool, url: string, proxying: bool}
     */
    public function get_cdn_status(): array {
        $enabled = '1' === get_option('phantom_asset_cdn_enabled', '0');
        $url     = get_option('phantom_asset_cdn_url', '');
        return [
            'enabled'  => $enabled,
            'url'      => $url ?: '',
            'proxying' => $enabled && $url && '1' === get_option('phantom_asset_cdn_proxy', '0'),
        ];
    }

    /**
     * Clear the resolved URL cache.
     */
    public function clear_cache(): void {
        $this->resolvedCache = [];
    }

    /**
     * Build a fallback chain for an asset.
     *
     * @param string $handle Asset handle.
     * @param array  $asset  Asset definition.
     * @return string[] Ordered list of fallback URLs.
     */
    public function get_fallback_chain(string $handle, array $asset): array {
        $chain   = [];
        $src     = $asset['src'] ?? '';
        $version = $asset['version'] ?? PHANTOM_CORE_VERSION;

        // Primary: resolve normally
        $resolved = $this->resolve($handle, $asset);
        $chain[]  = $resolved['url'];

        // Fallback 1: original src with version
        $v = '?v=' . $version;
        if (!str_contains($src, $v)) {
            $chain[] = $src . $v;
        }

        // Fallback 2: original src without version
        if ($src !== $chain[0] && $src !== end($chain)) {
            $chain[] = $src;
        }

        return array_unique($chain);
    }
}
