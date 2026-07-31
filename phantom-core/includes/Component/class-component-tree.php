<?php
declare(strict_types=1);

namespace PhantomCore\Component;

use PhantomCore\Components\Component_Registry;
use PhantomCore\Components\ComponentInstance;

defined('ABSPATH') || exit;

class Component_Tree {
    private static ?self $instance = null;
    private array $tree = [];
    private bool $built = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function build(string $url = ''): array {
        if ($this->built && empty($url)) {
            return $this->tree;
        }
        $this->tree = $this->build_from_registry();
        $this->built = true;
        return $this->tree;
    }

    public function get_parent(string $instance_id): ?string {
        $instances = ComponentInstance::load_all();
        $instance = $instances[$instance_id] ?? null;
        return $instance ? $instance->parent : null;
    }

    public function get_children(string $instance_id): array {
        $instances = ComponentInstance::load_all();
        $children = [];
        foreach ($instances as $id => $inst) {
            if ($inst->parent === $instance_id) {
                $children[$id] = $inst;
            }
        }
        return $children;
    }

    public function get_leaves(): array {
        $instances = ComponentInstance::load_all();
        $leaves = [];
        foreach ($instances as $id => $inst) {
            $has_children = false;
            foreach ($instances as $other_id => $other) {
                if ($other->parent === $id) {
                    $has_children = true;
                    break;
                }
            }
            if (!$has_children) {
                $leaves[$id] = $inst;
            }
        }
        return $leaves;
    }

    public function render_tree(array $tree, int $depth = 0): string {
        if (empty($tree)) {
            if (0 === $depth) {
                return '<div class="vc-tree-empty">No instances yet.</div>';
            }
            return '';
        }

        $html = str_repeat('  ', $depth) . '<ul class="vc-tree' . (0 === $depth ? '' : ' vc-tree-children') . '">' . "\n";
        foreach ($tree as $node) {
            $html .= $this->render_tree_node($node, $depth);
        }
        $html .= str_repeat('  ', $depth) . '</ul>' . "\n";
        return $html;
    }

    private function render_tree_node(array $node, int $depth): string {
        $instance_id = esc_attr($node['id'] ?? '');
        $component = esc_attr($node['component'] ?? '');
        $label = esc_html($node['label'] ?? $node['id'] ?? '');
        $locked = !empty($node['locked']);
        $has_children = !empty($node['children']);
        $override_count = (int)($node['override_count'] ?? 0);
        $has_state = !empty($node['has_state_overrides']);
        $has_viewport = !empty($node['has_viewport_overrides']);

        $toggle = $has_children
            ? '<span class="vc-tree-toggle dashicons dashicons-arrow-down"></span>'
            : '<span class="vc-tree-toggle vc-tree-toggle--empty"></span>';

        $lock_icon = $locked ? '<span class="vc-tree-locked dashicons dashicons-lock" title="Locked"></span>' : '';

        $badges = '';
        if ($has_state) {
            $badges .= '<span class="vc-tree-badge vc-badge-state" title="Has state overrides">S</span>';
        }
        if ($has_viewport) {
            $badges .= '<span class="vc-tree-badge vc-badge-viewport" title="Has viewport overrides">V</span>';
        }
        if ($override_count > 0) {
            $badges .= '<span class="vc-tree-badge vc-badge-modified" title="Modified">' . $override_count . '</span>';
        }

        $indent = str_repeat('  ', $depth + 1);
        $html = $indent . '<li class="vc-tree-node' . ($locked ? ' is-locked' : '') . '" data-instance-id="' . $instance_id . '" data-component="' . $component . '">' . "\n";
        $html .= $indent . '  <div class="vc-tree-node-label">' . $toggle . $lock_icon . ' <span>' . $label . '</span> ' . $badges . '</div>' . "\n";

        if ($has_children) {
            $html .= $indent . '  <ul class="vc-tree-children">' . "\n";
            foreach ($node['children'] as $child) {
                $html .= $this->render_tree_node($child, $depth + 2);
            }
            $html .= $indent . '  </ul>' . "\n";
        }

        $html .= $indent . '</li>' . "\n";
        return $html;
    }

    public function build_from_registry(): array {
        $instances = ComponentInstance::load_all();
        $registry = Component_Registry::get_instance();
        $tree = [];
        $children_map = [];

        foreach ($instances as $id => $instance) {
            $parent = $instance->parent ?? '';
            $override_count = count($instance->overrides);

            $node = [
                'id' => $id,
                'component' => $instance->component_name,
                'label' => $instance->component_name . ' (' . substr($id, 0, 12) . '...)',
                'locked' => $instance->locked,
                'override_count' => $override_count,
                'has_state_overrides' => !empty($instance->state_overrides),
                'has_viewport_overrides' => !empty($instance->viewport_overrides),
                'children' => [],
            ];

            $comp = $registry->get($instance->component_name);
            if ($comp) {
                $node['label'] = $comp->label . ' (' . substr($id, 0, 12) . '...)';
            }

            if ($parent && isset($instances[$parent])) {
                $children_map[$parent][] = $node;
            } else {
                $tree[] = $node;
            }
        }

        $add_children = function (array &$nodes) use (&$add_children, $children_map): void {
            foreach ($nodes as &$node) {
                $cid = $node['id'];
                if (isset($children_map[$cid])) {
                    $node['children'] = $children_map[$cid];
                    $add_children($node['children']);
                }
            }
        };
        $add_children($tree);

        return $tree;
    }
}
