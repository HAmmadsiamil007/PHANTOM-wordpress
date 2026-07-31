<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class ComponentInstance {
    private static ?self $_instance_cache = null;
    private static array $instances = [];

    public string $id;
    public string $component_name;
    public string $token_group;
    public array $overrides;
    public array $content;
    public array $assets;
    public string $source;
    public array $state_overrides;
    public array $viewport_overrides;
    public bool $locked;
    public ?string $parent;

    public function __construct(
        string $id,
        string $component_name,
        string $token_group = '',
        array $overrides = [],
        array $content = [],
        array $assets = [],
        string $source = 'theme',
        array $state_overrides = [],
        bool $locked = false,
        ?string $parent = null,
        array $viewport_overrides = []
    ) {
        $this->id = $id;
        $this->component_name = $component_name;
        $this->token_group = $token_group ?: str_replace('-', '_', $component_name);
        $this->overrides = $overrides;
        $this->content = $content;
        $this->assets = $assets;
        $this->source = $source;
        $this->state_overrides = $state_overrides;
        $this->locked = $locked;
        $this->parent = $parent;
        $this->viewport_overrides = $viewport_overrides;
    }

    public function is_overridden(string $token): bool {
        return array_key_exists($token, $this->overrides);
    }

    public function get_value(string $token, string $state = 'normal'): mixed {
        if ('normal' !== $state && isset($this->state_overrides[$state][$token])) {
            return $this->state_overrides[$state][$token];
        }
        return $this->overrides[$token] ?? null;
    }

    public function set_value(string $token, mixed $value, string $state = 'normal'): void {
        if ('normal' !== $state) {
            $this->state_overrides[$state][$token] = $value;
        } else {
            $this->overrides[$token] = $value;
        }
    }

    public function has_state_override(string $token, string $state): bool {
        return isset($this->state_overrides[$state][$token]);
    }

    public function get_state_value(string $token, string $state): mixed {
        return $this->state_overrides[$state][$token] ?? null;
    }

    public function has_viewport_override(string $token, string $viewport): bool {
        return isset($this->viewport_overrides[$viewport][$token]);
    }

    public function get_viewport_value(string $token, string $viewport): mixed {
        return $this->viewport_overrides[$viewport][$token] ?? null;
    }

    public function set_viewport_value(string $token, mixed $value, string $viewport): void {
        $this->viewport_overrides[$viewport][$token] = $value;
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'component_name' => $this->component_name,
            'token_group' => $this->token_group,
            'overrides' => $this->overrides,
            'content' => $this->content,
            'assets' => $this->assets,
            'source' => $this->source,
            'state_overrides' => $this->state_overrides,
            'viewport_overrides' => $this->viewport_overrides,
            'locked' => $this->locked,
            'parent' => $this->parent,
        ];
    }

    public static function from_array(array $data): self {
        return new self(
            id: $data['id'] ?? '',
            component_name: $data['component_name'] ?? '',
            token_group: $data['token_group'] ?? '',
            overrides: $data['overrides'] ?? [],
            content: $data['content'] ?? [],
            assets: $data['assets'] ?? [],
            source: $data['source'] ?? 'theme',
            state_overrides: $data['state_overrides'] ?? [],
            locked: (bool)($data['locked'] ?? false),
            parent: $data['parent'] ?? null,
            viewport_overrides: $data['viewport_overrides'] ?? []
        );
    }

    public static function load_all(): array {
        if (!empty(self::$instances)) {
            return self::$instances;
        }
        $data = get_option('phantom_instances', []);
        foreach ($data as $id => $raw) {
            self::$instances[$id] = self::from_array(array_merge($raw, ['id' => $id]));
        }
        return self::$instances;
    }

    public static function get(string $id): ?self {
        $instances = self::load_all();
        return $instances[$id] ?? null;
    }

    public static function get_all(): array {
        return self::load_all();
    }

    public function save(): bool {
        self::load_all();
        self::$instances[$this->id] = $this;

        $data = [];
        foreach (self::$instances as $id => $instance) {
            $data[$id] = $instance->to_array();
            unset($data[$id]['id']);
        }
        return update_option('phantom_instances', $data);
    }

    public static function delete(string $id): bool {
        self::load_all();
        if (!isset(self::$instances[$id])) {
            return false;
        }
        unset(self::$instances[$id]);

        $data = [];
        foreach (self::$instances as $inst_id => $instance) {
            $data[$inst_id] = $instance->to_array();
            unset($data[$inst_id]['id']);
        }
        return update_option('phantom_instances', $data);
    }

    public static function flush_cache(): void {
        self::$instances = [];
    }
}
