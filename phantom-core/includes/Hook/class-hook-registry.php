<?php declare(strict_types=1);

namespace PhantomCore\Hook;

defined('ABSPATH') || exit;

class Hook_Registry {
    private static ?Hook_Registry $instance = null;
    private array $hooks = [];

    private function __construct() {}

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $hook, array $metadata = []): void {
        $this->hooks[$hook] = array_merge([
            'type' => 'action',
            'callback' => null,
            'priority' => 10,
            'accepted_args' => 1,
            'description' => '',
            'registered' => time(),
        ], $metadata);
    }

    public function register_many(array $hooks): void {
        foreach ($hooks as $hook => $metadata) {
            $this->register(is_string($hook) ? $hook : ($metadata['hook'] ?? ''), $metadata);
        }
    }

    public function is_registered(string $hook): bool {
        return isset($this->hooks[$hook]);
    }

    public function get_all(): array {
        return $this->hooks;
    }

    public function get_by_type(string $type): array {
        return array_filter($this->hooks, fn(array $meta): bool => ($meta['type'] ?? 'action') === $type);
    }

    public function do_action(string $hook, ...$args): void {
        if (isset($this->hooks[$hook])) {
            $this->hooks[$hook]['last_executed'] = time();
            $this->hooks[$hook]['execution_count'] = ($this->hooks[$hook]['execution_count'] ?? 0) + 1;
        }
        if (function_exists('do_action')) {
            do_action($hook, ...$args);
        }
    }

    public function apply_filters(string $hook, $value, ...$args) {
        if (isset($this->hooks[$hook])) {
            $this->hooks[$hook]['last_executed'] = time();
            $this->hooks[$hook]['execution_count'] = ($this->hooks[$hook]['execution_count'] ?? 0) + 1;
        }
        if (function_exists('apply_filters')) {
            return apply_filters($hook, $value, ...$args);
        }
        return $value;
    }

    public function get_callbacks(string $hook): array {
        global $wp_filter;
        if (!isset($wp_filter[$hook])) {
            return [];
        }
        $callbacks = [];
        foreach ($wp_filter[$hook]->callbacks as $priority => $handlers) {
            foreach ($handlers as $idx => $handler) {
                $callbacks[] = [
                    'priority' => $priority,
                    'callback' => $handler['function'] ?? null,
                    'accepted_args' => $handler['accepted_args'] ?? 1,
                ];
            }
        }
        return $callbacks;
    }

    public function discover_from_file(string $file): array {
        if (!file_exists($file)) {
            return [];
        }
        $contents = file_get_contents($file);
        if (false === $contents) {
            return [];
        }
        $found = [];
        $patterns = [
            '/add_action\s*\(\s*[\'"]([^\'"]+)[\'"]/',
            '/add_filter\s*\(\s*[\'"]([^\'"]+)[\'"]/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $contents, $matches)) {
                foreach ($matches[1] as $hook_name) {
                    $type = str_contains($pattern, 'add_action') ? 'action' : 'filter';
                    if (!isset($this->hooks[$hook_name])) {
                        $this->register($hook_name, ['type' => $type, 'discovered_from' => $file]);
                    }
                    $found[] = $hook_name;
                }
            }
        }
        return array_unique($found);
    }
}
