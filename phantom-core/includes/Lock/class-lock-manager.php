<?php
declare(strict_types=1);

namespace PhantomCore\Lock;

defined('ABSPATH') || exit;

class Lock_Manager {
    private static ?self $instance = null;

    public const LOCK_META_KEY = 'phantom_locked_instances';

    private ?array $locked_cache = null;
    private ?array $locked_types_cache = null;
    private ?array $locked_roles_cache = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function is_locked(string $instance_id): bool {
        $locked = $this->get_locked();
        return isset($locked[$instance_id]);
    }

    public function is_locked_for_user(string $instance_id, int $user_id): bool {
        $locked = $this->get_locked();
        if (!isset($locked[$instance_id])) {
            return false;
        }
        $lock = $locked[$instance_id];
        return (int)($lock['user_id'] ?? 0) === $user_id;
    }

    public function lock(string $instance_id): bool {
        $locked = $this->get_locked();
        if (isset($locked[$instance_id])) {
            return false;
        }

        $user_id = get_current_user_id();
        $locked[$instance_id] = [
            'instance_id' => $instance_id,
            'user_id'     => $user_id,
            'user_name'   => $this->get_user_display_name($user_id),
            'locked_at'   => current_time('mysql'),
        ];

        $this->locked_cache = $locked;
        return update_option(self::LOCK_META_KEY, $locked);
    }

    public function unlock(string $instance_id): bool {
        $locked = $this->get_locked();
        if (!isset($locked[$instance_id])) {
            return false;
        }

        unset($locked[$instance_id]);
        $this->locked_cache = $locked;
        return update_option(self::LOCK_META_KEY, $locked);
    }

    public function get_locked(): array {
        if (null === $this->locked_cache) {
            $data = get_option(self::LOCK_META_KEY, []);
            $this->locked_cache = is_array($data) ? $data : [];
        }
        return $this->locked_cache;
    }

    public function lock_component_type(string $component_name): bool {
        $locked = $this->get_locked_component_types();
        if (in_array($component_name, $locked, true)) {
            return false;
        }
        $locked[] = $component_name;
        $this->locked_types_cache = $locked;
        return update_option('phantom_locked_component_types', $locked);
    }

    public function is_component_type_locked(string $component_name): bool {
        $locked = $this->get_locked_component_types();
        return in_array($component_name, $locked, true);
    }

    public function get_locked_component_types(): array {
        if (null === $this->locked_types_cache) {
            $data = get_option('phantom_locked_component_types', []);
            $this->locked_types_cache = is_array($data) ? $data : [];
        }
        return $this->locked_types_cache;
    }

    public function get_locked_roles(): array {
        if (null === $this->locked_roles_cache) {
            $data = get_option('phantom_locked_roles', []);
            $this->locked_roles_cache = is_array($data) ? $data : [];
        }
        return $this->locked_roles_cache;
    }

    public function set_locked_roles(array $roles): bool {
        $this->locked_roles_cache = $roles;
        return update_option('phantom_locked_roles', $roles);
    }

    public function user_can_edit_locked(int $user_id): bool {
        $roles = $this->get_locked_roles();
        if (empty($roles)) {
            return true;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        foreach ((array)$user->roles as $role) {
            if (in_array($role, $roles, true)) {
                return true;
            }
        }

        return false;
    }

    public function check_frontend_lock(array $attributes): bool {
        $locked = isset($attributes['data-locked']) && 'true' === $attributes['data-locked'];
        if (!$locked) {
            return false;
        }

        $instance_id = $attributes['data-instance'] ?? '';
        if (empty($instance_id)) {
            return false;
        }

        return $this->is_locked($instance_id);
    }

    private function get_user_display_name(int $user_id): string {
        $user = get_userdata($user_id);
        if (!$user) {
            return 'Unknown';
        }
        return $user->display_name ?: $user->user_login;
    }
}
