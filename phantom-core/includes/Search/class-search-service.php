<?php
declare(strict_types=1);

namespace PhantomCore\Search;

use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Components\Property_Registry;

defined('ABSPATH') || exit;

class Search_Service {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function search(string $query): array {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $q = strtolower(trim($query));
        $results = [];

        $component_results = $this->search_components($q);
        if (!empty($component_results)) {
            $results[] = [
                'category' => 'components',
                'label' => 'Components',
                'items' => $component_results,
            ];
        }

        $instance_results = $this->search_instances($q);
        if (!empty($instance_results)) {
            $results[] = [
                'category' => 'instances',
                'label' => 'Instances',
                'items' => $instance_results,
            ];
        }

        $property_results = $this->search_properties($q);
        if (!empty($property_results)) {
            $results[] = [
                'category' => 'properties',
                'label' => 'Properties',
                'items' => $property_results,
            ];
        }

        return $results;
    }

    private function search_components(string $q): array {
        $registry = Component_Registry::get_instance();
        $components = $registry->get_all();
        $results = [];

        foreach ($components as $name => $comp) {
            if (str_contains(strtolower($name), $q)
                || str_contains(strtolower($comp->label), $q)
                || str_contains(strtolower($comp->category), $q)
                || str_contains(strtolower($comp->description ?? ''), $q)
            ) {
                $results[] = [
                    'type' => 'component',
                    'id' => $name,
                    'label' => $comp->label,
                    'description' => $comp->category . (' ' . $comp->description ?? ''),
                ];
            }
        }

        return $results;
    }

    private function search_instances(string $q): array {
        $instances = ComponentInstance::load_all();
        $results = [];

        foreach ($instances as $id => $instance) {
            if (str_contains(strtolower($id), $q)
                || str_contains(strtolower($instance->component_name), $q)
            ) {
                $results[] = [
                    'type' => 'instance',
                    'id' => $id,
                    'label' => $instance->component_name . ' (' . substr($id, 0, 12) . '...)',
                    'description' => 'Instance of ' . $instance->component_name . ($instance->locked ? ' [locked]' : ''),
                ];
            } else {
                foreach ($instance->overrides as $token => $val) {
                    if (str_contains(strtolower($token), $q)) {
                        $results[] = [
                            'type' => 'instance',
                            'id' => $id,
                            'label' => $instance->component_name . ' → ' . $token,
                            'description' => 'Override: ' . $token . ' = ' . (is_string($val) ? $val : '...'),
                        ];
                        break;
                    }
                }
            }
        }

        return $results;
    }

    private function search_properties(string $q): array {
        $registry = Property_Registry::get_instance();
        $properties = $registry->get_all();
        $results = [];

        foreach ($properties as $key => $prop) {
            if (str_contains(strtolower($key), $q)
                || str_contains(strtolower($prop['label'] ?? ''), $q)
            ) {
                $results[] = [
                    'type' => 'property',
                    'id' => $key,
                    'label' => $prop['label'] ?? $key,
                    'description' => 'Property: ' . ($prop['type'] ?? 'string'),
                ];
            }
        }

        return $results;
    }
}
