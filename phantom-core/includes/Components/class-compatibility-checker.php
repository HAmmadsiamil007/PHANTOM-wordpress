<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class Compatibility_Checker {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function check_all(): CompatibilityReport {
        $errors = [];
        $warnings = [];
        $missing_components = [];
        $missing_tokens = [];
        $missing_renderers = [];
        $missing_adapters = [];
        $missing_templates = [];
        $missing_assets = [];
        $missing_css = [];
        $missing_js = [];
        $orphan_instances = [];

        // ─── 1. Component Registry Health ───
        $comp_registry = Component_Registry::get_instance();
        $components = $comp_registry->get_all();
        $validator = Component_Validator::get_instance();

        if (empty($components)) {
            $errors[] = 'Component Registry is empty — no components registered';
        }

        foreach ($components as $name => $component) {
            $result = $validator->validate($name);
            if (!$result->pass) {
                $missing_components[] = $name;
                foreach ($result->errors as $err) {
                    $errors[] = "[{$name}] {$err}";
                }
            }
            foreach ($result->warnings as $warn) {
                $warnings[] = "[{$name}] {$warn}";
            }
            if (!empty($component->class_name) && !class_exists($component->class_name)) {
                $missing_renderers[] = $component->class_name;
            }
            if (null !== $component->adapter && !class_exists($component->adapter)) {
                $missing_adapters[] = $component->adapter;
            }
        }

        // ─── 2. Token Registry Health ───
        if (class_exists('\\PhantomCore\\Design\\Token_Registry')) {
            $token_registry = \PhantomCore\Design\Token_Registry::get_instance();
            if (method_exists($token_registry, 'count') && $token_registry->count() === 0) {
                $warnings[] = 'Token Registry has 0 tokens registered';
            }
        } else {
            $warnings[] = 'Token_Registry class not available';
        }

        // ─── 3. Property Registry Health ───
        $prop_registry = Property_Registry::get_instance();
        if ($prop_registry->count() === 0) {
            $warnings[] = 'Property_Registry has 0 properties registered';
        }

        // ─── 4. Media Asset Registry Health ───
        $media_registry = Media_Asset_Registry::get_instance();
        if ($media_registry->count() === 0) {
            $warnings[] = 'Media_Asset_Registry has 0 assets registered';
        }

        // ─── 5. Instance Health ───
        $instances = ComponentInstance::get_all();
        foreach ($instances as $id => $instance) {
            if (!$comp_registry->has($instance->component_name)) {
                $orphan_instances[] = $id;
                $warnings[] = "Orphan instance '{$id}' — component '{$instance->component_name}' not registered";
            }
        }

        // ─── 6. Settings Registry Health ───
        $settings = \PhantomCore\Settings_Registry::get_instance();
        $entries = $settings->get_entries();
        if (empty($entries)) {
            $warnings[] = 'Settings_Registry has 0 entries';
        }

        // ─── 7. Feature Registry Health ───
        if (class_exists('\\PhantomCore\\Feature\\Feature_Registry')) {
            $feature_registry = \PhantomCore\Feature\Feature_Registry::get_instance();
            if (method_exists($feature_registry, 'count') && $feature_registry->count() === 0) {
                $warnings[] = 'Feature_Registry has 0 features registered';
            }
        }

        // Calculate score
        $score = 100;
        $score -= count($errors) * 25;
        $score -= count($warnings) * 5;
        $score = max(0, $score);

        return new CompatibilityReport(
            pass: empty($errors),
            score: $score,
            errors: $errors,
            warnings: $warnings,
            missing_components: $missing_components,
            missing_tokens: $missing_tokens,
            missing_renderers: $missing_renderers,
            missing_adapters: $missing_adapters,
            missing_templates: $missing_templates,
            missing_assets: $missing_assets,
            missing_css: $missing_css,
            missing_js: $missing_js,
            orphan_instances: $orphan_instances
        );
    }

    public function check_component(string $component_name): ValidationResult {
        $validator = Component_Validator::get_instance();
        return $validator->validate($component_name);
    }
}
