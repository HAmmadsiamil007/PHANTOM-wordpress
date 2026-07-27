<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenResolver;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Resolver_Test extends TestCase {
    private TokenResolver $resolver;

    protected function setUp(): void {
        TokenRegistry::get_instance()->load();
        $this->resolver = new TokenResolver();
    }

    public function test_resolve_returns_default_when_option_not_set(): void {
        $value = $this->resolver->resolve('color.primary');
        $this->assertSame('#C1121F', $value);
    }

    public function test_resolve_returns_option_value_when_set(): void {
        update_option('phantom_primary_color', '#FF0000');
        $this->resolver->invalidateCache();
        $this->assertSame('#FF0000', $this->resolver->resolve('color.primary'));
    }

    public function test_resolve_returns_null_for_nonexistent_token(): void {
        $this->assertNull($this->resolver->resolve('nonexistent.token'));
    }

    public function test_resolveAll_returns_array(): void {
        $all = $this->resolver->resolveAll();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('color.primary', $all);
        $this->assertArrayHasKey('space.md', $all);
    }

    public function test_resolveAll_with_filter(): void {
        $subset = $this->resolver->resolveAll(['color.primary', 'space.md']);
        $this->assertCount(2, $subset);
        $this->assertArrayHasKey('color.primary', $subset);
        $this->assertArrayHasKey('space.md', $subset);
    }

    public function test_resolveCategory_returns_only_category(): void {
        $radius = $this->resolver->resolveCategory('radius');
        $this->assertNotEmpty($radius);
        foreach ($radius as $name => $value) {
            $this->assertStringStartsWith('radius.', $name);
        }
    }

    public function test_invalidateCache_clears(): void {
        update_option('phantom_primary_color', '#00FF00');
        $this->resolver->invalidateCache();
        $this->assertSame('#00FF00', $this->resolver->resolve('color.primary'));
    }
}
