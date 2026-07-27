<?php
declare(strict_types=1);

namespace PhantomCore\Design;

use PhantomCore\Design\Providers\CoreProvider;
use PhantomCore\Design\Providers\DemoProvider;
use PhantomCore\Design\Providers\UserProvider;

defined('ABSPATH') || exit;

class DesignSystemManager {
    private static ?self $instance = null;
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private TokenCompiler $compiler;
    private CSSVariableGenerator $cssGenerator;
    private PresetRegistry $presetRegistry;
    private PresetManager $presetManager;
    private ThemeDNAEngine $dnaEngine;
    private bool $initialized = false;

    private function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
        $this->validator = new TokenValidator();
        $this->compiler = new TokenCompiler();
        $this->cssGenerator = new CSSVariableGenerator();
        $this->presetRegistry = PresetRegistry::get_instance();
        $this->presetManager = PresetManager::get_instance();
        $this->dnaEngine = ThemeDNAEngine::get_instance();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        if ($this->initialized) return;
        $this->registry->load();
        $this->presetRegistry->register_provider(new CoreProvider());
        $this->presetRegistry->register_provider(new DemoProvider());
        $this->presetRegistry->register_provider(new UserProvider());
        add_filter('phantom_dynamic_css', $this->cssGenerator->getOutputHook(), 2);
        $this->initialized = true;
    }

    public function token(string $name): mixed {
        return $this->resolver->resolve($name);
    }

    public function tokens(?array $categories = null): array {
        if (null === $categories) {
            return $this->resolver->resolveAll();
        }
        $result = [];
        foreach ($categories as $cat) {
            $result[$cat] = $this->resolver->resolveCategory($cat);
        }
        return $result;
    }

    public function cssVar(string $name): string {
        return $this->registry->get_css_var($name);
    }

    public function allCssVars(): array {
        $set = $this->compiler->compile();
        $vars = [];
        foreach ($set->cssVars as $name => $info) {
            $vars[$info['var']] = $info['value'];
        }
        return $vars;
    }

    public function generateCSS(): string {
        return $this->cssGenerator->generate();
    }

    public function validate(): array {
        return $this->validator->validateAll();
    }

    public function compile(): CompiledTokenSet {
        return $this->compiler->compile();
    }

    public function applyPreset(string $id): bool {
        return $this->presetManager->apply($id);
    }

    public function availablePresets(): array {
        return $this->presetRegistry->get_all();
    }

    public function currentPreset(): ?array {
        $id = $this->presetManager->current();
        if (null === $id) return null;
        $preset = $this->presetRegistry->get($id);
        return null !== $preset ? $preset->to_array() : null;
    }

    public function currentThemeDNA(): array {
        return $this->dnaEngine->getCurrent();
    }

    public function exportPreset(string $id): string {
        $exporter = new DesignExporter();
        return $exporter->exportPreset($id);
    }

    public function exportCurrent(): string {
        $exporter = new DesignExporter();
        return $exporter->exportCurrent();
    }

    public function importPreset(string $json): array {
        $importer = new DesignImporter();
        return $importer->import($json);
    }
}

class_alias(DesignSystemManager::class, 'DesignSystem');
