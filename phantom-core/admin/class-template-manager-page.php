<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

use PhantomCore\Registry\Template_Registry;

defined('ABSPATH') || exit;

class TemplateManagerPage {
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

        $registry = Template_Registry::get_instance();
        $registry->register_defaults();
        $templates = $registry->get_all();

        $route_patterns = $registry->get_patterns();

        $html_dir = PHANTOM_CORE_PATH . 'frontend/html/';
        $template_files = [];
        if (is_dir($html_dir)) {
            $template_files = glob($html_dir . '*.html');
        }

        $pack_dir = PHANTOM_CORE_PATH . 'frontend/templates/';
        $packs = [];
        if (is_dir($pack_dir)) {
            $pack_items = scandir($pack_dir);
            foreach ($pack_items as $item) {
                if ('.' === $item || '..' === $item) continue;
                if (is_dir($pack_dir . $item)) {
                    $tmpl_dir = $pack_dir . $item . '/html/';
                    $packs[$item] = is_dir($tmpl_dir) ? count(glob($tmpl_dir . '*.html')) : 0;
                }
            }
        }
        ?>
        <div class="wrap phantom-template-manager">
            <h1><?php esc_html_e('Template Manager', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Manage frontend HTML templates and routes for the SPA shell.', 'phantom-core'); ?></p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px;">
                <div class="phantom-section" style="background:#f5f5f5;padding:15px;border-radius:4px;">
                    <strong><?php esc_html_e('Total Routes', 'phantom-core'); ?></strong><br />
                    <span style="font-size:32px;font-weight:700;"><?php echo count($templates) + count($route_patterns); ?></span>
                </div>
                <div class="phantom-section" style="background:#f5f5f5;padding:15px;border-radius:4px;">
                    <strong><?php esc_html_e('Base Templates', 'phantom-core'); ?></strong><br />
                    <span style="font-size:32px;font-weight:700;"><?php echo count($template_files); ?></span>
                </div>
            </div>

            <!-- Registered Routes -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Registered Routes', 'phantom-core'); ?> (<?php echo count($templates); ?>)</h2>
                <table class="widefat striped" style="max-width:1000px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Route', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Template File', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('Pack', 'phantom-core'); ?></th>
                            <th><?php esc_html_e('404 Page', 'phantom-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><code>/<?php echo esc_html($template->slug); ?></code></td>
                                <td><code><?php echo esc_html($template->file); ?></code></td>
                                <td><?php echo esc_html($template->pack ?: '—'); ?></td>
                                <td><?php echo $template->is_404 ? '⚠️ ' . esc_html__('Yes', 'phantom-core') : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (empty($templates)): ?>
                    <p><em><?php esc_html_e('No routes registered yet.', 'phantom-core'); ?></em></p>
                <?php endif; ?>
            </div>

            <!-- Route Patterns -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Dynamic Route Patterns', 'phantom-core'); ?> (<?php echo count($route_patterns); ?>)</h2>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('Pattern', 'phantom-core'); ?></th><th><?php esc_html_e('Template', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($route_patterns as $pattern): ?>
                            <tr>
                                <td><code>/<?php echo esc_html($pattern['pattern']); ?></code></td>
                                <td><code><?php echo esc_html($pattern['template']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Base Templates -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Base HTML Templates', 'phantom-core'); ?> (<?php echo count($template_files); ?>)</h2>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('Template File', 'phantom-core'); ?></th><th><?php esc_html_e('Size', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($template_files as $file): ?>
                            <tr>
                                <td><code>frontend/html/<?php echo esc_html(basename($file)); ?></code></td>
                                <td><?php echo esc_html(size_format(filesize($file), 2) ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Demo Packs -->
            <div class="phantom-section" style="margin-top:30px;">
                <h2><?php esc_html_e('Demo Template Packs', 'phantom-core'); ?> (<?php echo count($packs); ?>)</h2>
                <table class="widefat striped" style="max-width:800px;">
                    <thead><tr><th><?php esc_html_e('Pack', 'phantom-core'); ?></th><th><?php esc_html_e('Templates', 'phantom-core'); ?></th></tr></thead>
                    <tbody>
                        <?php foreach ($packs as $pack => $count): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($pack)); ?></strong></td>
                                <td><?php echo esc_html((string) $count); ?> <?php esc_html_e('templates', 'phantom-core'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Route Registration Guide -->
            <div class="phantom-section" style="margin-top:30px;padding:20px;background:#f0f8ff;border:1px solid #cce5ff;border-radius:4px;">
                <h2><?php esc_html_e('Registering Custom Routes', 'phantom-core'); ?></h2>
                <p><?php esc_html_e('Developers can register custom routes from themes or plugins:', 'phantom-core'); ?></p>
                <pre style="background:#fff;padding:15px;border:1px solid #ddd;border-radius:3px;overflow-x:auto;"><code>// Register a static route
\PhantomCore\Registry\Template_Registry::get_instance()->register(
    'my-custom-page',         // Route slug
    'my-custom-template',     // Template file (without .html)
    'my-pack',                // Pack name (optional)
    false                     // Is 404?
);

// Register a dynamic pattern
\PhantomCore\Registry\Template_Registry::get_instance()->register_pattern(
    '^portfolio/([a-z0-9-]+)', // Regex pattern
    'portfolio-item'            // Template file
);</code></pre>
            </div>
        </div>
        <?php
    }
}
