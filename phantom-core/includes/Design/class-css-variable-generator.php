<?php
declare(strict_types=1);

namespace PhantomCore\Design;

defined('ABSPATH') || exit;

class CSSVariableGenerator {
    private TokenCompiler $compiler;
    private TokenRegistry $registry;
    private ?string $cachedCss = null;

    public function __construct() {
        $this->compiler = new TokenCompiler();
        $this->registry = TokenRegistry::get_instance();
    }

    public function generate(): string {
        if (null !== $this->cachedCss) {
            return $this->cachedCss;
        }

        $set = $this->compiler->compile();
        $css = $this->generateRoot($set);
        $css .= $this->generateComponentScoped($set);
        $css .= $this->generateResponsive($set);

        $this->cachedCss = $css;
        return $css;
    }

    public function generateRoot(CompiledTokenSet $set): string {
        $lines = [':root {'];
        foreach ($set->cssVars as $info) {
            $lines[] = "    {$info['var']}: {$info['value']};";
        }
        $lines[] = '}';
        return implode("\n", $lines) . "\n";
    }

    public function generateComponentScoped(CompiledTokenSet $set): string {
        $css = '';
        foreach ($set->components as $component => $tokens) {
            $className = '.phantom-' . str_replace('_', '-', $component);
            $css .= $className . " {\n";
            foreach ($tokens as $name => $value) {
                $varName = '--component-' . str_replace(['.', '_'], ['-', '-'], substr($name, strlen('component.')));
                $css .= "    {$varName}: {$value};\n";
            }
            $css .= "}\n";
        }
        return $css;
    }

    public function generateResponsive(CompiledTokenSet $set): string {
        $css = '';
        $bpMap = [
            'sm' => '576px', 'md' => '768px',
            'lg' => '992px', 'xl' => '1200px', 'xxl' => '1400px',
        ];
        foreach ($set->responsive as $bp => $val) {
            if (isset($bpMap[$bp])) {
                $bpVal = $set->tokens['breakpoint.' . $bp] ?? $bpMap[$bp];
                $css .= "@media (min-width: {$bpVal}) {\n";
                $css .= "    :root {\n";
                $css .= "        --breakpoint-{$bp}: {$bpVal};\n";
                $css .= "    }\n";
                $css .= "}\n";
            }
        }
        return $css;
    }

    public function getOutputHook(): callable {
        return function (string $css): string {
            return $css . $this->generate();
        };
    }

    public function invalidateCache(): void {
        $this->cachedCss = null;
        $this->compiler->invalidateCache();
    }
}
