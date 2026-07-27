<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Design\DesignSystemManager;

defined('ABSPATH') || exit;

class DesignStudioPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(): void {
        $dsm = DesignSystemManager::get_instance();
        $activeTab = sanitize_key($_GET['tab'] ?? 'presets');
        $tabs = [
            'presets' => __('Presets', 'phantom-core'),
            'dna' => __('Theme DNA', 'phantom-core'),
            'colors' => __('Colors', 'phantom-core'),
            'typography' => __('Typography', 'phantom-core'),
            'spacing' => __('Spacing', 'phantom-core'),
            'motion' => __('Motion', 'phantom-core'),
            'effects' => __('3D & Effects', 'phantom-core'),
            'tokens' => __('All Tokens', 'phantom-core'),
            'css' => __('CSS Preview', 'phantom-core'),
        ];
        ?>
        <div class="wrap phantom-design-studio">
            <h1><?php esc_html_e('Design Studio', 'phantom-core'); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $key => $label): ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $key)); ?>" class="nav-tab <?php echo $activeTab === $key ? 'nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="phantom-tab-content">
                <?php $this->renderTab($activeTab, $dsm); ?>
            </div>
        </div>
        <?php
    }

    private function renderTab(string $tab, DesignSystemManager $dsm): void {
        switch ($tab) {
            case 'presets':
                $this->renderPresetsTab($dsm);
                break;
            case 'dna':
                $this->renderDnaTab($dsm);
                break;
            case 'colors':
                $this->renderCategoryTab($dsm, 'color');
                break;
            case 'typography':
                $this->renderCategoryTab($dsm, 'typography');
                break;
            case 'spacing':
                $this->renderCategoryTab($dsm, 'space', 'spacing');
                break;
            case 'motion':
                $this->renderCategoryTab($dsm, 'motion');
                break;
            case 'effects':
                $this->renderCategoryTab($dsm, 'effect', 'effect');
                break;
            case 'tokens':
                $this->renderTokensTab($dsm);
                break;
            case 'css':
                $this->renderCssTab($dsm);
                break;
        }
    }

    private function renderPresetsTab(DesignSystemManager $dsm): void {
        $presets = $dsm->availablePresets();
        $currentId = $dsm->currentPreset()['id'] ?? null;
        ?>
        <h2><?php esc_html_e('Design Presets', 'phantom-core'); ?></h2>
        <p><?php esc_html_e('Select a preset to apply a complete design theme.', 'phantom-core'); ?></p>
        <div class="phantom-preset-grid">
            <?php foreach ($presets as $preset): ?>
                <div class="phantom-preset-card <?php echo $preset->id === $currentId ? 'active' : ''; ?>" data-preset-id="<?php echo esc_attr($preset->id); ?>">
                    <h3><?php echo esc_html($preset->name); ?></h3>
                    <p class="preset-source"><?php echo esc_html(ucfirst($preset->source)); ?></p>
                    <?php if (!empty($preset->metadata['description'])): ?>
                        <p class="preset-description"><?php echo esc_html($preset->metadata['description']); ?></p>
                    <?php endif; ?>
                    <button class="button button-primary apply-preset" data-id="<?php echo esc_attr($preset->id); ?>"><?php esc_html_e('Apply', 'phantom-core'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function renderDnaTab(DesignSystemManager $dsm): void {
        $dna = $dsm->currentThemeDNA();
        ?>
        <h2><?php esc_html_e('Theme DNA', 'phantom-core'); ?></h2>
        <p><?php esc_html_e('Fine-tune the design personality dimensions.', 'phantom-core'); ?></p>
        <table class="form-table">
            <?php foreach ($dna as $dimension => $value): ?>
                <tr>
                    <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', $dimension))); ?></th>
                    <td><code><?php echo esc_html($value); ?></code></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    private function renderCategoryTab(DesignSystemManager $dsm, string ...$cats): void {
        $tokens = $dsm->tokens($cats);
        ?>
        <h2><?php esc_html_e(ucfirst($cats[0]) . ' Tokens', 'phantom-core'); ?></h2>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Token', 'phantom-core'); ?></th><th><?php esc_html_e('Value', 'phantom-core'); ?></th><th><?php esc_html_e('CSS Variable', 'phantom-core'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($tokens as $category => $catTokens): ?>
                    <?php foreach ($catTokens as $name => $value): ?>
                        <tr>
                            <td><code><?php echo esc_html($name); ?></code></td>
                            <td><?php echo esc_html(is_string($value) ? $value : wp_json_encode($value)); ?></td>
                            <td><code><?php echo esc_html($dsm->cssVar($name)); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function renderTokensTab(DesignSystemManager $dsm): void {
        $all = $dsm->tokens();
        ?>
        <h2><?php esc_html_e('All Design Tokens', 'phantom-core'); ?></h2>
        <table class="widefat striped phantom-token-table">
            <thead><tr><th><?php esc_html_e('Token', 'phantom-core'); ?></th><th><?php esc_html_e('Value', 'phantom-core'); ?></th><th><?php esc_html_e('CSS Variable', 'phantom-core'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($all as $name => $value): ?>
                    <tr>
                        <td><code><?php echo esc_html($name); ?></code></td>
                        <td><?php echo esc_html(is_string($value) ? $value : wp_json_encode($value)); ?></td>
                        <td><code><?php echo esc_html($dsm->cssVar($name)); ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function renderCssTab(DesignSystemManager $dsm): void {
        $css = $dsm->generateCSS();
        ?>
        <h2><?php esc_html_e('Generated CSS Preview', 'phantom-core'); ?></h2>
        <textarea class="phantom-css-preview" readonly rows="30" style="width:100%;font-family:monospace;font-size:12px;"><?php echo esc_textarea($css); ?></textarea>
        <?php
    }
}
