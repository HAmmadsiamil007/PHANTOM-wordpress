<?php
declare(strict_types=1);

namespace PhantomCore\Asset;

defined('ABSPATH') || exit;

function get_upload_dir(): string {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['basedir']) . 'phantom';
}

function get_css_dir(): string {
    return get_upload_dir() . '/css';
}

function get_css_url(): string {
    $upload_dir = wp_upload_dir();
    return trailingslashit($upload_dir['baseurl']) . 'phantom/css';
}

function ensure_dirs(): void {
    $dirs = [get_upload_dir(), get_css_dir()];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
    }
    foreach ([get_upload_dir(), get_css_dir()] as $dir) {
        if (!file_exists($dir . '/index.php')) {
            file_put_contents($dir . '/index.php', '<?php // Silence is golden.');
        }
    }
}
