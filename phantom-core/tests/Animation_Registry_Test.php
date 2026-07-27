<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PhantomCore\Animation\Animation;
use PhantomCore\Animation\Animation_Registry;

class Animation_Registry_Test extends TestCase {
    private Animation_Registry $registry;

    protected function setUp(): void {
        $this->registry = Animation_Registry::get_instance();
    }

    public function test_registry_is_singleton(): void {
        $a = Animation_Registry::get_instance();
        $b = Animation_Registry::get_instance();
        $this->assertSame($a, $b);
    }

    public function test_register_defaults_creates_animations(): void {
        $this->registry->register_defaults();
        $this->assertGreaterThan(0, $this->registry->count());
    }

    public function test_get_returns_animation(): void {
        $this->registry->register_defaults();
        $anim = $this->registry->get('fade-up');
        $this->assertInstanceOf(Animation::class, $anim);
        $this->assertSame('fade-up', $anim->id);
    }

    public function test_get_returns_null_for_unknown(): void {
        $this->assertNull($this->registry->get('unknown-anim'));
    }

    public function test_has_returns_true_for_registered(): void {
        $this->registry->register_defaults();
        $this->assertTrue($this->registry->has('fade-up'));
        $this->assertTrue($this->registry->has('zoom-in'));
    }

    public function test_get_by_type_filters_correctly(): void {
        $this->registry->register_defaults();
        $scroll = $this->registry->get_by_type('scroll');
        foreach ($scroll as $anim) {
            $this->assertSame('scroll', $anim->type);
        }
        $hover = $this->registry->get_by_type('hover');
        foreach ($hover as $anim) {
            $this->assertSame('hover', $anim->type);
        }
    }

    public function test_get_by_category_filters_correctly(): void {
        $this->registry->register_defaults();
        $entrance = $this->registry->get_by_category('entrance');
        foreach ($entrance as $anim) {
            $this->assertSame('entrance', $anim->category);
        }
    }

    public function test_get_categories_returns_unique(): void {
        $this->registry->register_defaults();
        $cats = $this->registry->get_categories();
        $this->assertContains('entrance', $cats);
        $this->assertContains('interaction', $cats);
        $this->assertContains('transition', $cats);
    }

    public function test_register_adds_animation(): void {
        $anim = new Animation('test-anim', 'Test', 'scroll', 'entrance', '.test', ['opacity' => 0], true);
        $this->registry->register($anim);
        $this->assertTrue($this->registry->has('test-anim'));
    }

    public function test_deregister_removes_animation(): void {
        $anim = new Animation('temp-anim', 'Temp', 'scroll', 'entrance', '.temp');
        $this->registry->register($anim);
        $this->registry->deregister('temp-anim');
        $this->assertFalse($this->registry->has('temp-anim'));
    }

    public function test_animation_value_object(): void {
        $anim = new Animation('test', 'Test Label', 'scroll', 'entrance', '.test', ['opacity' => 0, 'y' => 20], false);
        $this->assertSame('test', $anim->id);
        $this->assertSame('Test Label', $anim->label);
        $this->assertSame('scroll', $anim->type);
        $this->assertSame('entrance', $anim->category);
        $this->assertSame('.test', $anim->target);
        $this->assertFalse($anim->enabled_by_default);
    }

    public function test_animation_merge_config(): void {
        $anim = new Animation('test', 'Test', 'scroll', 'entrance', '.test', ['opacity' => 0, 'y' => 10], true);
        $anim->merge_config(['opacity' => 1, 'duration' => 0.5]);
        $this->assertSame(1, $anim->config['opacity']);
        $this->assertSame(0.5, $anim->config['duration']);
        $this->assertSame(10, $anim->config['y']); // original default preserved
    }

    public function test_animation_to_array(): void {
        $anim = new Animation('test', 'Test', 'page', 'transition', '#main', ['opacity' => [0, 1]], false);
        $arr = $anim->to_array();
        $this->assertSame('test', $arr['id']);
        $this->assertSame('page', $arr['type']);
        $this->assertFalse($arr['enabled_by_default']);
    }

    public function test_animation_from_array(): void {
        $anim = Animation::from_array([
            'id' => 'reconstructed',
            'label' => 'Reconstructed',
            'type' => 'hover',
            'category' => 'interaction',
            'target' => '.card',
            'defaults' => ['scale' => 1.05],
            'enabled_by_default' => true,
        ]);
        $this->assertInstanceOf(Animation::class, $anim);
        $this->assertSame('reconstructed', $anim->id);
        $this->assertSame('hover', $anim->type);
    }
}
