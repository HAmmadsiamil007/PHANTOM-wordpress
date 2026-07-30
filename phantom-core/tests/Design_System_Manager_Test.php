<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\DesignSystemManager;

class Design_System_Manager_Test extends TestCase {
    private DesignSystemManager $dsm;

    protected function setUp(): void {
        $this->dsm = DesignSystemManager::get_instance();
        $this->dsm->init();
    }

    public function test_singleton(): void {
        $this->assertSame($this->dsm, DesignSystemManager::get_instance());
    }

    public function test_token_returns_value(): void {
        $this->assertSame('#C8956C', $this->dsm->token('color.primary'));
    }

    public function test_tokens_returns_all(): void {
        $all = $this->dsm->tokens();
        $this->assertArrayHasKey('color.primary', $all);
    }

    public function test_tokens_with_categories(): void {
        $result = $this->dsm->tokens(['radius', 'shadow']);
        $this->assertArrayHasKey('radius', $result);
        $this->assertArrayHasKey('shadow', $result);
    }

    public function test_cssVar_returns_var_name(): void {
        $this->assertSame('--color-primary', $this->dsm->cssVar('color.primary'));
    }

    public function test_allCssVars_returns_map(): void {
        $vars = $this->dsm->allCssVars();
        $this->assertArrayHasKey('--color-primary', $vars);
        $this->assertGreaterThan(100, count($vars));
    }

    public function test_generateCSS_returns_css(): void {
        $css = $this->dsm->generateCSS();
        $this->assertStringContainsString('--color-primary', $css);
    }

    public function test_validate_returns_array(): void {
        $results = $this->dsm->validate();
        $this->assertGreaterThan(100, count($results));
    }

    public function test_compile_returns_compiled_token_set(): void {
        $set = $this->dsm->compile();
        $this->assertInstanceOf(\PhantomCore\Design\CompiledTokenSet::class, $set);
    }


}
