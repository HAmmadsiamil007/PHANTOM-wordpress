<?php
declare(strict_types=1);

namespace PhantomCore\Customizer\Controls;

defined('ABSPATH') || exit;

class Preset_Card_Control extends Control_Base {

    public $type = 'ast-preset-card';

    public static function get_type(): string {
        return 'ast-preset-card';
    }

    public static function get_sanitize_callback(): callable {
        return 'sanitize_text_field';
    }

    public function render_content(): void {
        $presets = $this->choices ?? [];
        $current = $this->value();
        ?>
        <label>
            <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
            <?php if ($this->description) : ?>
                <span class="description customize-control-description"><?php echo esc_html($this->description); ?></span>
            <?php endif; ?>
        </label>
        <div class="ast-preset-card-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
            <?php foreach ($presets as $slug => $preset) :
                $name = $preset['name'] ?? $slug;
                $desc = $preset['description'] ?? '';
                $colors = $preset['colors'] ?? [];
                $active = ($slug === $current) ? ' ast-preset-card--active' : '';
            ?>
                <div class="ast-preset-card<?php echo $active; ?>" data-preset-slug="<?php echo esc_attr($slug); ?>" style="border:2px solid <?php echo $active ? '#2271b1' : '#ddd'; ?>;border-radius:8px;padding:10px;cursor:pointer;transition:border-color .15s;background:#fff;">
                    <div class="ast-preset-card-swatches" style="display:flex;gap:4px;margin-bottom:6px;">
                        <?php foreach ($colors as $c) : ?>
                            <span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:<?php echo esc_attr($c); ?>;border:1px solid rgba(0,0,0,0.1);"></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="ast-preset-card-name" style="font-weight:600;font-size:12px;line-height:1.3;"><?php echo esc_html($name); ?></div>
                    <?php if ($desc) : ?>
                        <div class="ast-preset-card-desc" style="font-size:11px;color:#666;margin-top:2px;"><?php echo esc_html($desc); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <input type="hidden" class="ast-preset-card-input" value="<?php echo esc_attr($current); ?>" <?php $this->link(); ?>>
        <?php
    }

    public function content_template(): void {
        ?>
        <label>
            <span class="customize-control-title">{{ data.label }}</span>
            <# if ( data.description ) { #>
                <span class="description customize-control-description">{{ data.description }}</span>
            <# } #>
        </label>
        <div class="ast-preset-card-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px;">
            <# _.each( data.choices, function( preset, slug ) { #>
                <div class="ast-preset-card<# if ( slug === data.value ) { #> ast-preset-card--active<# } #>" data-preset-slug="{{ slug }}" style="border:2px solid <# if ( slug === data.value ) { #>#2271b1<# } else { #>#ddd<# } #>;border-radius:8px;padding:10px;cursor:pointer;transition:border-color .15s;background:#fff;">
                    <div class="ast-preset-card-swatches" style="display:flex;gap:4px;margin-bottom:6px;">
                        <# _.each( preset.colors, function( color ) { #>
                            <span style="display:inline-block;width:24px;height:24px;border-radius:4px;background:{{ color }};border:1px solid rgba(0,0,0,0.1);"></span>
                        <# } ) #>
                    </div>
                    <div class="ast-preset-card-name" style="font-weight:600;font-size:12px;line-height:1.3;">{{ preset.name }}</div>
                    <# if ( preset.description ) { #>
                        <div class="ast-preset-card-desc" style="font-size:11px;color:#666;margin-top:2px;">{{ preset.description }}</div>
                    <# } #>
                </div>
            <# } ) #>
        </div>
        <input type="hidden" class="ast-preset-card-input" value="{{ data.value }}" data-customize-setting-link="{{ data.settings.default }}">
        <?php
    }
}
