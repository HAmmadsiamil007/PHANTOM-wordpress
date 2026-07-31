<?php
declare(strict_types=1);

namespace PhantomCore\Components;

defined('ABSPATH') || exit;

class CompatibilityReport {
    public bool $pass;
    public int $score;
    public array $errors;
    public array $warnings;
    public array $missing_components;
    public array $missing_tokens;
    public array $missing_renderers;
    public array $missing_adapters;
    public array $missing_templates;
    public array $missing_assets;
    public array $missing_css;
    public array $missing_js;
    public array $orphan_instances;

    public function __construct(
        bool $pass = true,
        int $score = 100,
        array $errors = [],
        array $warnings = [],
        array $missing_components = [],
        array $missing_tokens = [],
        array $missing_renderers = [],
        array $missing_adapters = [],
        array $missing_templates = [],
        array $missing_assets = [],
        array $missing_css = [],
        array $missing_js = [],
        array $orphan_instances = []
    ) {
        $this->pass = $pass;
        $this->score = $score;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->missing_components = $missing_components;
        $this->missing_tokens = $missing_tokens;
        $this->missing_renderers = $missing_renderers;
        $this->missing_adapters = $missing_adapters;
        $this->missing_templates = $missing_templates;
        $this->missing_assets = $missing_assets;
        $this->missing_css = $missing_css;
        $this->missing_js = $missing_js;
        $this->orphan_instances = $orphan_instances;
    }

    public function summary(): string {
        if ($this->pass) {
            return "All checks passed (Score: {$this->score}/100)";
        }
        $parts = [];
        if (!empty($this->errors)) {
            $parts[] = count($this->errors) . ' error(s)';
        }
        if (!empty($this->warnings)) {
            $parts[] = count($this->warnings) . ' warning(s)';
        }
        return implode(', ', $parts) . " (Score: {$this->score}/100)";
    }

    public function to_array(): array {
        return [
            'pass' => $this->pass,
            'score' => $this->score,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'missing_components' => $this->missing_components,
            'missing_tokens' => $this->missing_tokens,
            'missing_renderers' => $this->missing_renderers,
            'missing_adapters' => $this->missing_adapters,
            'missing_templates' => $this->missing_templates,
            'missing_assets' => $this->missing_assets,
            'missing_css' => $this->missing_css,
            'missing_js' => $this->missing_js,
            'orphan_instances' => $this->orphan_instances,
        ];
    }
}
