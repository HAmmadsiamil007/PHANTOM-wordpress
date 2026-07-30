<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenRegistry {
    private static ?self $instance = null;
    private array $tokens = [];
    private bool $loaded = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load(): void {
        if ($this->loaded) return;
        $this->tokens = require PHANTOM_CORE_PATH . 'includes/Design/data/token-definitions.php';
        $this->loaded = true;
    }

    public function get_all(): array {
        $this->load();
        return $this->tokens;
    }

    public function get(string $name): ?array {
        $this->load();
        return $this->tokens[$name] ?? null;
    }

    public function get_by_category(string $category): array {
        $this->load();
        return array_filter($this->tokens, fn($t) => ($t['category'] ?? '') === $category);
    }

    public function has(string $name): bool {
        $this->load();
        return isset($this->tokens[$name]);
    }

    public function get_css_var(string $name): string {
        return '--' . str_replace(['.', '_'], ['-', '-'], $name);
    }

    public function get_option_key(string $name): string {
        $this->load();
        return $this->tokens[$name]['option_key'] ?? 'phantom_' . str_replace('.', '_', $name);
    }

    public function get_default(string $name): mixed {
        $this->load();
        return $this->tokens[$name]['default'] ?? null;
    }

    public function get_type(string $name): ?string {
        $this->load();
        return $this->tokens[$name]['type'] ?? null;
    }

    public function count(): int {
        $this->load();
        return count($this->tokens);
    }

    public function get_usage(string $token_name): array {
        $this->load();
        return $this->tokens[$token_name]['usage'] ?? [];
    }

    public function get_tokens_used_by_component(string $component_id): array {
        $this->load();
        $result = [];
        foreach ($this->tokens as $name => $def) {
            $usage = $def['usage'] ?? [];
            if (in_array($component_id, $usage, true)) {
                $result[] = $name;
            }
        }
        return $result;
    }

    public function count_by_category(): array {
        $this->load();
        $cats = [];
        foreach ($this->tokens as $t) {
            $cat = $t['category'] ?? 'other';
            if (!isset($cats[$cat])) {
                $cats[$cat] = 0;
            }
            $cats[$cat]++;
        }
        return $cats;
    }
}
