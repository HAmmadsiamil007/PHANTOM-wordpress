<?php
declare(strict_types=1);

namespace PhantomCore\Asset\Pipeline;

use PhantomCore\Asset\CSS\CSS_Compiler;
use PhantomCore\Asset\CSS\CSS_Optimizer;
use PhantomCore\Asset\Theme_State_Engine;
use PhantomCore\Asset\Version_Manager;
use PhantomCore\Asset\Manifest;
use PhantomCore\Asset\CSS_Cache_Manager;

defined('ABSPATH') || exit;

class Pipeline {
    private static ?self $instance = null;
    private CSS_Compiler $compiler;
    private CSS_Optimizer $optimizer;
    private Version_Manager $version_manager;
    private Manifest $manifest;
    private Theme_State_Engine $state_engine;
    private Build_Queue $queue;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->compiler = new CSS_Compiler();
        $this->optimizer = new CSS_Optimizer();
        $this->version_manager = Version_Manager::get_instance();
        $this->manifest = Manifest::get_instance();
        $this->state_engine = Theme_State_Engine::get_instance();
        $this->queue = Build_Queue::get_instance();
    }

    public function process_next(): array {
        $item = $this->queue->next();
        if (null === $item) {
            return ['success' => false, 'message' => 'Queue is empty'];
        }

        try {
            $result = $this->execute($item['type'], $item['params']);
        } finally {
            $this->queue->dequeue();
        }

        return $result;
    }

    public function execute(string $type, array $params = []): array {
        $profile = $params['profile'] ?? 'production';

        $theme = $this->state_engine->get_resolved_theme();
        $sections = $this->compiler->compile($theme, $profile);

        foreach ($sections as $key => $css) {
            $sections[$key] = $this->optimizer->optimize($css, $profile);
        }

        $all_css = implode('', $sections);
        $new_hash = $this->version_manager->version($all_css, $theme);

        $active_manifest = $this->manifest->get_active();
        if ($active_manifest['version'] === $new_hash) {
            return [
                'success' => true,
                'version' => $new_hash,
                'changed' => false,
                'message' => 'CSS unchanged, skipping write',
            ];
        }

        $css_dir = \PhantomCore\Asset\get_css_dir();
        \PhantomCore\Asset\ensure_dirs();

        foreach ($sections as $section => $css_content) {
            if (empty($css_content)) {
                continue;
            }
            $filename = "{$section}-{$new_hash}.css";
            $filepath = trailingslashit($css_dir) . $filename;
            file_put_contents($filepath, $css_content);
        }

        $this->manifest->update_css_build($new_hash, $sections, $profile);

        CSS_Cache_Manager::get_instance()->cleanup($new_hash);

        return [
            'success' => true,
            'version' => $new_hash,
            'changed' => true,
            'sections' => array_keys($sections),
            'profile' => $profile,
        ];
    }

    public function get_build_history(): array {
        $manifest = $this->manifest->get_active();
        $css_dir = \PhantomCore\Asset\get_css_dir();
        $files = glob(trailingslashit($css_dir) . 'theme-*.css');
        $history = [];

        if ($files) {
            foreach ($files as $filepath) {
                $filename = basename($filepath);
                if (preg_match('/theme-([a-f0-9]+)\.css/', $filename, $m)) {
                    $hash = $m[1];
                    $history[] = [
                        'version' => $hash,
                        'file'    => $filename,
                        'size'    => filesize($filepath),
                        'date'    => date('Y-m-d H:i:s', filemtime($filepath)),
                        'active'  => $hash === $manifest['version'],
                    ];
                }
            }
        }

        usort($history, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $history;
    }

    public function activate_version(string $version_hash): bool {
        $manifest = $this->manifest->get_active();
        $manifest['version'] = $version_hash;
        $manifest['date'] = current_time('c');
        return $this->manifest->write($manifest);
    }

    public function process_all(): array {
        $results = [];
        while ($this->queue->next()) {
            $results[] = $this->process_next();
        }
        return $results;
    }
}
