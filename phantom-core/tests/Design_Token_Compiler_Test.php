<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenCompiler;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Compiler_Test extends TestCase {
    private TokenCompiler $compiler;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->compiler = new TokenCompiler();
    }

    public function test_compile_returns_compiled_token_set(): void {
        $set = $this->compiler->compile();
        $this->assertInstanceOf(\PhantomCore\Design\CompiledTokenSet::class, $set);
    }

    public function test_compile_contains_all_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertGreaterThan(100, count($set->tokens));
        $this->assertArrayHasKey('color.primary', $set->tokens);
    }

    public function test_compile_contains_css_vars(): void {
        $set = $this->compiler->compile();
        $this->assertArrayHasKey('color.primary', $set->cssVars);
        $this->assertSame('--color-primary', $set->cssVars['color.primary']['var']);
    }

    public function test_compile_contains_component_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertIsArray($set->components);
    }

    public function test_compile_contains_responsive_tokens(): void {
        $set = $this->compiler->compile();
        $this->assertArrayHasKey('sm', $set->responsive);
    }

    public function test_invalidateCache_clears(): void {
        $set1 = $this->compiler->compile();
        $this->compiler->invalidateCache();
        $this->assertNotNull($this->compiler->compile());
    }

    public function test_compileCategory_returns_category(): void {
        $radius = $this->compiler->compileCategory('radius');
        $this->assertNotEmpty($radius);
        foreach ($radius as $name => $value) {
            $this->assertStringStartsWith('radius.', $name);
        }
    }
}
