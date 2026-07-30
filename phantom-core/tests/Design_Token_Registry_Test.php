<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Design\TokenRegistry;

class Design_Token_Registry_Test extends TestCase {
    private TokenRegistry $registry;

    protected function setUp(): void {
        $this->registry = TokenRegistry::get_instance();
        $this->registry->load();
    }

    public function test_singleton_returns_same_instance(): void {
        $this->assertSame(TokenRegistry::get_instance(), $this->registry);
    }

    public function test_get_all_returns_array(): void {
        $tokens = $this->registry->get_all();
        $this->assertIsArray($tokens);
        $this->assertGreaterThan(100, count($tokens));
    }

    public function test_get_returns_token_definition(): void {
        $token = $this->registry->get('color.primary');
        $this->assertIsArray($token);
        $this->assertSame('color', $token['category']);
        $this->assertSame('#C8956C', $token['default']);
        $this->assertSame('phantom_color_primary', $token['option_key']);
    }

    public function test_get_returns_null_for_nonexistent(): void {
        $this->assertNull($this->registry->get('nonexistent.token'));
    }

    public function test_has_returns_true_for_existing(): void {
        $this->assertTrue($this->registry->has('color.primary'));
    }

    public function test_has_returns_false_for_nonexistent(): void {
        $this->assertFalse($this->registry->has('color.nonexistent'));
    }

    public function test_get_by_category_returns_only_matching(): void {
        $radiusTokens = $this->registry->get_by_category('radius');
        $this->assertNotEmpty($radiusTokens);
        foreach ($radiusTokens as $t) {
            $this->assertSame('radius', $t['category']);
        }
    }

    public function test_get_css_var_converts_dot_notation(): void {
        $this->assertSame('--color-primary', $this->registry->get_css_var('color.primary'));
        $this->assertSame('--typography-heading-font', $this->registry->get_css_var('typography.heading.font'));
        $this->assertSame('--space-xl', $this->registry->get_css_var('space.xl'));
    }

    public function test_get_option_key_returns_configured_key(): void {
        $this->assertSame('phantom_color_primary', $this->registry->get_option_key('color.primary'));
    }

    public function test_get_default_returns_configured_default(): void {
        $this->assertSame('#C8956C', $this->registry->get_default('color.primary'));
    }

    public function test_get_type_returns_correct_type(): void {
        $this->assertSame('color', $this->registry->get_type('color.primary'));
        $this->assertSame('size', $this->registry->get_type('space.md'));
        $this->assertSame('shadow', $this->registry->get_type('shadow.md'));
    }

    public function test_count_returns_expected(): void {
        $this->assertGreaterThanOrEqual(140, $this->registry->count());
    }

    public function test_all_token_names_use_valid_format(): void {
        foreach ($this->registry->get_all() as $name => $def) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+(\.[a-z0-9_-]+)+$/', $name, "Invalid token name: $name");
        }
    }

    public function test_all_tokens_have_required_fields(): void {
        foreach ($this->registry->get_all() as $name => $def) {
            $this->assertArrayHasKey('name', $def, "Missing name for $name");
            $this->assertArrayHasKey('category', $def, "Missing category for $name");
            $this->assertArrayHasKey('type', $def, "Missing type for $name");
            $this->assertArrayHasKey('default', $def, "Missing default for $name");
            $this->assertArrayHasKey('option_key', $def, "Missing option_key for $name");
        }
    }
}
