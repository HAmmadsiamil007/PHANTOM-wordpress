<?php
declare(strict_types=1);

namespace PhantomCore\Favorites;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Component_Registry;

defined('ABSPATH') || exit;

class Favorites_Manager {
    private static ?self $instance = null;
    private const META_KEY = 'phantom_favorites';

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_all(): array {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return [];
        }
        $favorites = get_user_meta($user_id, self::META_KEY, true);
        return is_array($favorites) ? $favorites : [];
    }

    public function add(string $type, string $id): bool {
        $favorites = $this->get_all();
        $key = $this->make_key($type, $id);
        if (!in_array($key, $favorites, true)) {
            $favorites[] = $key;
            return (bool) update_user_meta(get_current_user_id(), self::META_KEY, $favorites);
        }
        return true;
    }

    public function remove(string $type, string $id): bool {
        $favorites = $this->get_all();
        $key = $this->make_key($type, $id);
        $favorites = array_values(array_filter($favorites, fn($k) => $k !== $key));
        return (bool) update_user_meta(get_current_user_id(), self::META_KEY, $favorites);
    }

    public function toggle(string $type, string $id): array {
        $key = $this->make_key($type, $id);
        $favorites = $this->get_all();
        $is_fav = in_array($key, $favorites, true);
        if ($is_fav) {
            $this->remove($type, $id);
        } else {
            $this->add($type, $id);
        }
        return [
            'active' => !$is_fav,
            'type' => $type,
            'id' => $id,
        ];
    }

    public function is_favorite(string $type, string $id): bool {
        return in_array($this->make_key($type, $id), $this->get_all(), true);
    }

    public function get_with_data(): array {
        $favorites = $this->get_all();
        $items = [];
        $registry = Component_Registry::get_instance();

        foreach ($favorites as $key) {
            $parts = explode(':', $key, 2);
            $type = $parts[0] ?? '';
            $id = $parts[1] ?? '';

            if ('component' === $type) {
                $comp = $registry->get($id);
                if ($comp) {
                    $items[] = [
                        'key' => $key,
                        'type' => $type,
                        'id' => $id,
                        'label' => $comp->label,
                        'category' => $comp->category,
                    ];
                }
            } elseif ('instance' === $type) {
                $inst = ComponentInstance::get($id);
                if ($inst) {
                    $items[] = [
                        'key' => $key,
                        'type' => $type,
                        'id' => $id,
                        'label' => $inst->component_name . ' (' . substr($id, 0, 12) . '...)',
                        'category' => 'instance',
                    ];
                }
            }
        }

        return $items;
    }

    private function make_key(string $type, string $id): string {
        return $type . ':' . $id;
    }
}
