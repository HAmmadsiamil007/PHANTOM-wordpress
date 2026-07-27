<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\CSSVariableGenerator;
use PhantomCore\Design\TokenRegistry;

class Design_CSS_Variable_Generator_Test extends TestCase {
    private CSSVariableGenerator $generator;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->generator = new CSSVariableGenerator();
    }

    public function test_generate_returns_css_string(): void {
        $css = $this->generator->generate();
        $this->assertIsString($css);
        $this->assertStringStartsWith(':root {', $css);
    }

    public function test_generate_contains_semantic_var_names(): void {
        $css = $this->generator->generate();
        $this->assertStringContainsString('--color-primary', $css);
        $this->assertStringContainsString('--space-md', $css);
        $this->assertStringContainsString('--shadow-sm', $css);
    }

    public function test_generate_ends_with_newline(): void {
        $css = $this->generator->generate();
        $this->assertStringEndsWith("\n", $css);
    }

    public function test_getOutputHook_returns_callable(): void {
        $hook = $this->generator->getOutputHook();
        $this->assertIsCallable($hook);
        $result = $hook('existing-css');
        $this->assertStringStartsWith('existing-css', $result);
        $this->assertStringContainsString('--color-primary', $result);
    }

    public function test_invalidateCache_clears(): void {
        $css1 = $this->generator->generate();
        $this->generator->invalidateCache();
        $css2 = $this->generator->generate();
        $this->assertSame($css1, $css2);
    }
}
