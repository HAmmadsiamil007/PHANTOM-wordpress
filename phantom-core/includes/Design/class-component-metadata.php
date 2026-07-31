<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

/**
 * Component_Metadata — the B.0 metadata engine for the Visual Tool Editor.
 *
 * Every editable component self-describes through a parts map: each part
 * groups generic properties (from Property_Registry) under a label and maps
 * them to component-specific storage keys. Tools are derived from the
 * properties — the editor never branches on component ids.
 *
 * Parts data lives in `includes/Design/data/component-parts.php`.
 *
 * @package PhantomCore\Design
 */
class Component_Metadata {

    private static ?self $instance = null;

    private ?Property_Registry $properties = null;

    /** @var array<string, array>|null */
    private ?array $parts_data = null;

    private bool $loaded = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->properties = Property_Registry::get_instance();
    }

    /**
     * Whether the component has metadata parts.
     */
    public function has(string $component_id): bool {
        $this->load();
        return isset($this->parts_data[$component_id]);
    }

    /**
     * Resolved parts for a component.
     *
     * Each part: ['label' => string, 'properties' => array of
     *   ['property' => string, 'key' => string, 'label' => ?string, 'def' => ?array]]
     *
     * @return array<string, array>
     */
    public function get_parts(string $component_id): array {
        $this->load();
        if (!isset($this->parts_data[$component_id])) {
            return [];
        }

        $out = [];
        foreach ($this->parts_data[$component_id]['parts'] as $part_id => $part) {
            $properties = [];
            foreach ($part['properties'] as $entry) {
                $property = (string) ($entry['property'] ?? '');
                $def      = $this->properties->get($property);
                if (null === $def) {
                    $def = array(
                        'key'   => $property,
                        'type'  => 'text',
                        'label' => $property,
                    );
                }
                $properties[] = array(
                    'property' => $property,
                    'key'      => (string) ($entry['key'] ?? $property),
                    'label'    => isset($entry['label']) ? (string) $entry['label'] : null,
                    'target'   => isset($entry['target']) ? (string) $entry['target'] : null,
                    'def'      => $def,
                );
            }
            $out[$part_id] = array(
                'label'      => (string) ($part['label'] ?? ucfirst($part_id)),
                'properties' => $properties,
            );
        }

        return $out;
    }

    /**
     * Parts filtered to a single tool (empty tool returns everything).
     *
     * @return array<string, array>
     */
    public function get_parts_for_tool(string $component_id, string $tool): array {
        if ('' === $tool || !$this->properties->tool_exists($tool)) {
            return $this->get_parts($component_id);
        }
        $parts = $this->get_parts($component_id);
        foreach ($parts as $part_id => $part) {
            $kept = array_values(array_filter($part['properties'], static function (array $entry) use ($tool): bool {
                return (($entry['def']['tool'] ?? '') === $tool);
            }));
            if (empty($kept)) {
                unset($parts[$part_id]);
                continue;
            }
            $parts[$part_id]['properties'] = $kept;
        }
        return $parts;
    }

    /**
     * Tools supported by a component, derived from its part properties.
     * Ordered by the registry tool order; includes label/icon/implemented
     * flags for the client tool palette. Empty when no metadata exists.
     *
     * @return array<int, array{tool: string, label: string, icon: string, implemented: bool}>
     */
    public function get_tools(string $component_id): array {
        if (!$this->has($component_id)) {
            return [];
        }
        $parts = $this->get_parts($component_id);
        $tools = [];
        foreach (Property_Registry::TOOLS as $tool_key => $tool) {
            $found = false;
            foreach ($parts as $part) {
                foreach ($part['properties'] as $entry) {
                    if (($entry['def']['tool'] ?? '') === $tool_key) {
                        $found = true;
                        break 2;
                    }
                }
            }
            if ($found) {
                $tools[] = array(
                    'tool'        => $tool_key,
                    'label'       => $tool['label'],
                    'icon'        => $tool['icon'],
                    'implemented' => $tool['implemented'],
                );
            }
        }
        return $tools;
    }

    /**
     * Tools exposed as a keyed map (component id => tools list).
     *
     * @return array<string, array>
     */
    public function get_all_tools(): array {
        $this->load();
        $out = [];
        foreach (array_keys($this->parts_data ?? []) as $id) {
            $tools = $this->get_tools($id);
            if ($tools) {
                $out[$id] = $tools;
            }
        }
        return $out;
    }

    /**
     * Unfiltered raw parts data (for introspection/tests).
     *
     * @return array<string, array>
     */
    public function get_raw(): array {
        $this->load();
        return $this->parts_data ?? [];
    }

    private function load(): void {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        $file = dirname(__DIR__) . '/Design/data/component-parts.php';
        if (is_readable($file)) {
            $data = include $file;
            if (is_array($data)) {
                $this->parts_data = $data;
            }
        }
    }
}
