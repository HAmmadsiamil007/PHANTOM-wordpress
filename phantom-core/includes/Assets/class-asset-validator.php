<?php
declare(strict_types=1);

namespace PhantomCore\Assets;

defined('ABSPATH') || exit;

/**
 * Asset_Validator — validates asset existence, MIME types, size, and integrity.
 *
 * @package PhantomCore\Assets
 */
class Asset_Validator {

    private static ?self $instance = null;

    /** @var array<string, array> Cached validation results. */
    private array $validationCache = [];

    /** @var string[] Allowed CSS MIME types. */
    private const ALLOWED_CSS_MIMES = ['text/css', 'application/x-css'];

    /** @var string[] Allowed JS MIME types. */
    private const ALLOWED_JS_MIMES = [
        'application/javascript',
        'text/javascript',
        'application/x-javascript',
        'application/ecmascript',
    ];

    /** @var string[] Allowed image MIME types. */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/avif',
    ];

    /** @var int Maximum allowed file size in bytes (5 MB default). */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Validate a single asset by its definition.
     *
     * @param string $handle Asset handle.
     * @param array  $asset  Asset definition with 'src', 'type' keys.
     * @return array{valid: bool, exists: bool, mime: string|null, mimeValid: bool, size: int|null, sizeValid: bool, error: string|null}
     */
    public function validate(string $handle, array $asset): array {
        if (isset($this->validationCache[$handle])) {
            return $this->validationCache[$handle];
        }

        $src     = $asset['src'] ?? '';
        $type    = $asset['type'] ?? 'js';
        $version = $asset['version'] ?? PHANTOM_CORE_VERSION;

        $result = [
            'valid'     => false,
            'exists'    => false,
            'mime'      => null,
            'mimeValid' => false,
            'size'      => null,
            'sizeValid' => false,
            'error'     => null,
        ];

        // Empty source check
        if (empty($src)) {
            $result['error'] = 'Empty source URL.';
            $this->validationCache[$handle] = $result;
            return $result;
        }

        // Check if it's a local plugin file
        $localFile = $this->src_to_local_path($src);
        if (null !== $localFile) {
            return $this->validate_local_file($handle, $localFile, $type);
        }

        // Remote URL — check reachability via HTTP headers
        return $this->validate_remote_url($handle, $src, $type, $version);
    }

    /**
     * Validate multiple assets at once.
     *
     * @param array<string, array> $assets Keyed by handle.
     * @return array<string, array{valid: bool, exists: bool, mime: string|null, mimeValid: bool, size: int|null, sizeValid: bool, error: string|null}>
     */
    public function validate_bulk(array $assets): array {
        $results = [];
        foreach ($assets as $handle => $asset) {
            $results[$handle] = $this->validate($handle, $asset);
        }
        return $results;
    }

    /**
     * Get a summary report of asset health.
     *
     * @param array<string, array> $assets Keyed by handle.
     * @return array{total: int, valid: int, invalid: int, missing: int, mimeIssues: int, totalSize: string}
     */
    public function get_health_report(array $assets): array {
        $results    = $this->validate_bulk($assets);
        $total      = count($results);
        $valid      = 0;
        $invalid    = 0;
        $missing    = 0;
        $mimeIssues = 0;
        $totalSize  = 0;

        foreach ($results as $r) {
            if ($r['valid']) {
                $valid++;
            } else {
                $invalid++;
            }
            if (!$r['exists']) {
                $missing++;
            }
            if (!$r['mimeValid'] && $r['mime'] !== null) {
                $mimeIssues++;
            }
            $totalSize += $r['size'] ?? 0;
        }

        return [
            'total'      => $total,
            'valid'      => $valid,
            'invalid'    => $invalid,
            'missing'    => $missing,
            'mimeIssues' => $mimeIssues,
            'totalSize'  => size_format($totalSize, 2) ?: '0 B',
        ];
    }

    /**
     * Convert a URL to a local file path if possible.
     */
    private function src_to_local_path(string $src): ?string {
        $pluginUrl = PHANTOM_CORE_URL;
        if (!str_starts_with($src, $pluginUrl)) {
            return null;
        }
        $relative = substr($src, strlen($pluginUrl));
        return PHANTOM_CORE_PATH . $relative;
    }

    /**
     * Validate a local file.
     */
    private function validate_local_file(string $handle, string $filePath, string $type): array {
        $result = [
            'valid'     => false,
            'exists'    => false,
            'mime'      => null,
            'mimeValid' => false,
            'size'      => null,
            'sizeValid' => false,
            'error'     => null,
        ];

        if (!file_exists($filePath)) {
            $result['error'] = 'File not found: ' . basename($filePath);
            $this->validationCache[$handle] = $result;
            return $result;
        }

        $result['exists'] = true;
        $result['size']   = (int) filesize($filePath);
        $result['sizeValid'] = $result['size'] <= self::MAX_FILE_SIZE;

        // Determine MIME type from extension when finfo isn't available
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (function_exists('finfo_open')) {
            $finfo         = finfo_open(FILEINFO_MIME_TYPE);
            $result['mime'] = finfo_file($finfo, $filePath);
            finfo_close($finfo);
        } else {
            $mimeMap = [
                'css' => 'text/css',
                'js'  => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg'=> 'image/jpeg',
                'gif' => 'image/gif',
                'webp'=> 'image/webp',
                'svg' => 'image/svg+xml',
                'avif'=> 'image/avif',
                'woff'=> 'font/woff',
                'woff2'=> 'font/woff2',
                'ttf' => 'font/ttf',
            ];
            $result['mime'] = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        $result['mimeValid'] = $this->is_mime_allowed($result['mime'], $type);
        $result['valid']     = $result['exists'] && $result['mimeValid'];

        $this->validationCache[$handle] = $result;
        return $result;
    }

    /**
     * Validate a remote URL.
     */
    private function validate_remote_url(string $handle, string $url, string $type, string $version): array {
        $result = [
            'valid'     => false,
            'exists'    => false,
            'mime'      => null,
            'mimeValid' => false,
            'size'      => null,
            'sizeValid' => false,
            'error'     => null,
        ];

        // Only validate URLs that match known CDN patterns (skip validation for user URLs)
        $knownCdns = ['cdn.jsdelivr.net', 'cdnjs.cloudflare.com', 'unpkg.com', 'fonts.googleapis.com', 'fonts.gstatic.com'];
        $host      = parse_url($url, PHP_URL_HOST);

        if (!in_array($host, $knownCdns, true)) {
            // Unknown host — assume valid to avoid blocking custom URLs
            $result['valid'] = true;
            $result['exists'] = true;
            $result['mimeValid'] = true;
            $this->validationCache[$handle] = $result;
            return $result;
        }

        // Validate known CDN URLs with HEAD request
        $response = wp_remote_head($url, [
            'timeout' => 5,
            'headers' => [
                'Accept' => '*/*',
            ],
        ]);

        if (is_wp_error($response)) {
            $result['error'] = 'HTTP error: ' . $response->get_error_message();
            $this->validationCache[$handle] = $result;
            return $result;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        if ($statusCode < 200 || $statusCode >= 400) {
            $result['error'] = "HTTP {$statusCode}";
            $this->validationCache[$handle] = $result;
            return $result;
        }

        $result['exists']  = true;
        $result['mime']    = wp_remote_retrieve_header($response, 'content-type');
        $result['size']    = (int) wp_remote_retrieve_header($response, 'content-length');
        $result['sizeValid'] = $result['size'] <= self::MAX_FILE_SIZE;

        // Clean MIME (remove charset suffix)
        $cleanMime = $result['mime'] ? strtok($result['mime'], ';') : null;
        if ($cleanMime) {
            $result['mime']      = $cleanMime;
            $result['mimeValid'] = $this->is_mime_allowed($cleanMime, $type);
        } else {
            $result['mimeValid'] = true; // Unknown MIME but URL resolved
        }

        $result['valid'] = $result['exists'];
        $this->validationCache[$handle] = $result;
        return $result;
    }

    /**
     * Check if MIME type matches expected asset type.
     */
    private function is_mime_allowed(string $mime, string $type): bool {
        if ('css' === $type) {
            return in_array($mime, self::ALLOWED_CSS_MIMES, true);
        }
        if ('js' === $type) {
            return in_array($mime, self::ALLOWED_JS_MIMES, true);
        }
        if ('image' === $type || str_starts_with($mime, 'image/')) {
            return in_array($mime, self::ALLOWED_IMAGE_MIMES, true);
        }
        return true; // Unknown type — allow
    }

    /**
     * Clear the validation cache.
     */
    public function clear_cache(): void {
        $this->validationCache = [];
    }
}
