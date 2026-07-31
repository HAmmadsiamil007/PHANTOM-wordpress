<?php
declare(strict_types=1);

namespace PhantomCore\Search;

use PhantomCore\Settings_Registry;
use PhantomCore\Components\Component_Registry;
use PhantomCore\Components\ComponentInstance;
use PhantomCore\Components\Property_Registry;
use PhantomCore\Animation\Animation_Registry;
use PhantomCore\Registry\Asset_Registry;

defined('ABSPATH') || exit;

class Search_Index {
    private static ?self $instance = null;
    private array $index = [];
    private bool $built = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function build(): void {
        if ($this->built) {
            return;
        }
        $this->index = [];
        $this->index_components();
        $this->index_instances();
        $this->index_properties();
        $this->index_tokens();
        $this->index_assets();
        $this->index_animations();
        $this->index_settings();
        $this->built = true;
    }

    public function rebuild(): void {
        $this->built = false;
        $this->index = [];
        $this->build();
    }

    public function search(string $query): array {
        $this->build();

        $q = strtolower(trim($query));
        if (strlen($q) < 2) {
            return [];
        }

        $results = [];

        foreach ($this->index as $entry) {
            $label_match = str_contains(strtolower($entry->label), $q);
            $desc_match = str_contains(strtolower($entry->description), $q);
            $id_match = str_contains(strtolower($entry->id), $q);

            if ($label_match || $desc_match || $id_match) {
                $relevance = $entry->relevance;
                if ($label_match) {
                    $relevance += 0.2;
                }
                if ($id_match) {
                    $relevance += 0.1;
                }
                $entry->relevance = min(1.0, $relevance);
                $results[] = $entry;
            }
        }

        usort($results, fn(SearchResult $a, SearchResult $b) => $b->relevance <=> $a->relevance);

        return $results;
    }

    public function index_components(): void {
        $registry = Component_Registry::get_instance();
        foreach ($registry->get_all() as $name => $comp) {
            $this->index[] = new SearchResult(
                type: 'component',
                id: $name,
                label: $comp->label,
                description: $comp->category . ($comp->description ? ' — ' . $comp->description : ''),
                relevance: 0.8
            );
        }
    }

    public function index_instances(): void {
        $instances = ComponentInstance::load_all();
        foreach ($instances as $id => $instance) {
            $this->index[] = new SearchResult(
                type: 'instance',
                id: $id,
                label: $instance->component_name . ' (' . substr($id, 0, 12) . '...)',
                description: 'Instance of ' . $instance->component_name . ($instance->locked ? ' [locked]' : ''),
                relevance: 0.7
            );

            foreach ($instance->overrides as $token => $val) {
                $this->index[] = new SearchResult(
                    type: 'instance',
                    id: $id . ':' . $token,
                    label: $instance->component_name . ' → ' . $token,
                    description: 'Override: ' . $token . ' = ' . (is_string($val) ? $val : '...'),
                    relevance: 0.5
                );
            }
        }
    }

    public function index_properties(): void {
        $registry = Property_Registry::get_instance();
        if (method_exists($registry, 'register_defaults')) {
            $registry->register_defaults();
        }
        foreach ($registry->get_all() as $key => $prop) {
            $label = is_object($prop) ? ($prop->label ?? $key) : ($prop['label'] ?? $key);
            $type = is_object($prop) ? ($prop->type ?? 'string') : ($prop['type'] ?? 'string');
            $this->index[] = new SearchResult(
                type: 'property',
                id: $key,
                label: $label,
                description: 'Property — ' . $type,
                relevance: 0.6
            );
        }
    }

    public function index_tokens(): void {
        $tokens = apply_filters('phantom_design_tokens', []);
        foreach ($tokens as $name => $token) {
            $label = $token['label'] ?? $name;
            $category = $token['category'] ?? 'general';
            $this->index[] = new SearchResult(
                type: 'token',
                id: $name,
                label: $label,
                description: 'Design token — ' . $category . ($token['value'] ? ' = ' . $token['value'] : ''),
                relevance: 0.6
            );
        }
    }

    public function index_assets(): void {
        $registry = Asset_Registry::get_instance();
        foreach ($registry->get_all() as $handle => $asset) {
            $type = $asset['type'] ?? 'js';
            $this->index[] = new SearchResult(
                type: 'asset',
                id: $handle,
                label: $handle,
                description: 'Asset — ' . $type . ($asset['src'] ? ' (' . $asset['src'] . ')' : ''),
                relevance: 0.4
            );
        }
    }

    public function index_animations(): void {
        $registry = Animation_Registry::get_instance();
        foreach ($registry->get_all() as $id => $animation) {
            $this->index[] = new SearchResult(
                type: 'animation',
                id: $id,
                label: $animation->label ?? $id,
                description: 'Animation — ' . ($animation->type ?? '') . ' / ' . ($animation->category ?? ''),
                relevance: 0.5
            );
        }
    }

    public function index_settings(): void {
        $registry = Settings_Registry::get_instance();
        $ref = new \ReflectionClass($registry);
        $entries_method = $ref->getMethod('register');
        $entries_method->setAccessible(true);
        $entries_method->invoke($registry);

        $entries_prop = $ref->getProperty('entries');
        $entries_prop->setAccessible(true);
        $entries = $entries_prop->getValue($registry);

        foreach ($entries as $key => $entry) {
            $label = $entry['label'] ?? $key;
            $section = $entry['section'] ?? '';
            $this->index[] = new SearchResult(
                type: 'setting',
                id: $key,
                label: $label,
                description: 'Setting — ' . $section . ($entry['type'] ? ' [' . $entry['type'] . ']' : ''),
                relevance: 0.5
            );
        }
    }

    public function suggest(string $prefix): array {
        $this->build();
        $p = strtolower(trim($prefix));
        if (strlen($p) < 1) {
            return [];
        }

        $suggestions = [];
        foreach ($this->index as $entry) {
            if (str_starts_with(strtolower($entry->label), $p)
                || str_starts_with(strtolower($entry->id), $p)
            ) {
                $suggestions[] = $entry;
            }
        }

        usort($suggestions, fn(SearchResult $a, SearchResult $b) => $b->relevance <=> $a->relevance);
        return array_slice($suggestions, 0, 10);
    }

    public function count(): int {
        $this->build();
        return count($this->index);
    }
}
