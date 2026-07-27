<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class TokenCompiler {
    private TokenRegistry $registry;
    private TokenResolver $resolver;
    private TokenValidator $validator;
    private ?CompiledTokenSet $cached = null;

    public function __construct() {
        $this->registry = TokenRegistry::get_instance();
        $this->resolver = new TokenResolver();
        $this->validator = new TokenValidator();
    }

    public function compile(): CompiledTokenSet {
        if (null !== $this->cached) {
            return $this->cached;
        }

        $set = new CompiledTokenSet();
        $set->tokens = $this->resolver->resolveAll();
        $set->cssVars = $this->buildCssVars($set->tokens);
        $set->components = $this->extractComponents($set->tokens);
        $set->responsive = $this->extractResponsive($set->tokens);

        $this->cached = $set;
        return $set;
    }

    public function compileCategory(string $category): array {
        return $this->resolver->resolveCategory($category);
    }

    public function invalidateCache(): void {
        $this->cached = null;
        $this->resolver->invalidateCache();
    }

    private function buildCssVars(array $tokens): array {
        $vars = [];
        foreach ($tokens as $name => $value) {
            $vars[$name] = [
                'var' => $this->registry->get_css_var($name),
                'value' => $value,
            ];
        }
        return $vars;
    }

    private function extractComponents(array $tokens): array {
        $components = [];
        foreach ($tokens as $name => $value) {
            if (str_starts_with($name, 'component.')) {
                $parts = explode('.', $name);
                $component = $parts[1] ?? 'unknown';
                if (!isset($components[$component])) {
                    $components[$component] = [];
                }
                $components[$component][$name] = $value;
            }
        }
        return $components;
    }

    private function extractResponsive(array $tokens): array {
        $responsive = [];
        foreach ($tokens as $name => $value) {
            if (str_starts_with($name, 'breakpoint.')) {
                $bp = substr($name, strlen('breakpoint.'));
                $responsive[$bp] = $value;
            }
        }
        return $responsive;
    }
}
