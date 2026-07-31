<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class Component_Validator {
    private static ?self $instance = null;
    private array $validation_cache = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validate(string $component_name): ValidationResult {
        if (isset($this->validation_cache[$component_name])) {
            return $this->validation_cache[$component_name];
        }

        $registry = Component_Registry::get_instance();
        $component = $registry->get($component_name);

        if (null === $component) {
            $result = new ValidationResult(
                $component_name,
                pass: false,
                score: 0,
                errors: ["Component '{$component_name}' is not registered"]
            );
            $this->validation_cache[$component_name] = $result;
            return $result;
        }

        $errors = [];
        $warnings = [];

        // Check renderer class exists
        if (!empty($component->class_name) && !class_exists($component->class_name)) {
            $errors[] = "Renderer class '{$component->class_name}' not found for '{$component_name}'";
        }

        // Check adapter class exists (if specified)
        if (null !== $component->adapter) {
            $adapter_class = $component->adapter;
            if (!class_exists($adapter_class)) {
                $warnings[] = "Adapter class '{$adapter_class}' not found for '{$component_name}'";
            }
        }

        // Check content type is valid
        $valid_content_types = ['static', 'dynamic', 'repeatable', 'generated'];
        if (!in_array($component->content_type, $valid_content_types, true)) {
            $errors[] = "Invalid content_type '{$component->content_type}' for '{$component_name}'";
        }

        // Check required features
        if (!empty($component->required_features)) {
            if (!class_exists('\\PhantomCore\\Feature\\Feature_Registry')) {
                $warnings[] = "Feature_Registry not available, cannot verify required features";
            } else {
                $feature_registry = \PhantomCore\Feature\Feature_Registry::get_instance();
                foreach ($component->required_features as $feature_id) {
                    if (!$feature_registry->enabled($feature_id)) {
                        $warnings[] = "Required feature '{$feature_id}' is not enabled for '{$component_name}'";
                    }
                }
            }
        }

        // Check token references
        if (class_exists('\\PhantomCore\\Design\\Token_Registry')) {
            $token_registry = \PhantomCore\Design\Token_Registry::get_instance();
            foreach ($component->tokens as $token) {
                if (!$token_registry->has($token)) {
                    $warnings[] = "Token '{$token}' not found in Token_Registry for '{$component_name}'";
                }
            }
        }

        // Check property references
        $property_registry = Property_Registry::get_instance();
        foreach ($component->properties as $prop_name) {
            if (!$property_registry->has($prop_name)) {
                $warnings[] = "Property '{$prop_name}' not found in Property_Registry for '{$component_name}'";
            }
        }

        // Check asset key references
        $media_registry = Media_Asset_Registry::get_instance();
        foreach ($component->default_assets as $asset_key) {
            if (!$media_registry->has($asset_key)) {
                $warnings[] = "Asset key '{$asset_key}' not found in Media_Asset_Registry for '{$component_name}'";
            }
        }

        // Check style states are valid
        $valid_states = ['normal', 'hover', 'focus', 'active', 'disabled'];
        foreach ($component->style_states as $state) {
            if (!in_array($state, $valid_states, true)) {
                $warnings[] = "Invalid style state '{$state}' for '{$component_name}'";
            }
        }

        // Check template files exist
        $metadata = Component_Metadata::get_instance();
        $templates = $metadata->get_templates($component_name);
        if (empty($templates)) {
            $warnings[] = "No template files registered for '{$component_name}'";
        }

        // Required settings keys exist
        $settings_registry = \PhantomCore\Settings_Registry::get_instance();
        foreach ($component->component_settings as $setting_key) {
            if (!$settings_registry->has($setting_key)) {
                $warnings[] = "Setting key '{$setting_key}' not found in Settings_Registry for '{$component_name}'";
            }
        }

        $score = $this->calculate_score($errors, $warnings);
        $result = new ValidationResult(
            $component_name,
            pass: empty($errors),
            score: $score,
            errors: $errors,
            warnings: $warnings
        );

        $this->validation_cache[$component_name] = $result;
        return $result;
    }

    public function validate_all(): array {
        $registry = Component_Registry::get_instance();
        $components = $registry->get_all();
        $results = [];

        foreach ($components as $name => $component) {
            $results[$name] = $this->validate($name);
        }

        return $results;
    }

    public function flush_cache(): void {
        $this->validation_cache = [];
    }

    private function calculate_score(array $errors, array $warnings): int {
        $score = 100;
        $score -= count($errors) * 25;
        $score -= count($warnings) * 5;
        return max(0, $score);
    }
}
