<?php
declare(strict_types=1);

use PhantomCore\Customizer;
use PhantomCore\Customizer\Controls\Asset_Grid_Control;
use PhantomCore\Customizer\Controls\Control_Base;
use PhantomCore\Customizer\Controls\Visual_Inspector_Control;
use PhantomCore\Customizer\Controls\Visual_Toggle_Control;
use PHPUnit\Framework\TestCase;

require_once PHANTOM_CORE_PATH . 'includes/custom-controls/class-control-base.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-controls/class-asset-grid-control.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-controls/class-visual-toggle-control.php';
require_once PHANTOM_CORE_PATH . 'includes/custom-controls/class-visual-inspector-control.php';

/**
 * Visual Customizer (Phase A) structure tests — slim curated sections,
 * PHANTOM panel (Visual Colors / Typography / Spacing / Assets / Live
 * Preview / Element Inspector), legacy panels removed, new controls wired.
 */
class Customizer_Visual_Panel_Test extends TestCase {

    private Customizer $customizer;
    private array $entries;

    protected function setUp(): void {
        parent::setUp();
        \PhantomCore\Settings_Registry::get_instance()->register();
        $this->customizer = Customizer::get_instance();
        $this->entries = \PhantomCore\Settings_Registry::get_instance()->get_entries();
    }

    private function sections(string $method): array {
        $ref = new ReflectionMethod($this->customizer, $method);
        $ref->setAccessible(true);
        return (array) $ref->invoke($this->customizer);
    }

    public function test_singleton(): void {
        $this->assertSame(
            Customizer::get_instance(),
            Customizer::get_instance()
        );
    }

    public function test_curated_sections_are_slim(): void {
        $sections = $this->sections('curated_sections');
        $this->assertArrayHasKey('phantom_section_homepage', $sections);
        $this->assertArrayHasKey('phantom_section_blog', $sections);
        $this->assertArrayHasKey('phantom_section_woocommerce', $sections);
        $this->assertCount(3, $sections);
        foreach ($sections as $cfg) {
            $this->assertLessThanOrEqual(5, count($cfg['keys']));
        }
    }

    public function test_visual_sections_are_slim(): void {
        $sections = $this->sections('visual_sections');
        $this->assertArrayHasKey('phantom_section_colors', $sections);
        $this->assertArrayHasKey('phantom_section_typography', $sections);
        $this->assertArrayHasKey('phantom_section_spacing', $sections);
        $this->assertCount(3, $sections);
    }

    public function test_visual_colors_five_global_keys(): void {
        $sections = $this->sections('visual_sections');
        $keys = $sections['phantom_section_colors']['keys'];
        $this->assertSame(
            ['color_primary', 'color_secondary', 'color_accent', 'color_success', 'color_error'],
            $keys
        );
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->entries, "Missing registry entry: $key");
            $this->assertSame('ast-color', $this->entries[$key]['type'] ?? null, "$key should be ast-color");
        }
    }

    public function test_visual_typography_keys_exist_in_registry(): void {
        $sections = $this->sections('visual_sections');
        foreach ($sections['phantom_section_typography']['keys'] as $key) {
            $this->assertArrayHasKey($key, $this->entries, "Missing registry entry: $key");
        }
    }

    public function test_visual_spacing_keys_exist_in_registry(): void {
        $sections = $this->sections('visual_sections');
        foreach ($sections['phantom_section_spacing']['keys'] as $key) {
            $this->assertArrayHasKey($key, $this->entries, "Missing registry entry: $key");
        }
    }

    public function test_legacy_panels_removed_from_source(): void {
        $source = file_get_contents(PHANTOM_CORE_PATH . 'includes/class-customizer.php');
        foreach (['phantom_branding', 'phantom_global_colors', 'phantom_global_typography',
                  'phantom_presets', 'phantom_responsive', 'phantom_performance',
                  'phantom_integrations', 'Design Studio'] as $legacy) {
            $this->assertStringNotContainsString($legacy, $source, "Legacy reference '$legacy' still in customizer");
        }
    }

    public function test_visual_structure_present_in_source(): void {
        $source = file_get_contents(PHANTOM_CORE_PATH . 'includes/class-customizer.php');
        $this->assertStringContainsString("'phantom_visual'", $source);
        $this->assertStringContainsString('phantom_section_colors', $source);
        $this->assertStringContainsString('phantom_section_assets', $source);
        $this->assertStringContainsString('phantom_section_live_preview', $source);
        $this->assertStringContainsString('phantom_section_inspector', $source);
    }

    public function test_new_control_types_registered_in_control_base(): void {
        $this->assertSame(Asset_Grid_Control::class, Control_Base::get_class_for_type('ast-asset-grid'));
        $this->assertSame(Visual_Toggle_Control::class, Control_Base::get_class_for_type('ast-visual-toggle'));
        $this->assertSame(Visual_Inspector_Control::class, Control_Base::get_class_for_type('ast-visual-inspector'));
    }

    public function test_asset_grid_control_renders_rows(): void {
        $control = new Asset_Grid_Control(new \stdClass(), 'phantom_visual_assets', []);
        ob_start();
        $control->render_content();
        $output = ob_get_clean();

        $this->assertStringContainsString('phantom-asset-grid', $output);
        $this->assertStringContainsString('vc-asset-row', $output);
        $this->assertStringContainsString('data-asset="logo"', $output);
        $this->assertStringContainsString('data-asset="hero_desktop"', $output);
        $this->assertStringContainsString('data-asset="author_avatar"', $output);
        $this->assertStringContainsString('vc-btn-upload', $output);
        $this->assertStringContainsString('vc-btn-reset', $output);
    }

    public function test_visual_toggle_control_renders_start_editing(): void {
        $control = new Visual_Toggle_Control(new \stdClass(), 'phantom_live_preview_edit', []);
        ob_start();
        $control->render_content();
        $output = ob_get_clean();

        $this->assertStringContainsString('id="phantom-live-preview-edit"', $output);
        $this->assertStringContainsString('phantom-visual-toggle', $output);
        $this->assertStringContainsString('Start Editing', $output);
    }

    public function test_visual_inspector_control_renders_container(): void {
        $control = new Visual_Inspector_Control(new \stdClass(), 'phantom_visual_inspector', []);
        ob_start();
        $control->render_content();
        $output = ob_get_clean();

        $this->assertStringContainsString('id="phantom-visual-inspector"', $output);
        $this->assertStringContainsString('phantom-inspector-hint', $output);
    }

    public function test_get_inline_css_produces_root_vars(): void {
        $key = 'phantom_color_primary';
        $before = array_key_exists($key, $GLOBALS['_phantom_options']) ? $GLOBALS['_phantom_options'][$key] : null;

        \PhantomCore\Settings_Registry::get_instance()->set('color_primary', '#ff0000');
        $css = $this->customizer->get_inline_css();
        $this->assertStringContainsString(':root{', $css);
        $this->assertStringContainsString('--primary--color', $css);
        $this->assertStringContainsString('#ff0000', $css);

        if (null === $before) {
            unset($GLOBALS['_phantom_options'][$key]);
        } else {
            $GLOBALS['_phantom_options'][$key] = $before;
        }
    }
}
