<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Components\Component_Registry;

defined('ABSPATH') || exit;

class ComponentLibraryPage {
    private static ?self $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'phantom-core'));
        }

        $registry = Component_Registry::get_instance();
        $registry->register_defaults();
        $components = $registry->get_all();

        $adapter_path = PHANTOM_CORE_PATH . 'includes/adapters/';
        $adapter_files = glob($adapter_path . 'class-*.php');
        $renderer_path = PHANTOM_CORE_PATH . 'includes/renderer/';
        $renderer_files = glob($renderer_path . 'class-*.php');
        ?>
        <div class="wrap phantom-component-library">
            <h1><?php esc_html_e('Component Library', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Browse all registered UI components, adapters, and renderers available in Phantom Core.', 'phantom-core'); ?></p>

            <!-- Registered Components -->
            <div class="phantom-section" style="margin-top:20px;">
                <h2><?php esc_html_e('Registered Components', 'phantom-core'); ?> (<?php echo count($components); ?>)</h2>
                <table class="widefat striped" style="max-width:1000px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Class', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Category', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Status', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Preview', 'phantom-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($components as $component): ?>
                            <tr>
                                <td><code><?php echo esc_html($component->id); ?></code></td>
                                <td><code><?php echo esc_html($component->class_name); ?></code></td>
                                <td><?php echo esc_html(ucfirst($component->category)); ?></td>
                                <td>
                                    <?php if (class_exists($component->class_name)): ?>
                                        <span style="color:#46b450;">✅ <?php esc_html_e('Available', 'phantom-core'); ?></span>
                                    <?php else: ?>
                                        <span style="color:#dc3232;">❌ <?php esc_html_e('Missing', 'phantom-core'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small phantom-preview-component" data-id="<?php echo esc_attr($component->id); ?>">
                                        <?php esc_html_e('Render Test', 'phantom-core'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($components)): ?>
                    <p><em><?php esc_html_e('No components registered yet. Activate the Component Registry to register default components.', 'phantom-core'); ?></em></p>
                <?php endif; ?>
            </div>

            <!-- Renderers -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Available Renderers', 'phantom-core'); ?> (<?php echo count($renderer_files); ?>)</h2>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($renderer_files as $file): ?>
                            <tr>
                                <td><code><?php echo esc_html(basename($file)); ?></code></td>
                                <td><?php echo esc_html(size_format(filesize($file), 2) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Adapters -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Data Adapters', 'phantom-core'); ?> (<?php echo count($adapter_files); ?>)</h2>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($adapter_files as $file): ?>
                            <tr>
                                <td><code><?php echo esc_html(basename($file)); ?></code></td>
                                <td><?php echo esc_html(size_format(filesize($file), 2) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Component Registration Guide -->
            <div class="phantom-section" style="margin-top:30px;padding:20px;background:#f0f8ff;border:1px solid #cce5ff;border-radius:4px;">
                <h2><?php esc_html_e('Registering Custom Components', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Developers can register custom components from themes or plugins:', 'phantom-core'); ?></p>
                <pre style="background:#fff;padding:15px;border:1px solid #ddd;border-radius:3px;overflow-x:auto;"><code>// Register a custom component
\PhantomCore\Components\Component_Registry::get_instance()->register(
    'my-custom-card',        // Component ID
    'My_Custom_Renderer',    // Class name
    'custom'                 // Category
);

// Or from a plugin/theme functions.php:
add_action('init', function() {
    \PhantomCore\Components\Component_Registry::get_instance()->register(
        'my-custom-card',
        'My_Custom_Renderer',
        'custom'
    );
});</code></pre>
            </div>
        </div>
        <?php
    }
}
