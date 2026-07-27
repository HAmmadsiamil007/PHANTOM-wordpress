<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class PerformancePage {
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

        $this->handle_save();

        $minify_css = get_option('phantom_performance_minify_css', '0');
        $minify_js = get_option('phantom_performance_minify_js', '0');
        $lazy_load = get_option('phantom_performance_lazy_load', '1');
        $defer_js = get_option('phantom_performance_defer_js', '0');
        $preconnect = get_option('phantom_performance_preconnect', '');
        $dns_prefetch = get_option('phantom_performance_dns_prefetch', '');
        $preload_fonts = get_option('phantom_performance_preload_fonts', '');
        $preload_hero = get_option('phantom_performance_preload_hero', '1');
        $remove_wp_bloat = get_option('phantom_performance_remove_wp_bloat', '0');
        $emoji_removal = get_option('phantom_performance_emoji_removal', '0');
        $comment_js = get_option('phantom_performance_comment_js', '0');
        $heartbeat = get_option('phantom_performance_heartbeat', 'default');
        ?>
        <div class="wrap phantom-performance">
            <h1><?php esc_html_e('Performance', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Optimize your site speed with caching, asset minification, and WordPress bloat removal.', 'phantom-core'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('phantom_performance_save', 'phantom_performance_nonce'); ?>
                <input type="hidden" name="action" value="save_performance" />

                <!-- Asset Optimization -->
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Asset Optimization', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Minify CSS', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="minify_css" value="1" <?php checked('1', $minify_css); ?> />
                                <?php esc_html_e('Remove whitespace and comments from generated CSS files.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Minify JavaScript', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="minify_js" value="1" <?php checked('1', $minify_js); ?> />
                                <?php esc_html_e('Minify Phantom Core JavaScript bundles.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Lazy Load Images', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="lazy_load" value="1" <?php checked('1', $lazy_load); ?> />
                                <?php esc_html_e('Add loading="lazy" to all images below the fold.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Defer JavaScript', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="defer_js" value="1" <?php checked('1', $defer_js); ?> />
                                <?php esc_html_e('Add defer attribute to non-critical JavaScript.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Preload Hero Image', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="preload_hero" value="1" <?php checked('1', $preload_hero); ?> />
                                <?php esc_html_e('Add preload link for the hero background image.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Resource Hints -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Resource Hints', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="preconnect"><?php esc_html_e('Preconnect URLs', 'phantom-core'); ?></label></th>
                            <td>
                                <textarea id="preconnect" name="preconnect" class="large-text" rows="3"><?php echo esc_textarea($preconnect); ?></textarea>
                                <p class="description"><?php esc_html_e('One URL per line. E.g. https://fonts.googleapis.com', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="dns_prefetch"><?php esc_html_e('DNS Prefetch URLs', 'phantom-core'); ?></label></th>
                            <td>
                                <textarea id="dns_prefetch" name="dns_prefetch" class="large-text" rows="3"><?php echo esc_textarea($dns_prefetch); ?></textarea>
                                <p class="description"><?php esc_html_e('One URL per line. E.g. https://example.com', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="preload_fonts"><?php esc_html_e('Preload Fonts', 'phantom-core'); ?></label></th>
                            <td>
                                <textarea id="preload_fonts" name="preload_fonts" class="large-text" rows="3"><?php echo esc_textarea($preload_fonts); ?></textarea>
                                <p class="description"><?php esc_html_e('One URL per line. E.g. /wp-content/themes/phantom-theme/assets/fonts/custom.woff2', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- WordPress Bloat -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('WordPress Bloat Removal', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Remove WP Assets', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="remove_wp_bloat" value="1" <?php checked('1', $remove_wp_bloat); ?> />
                                <?php esc_html_e('Remove block-library CSS, global styles, and wp-embed script.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Disable Emojis', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="emoji_removal" value="1" <?php checked('1', $emoji_removal); ?> />
                                <?php esc_html_e('Remove WordPress emoji scripts and styles.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Comment JS', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="comment_js" value="1" <?php checked('1', $comment_js); ?> />
                                <?php esc_html_e('Remove comment-reply JS on pages without comments.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="heartbeat"><?php esc_html_e('Heartbeat Control', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="heartbeat" name="heartbeat">
                                    <option value="default" <?php selected('default', $heartbeat); ?>><?php esc_html_e('Default (WordPress)', 'phantom-core'); ?></option>
                                    <option value="minimal" <?php selected('minimal', $heartbeat); ?>><?php esc_html_e('Minimal (15s interval)', 'phantom-core'); ?></option>
                                    <option value="disabled" <?php selected('disabled', $heartbeat); ?>><?php esc_html_e('Disabled (not recommended)', 'phantom-core'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Reduce or disable WordPress Heartbeat API to save server resources.', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Performance Score -->
                <?php
                $score = $this->calculate_score();
                $color = $score >= 80 ? '#46b450' : ($score >= 50 ? '#ffb900' : '#dc3232');
                ?>
                <div class="phantom-section" style="margin-top:30px;padding:20px;background:#f5f5f5;border-radius:4px;">
                    <h2><?php esc_html_e('Estimated Performance Score', 'phantom-core'); ?></h2>
                    <div style="display:flex;align-items:center;gap:20px;">
                        <div style="width:100px;height:100px;border-radius:50%;background:conic-gradient(<?php echo esc_attr($color); ?> 0% <?php echo esc_attr((string) $score); ?>%, #e0e0e0 <?php echo esc_attr((string) $score); ?>% 100%);display:flex;align-items:center;justify-content:center;">
                            <span style="background:#fff;width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:<?php echo esc_attr($color); ?>;"><?php echo esc_html((string) $score); ?></span>
                        </div>
                        <div>
                            <p style="margin:0;"><?php esc_html_e('Based on your current configuration settings.', 'phantom-core'); ?></p>
                            <p style="margin:5px 0 0;color:#888;"><?php esc_html_e('Enable minification, lazy loading, and WP bloat removal for the best score.', 'phantom-core'); ?></p>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Save Performance Settings', 'phantom-core'), 'primary', 'save_performance'); ?>
            </form>
        </div>
        <?php
    }

    private function calculate_score(): int {
        $score = 50;
        if ('1' === get_option('phantom_performance_minify_css', '0')) $score += 10;
        if ('1' === get_option('phantom_performance_minify_js', '0')) $score += 10;
        if ('1' === get_option('phantom_performance_lazy_load', '1')) $score += 10;
        if ('1' === get_option('phantom_performance_defer_js', '0')) $score += 5;
        if ('1' === get_option('phantom_performance_preload_hero', '1')) $score += 5;
        if ('1' === get_option('phantom_performance_remove_wp_bloat', '0')) $score += 10;
        if ('1' === get_option('phantom_performance_emoji_removal', '0')) $score += 5;
        $preconnect = get_option('phantom_performance_preconnect', '');
        if (!empty($preconnect)) $score += 5;
        return min(100, $score);
    }

    private function handle_save(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_performance_nonce'])) return;
        if (!wp_verify_nonce(wp_unslash($_POST['phantom_performance_nonce']), 'phantom_performance_save')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'phantom-core'));
        }

        $fields = [
            'minify_css' => 'phantom_performance_minify_css',
            'minify_js' => 'phantom_performance_minify_js',
            'lazy_load' => 'phantom_performance_lazy_load',
            'defer_js' => 'phantom_performance_defer_js',
            'preload_hero' => 'phantom_performance_preload_hero',
            'remove_wp_bloat' => 'phantom_performance_remove_wp_bloat',
            'emoji_removal' => 'phantom_performance_emoji_removal',
            'comment_js' => 'phantom_performance_comment_js',
        ];
        foreach ($fields as $field => $option) {
            update_option($option, isset($_POST[$field]) ? '1' : '0');
        }

        update_option('phantom_performance_preconnect', sanitize_textarea_field(wp_unslash($_POST['preconnect'] ?? '')));
        update_option('phantom_performance_dns_prefetch', sanitize_textarea_field(wp_unslash($_POST['dns_prefetch'] ?? '')));
        update_option('phantom_performance_preload_fonts', sanitize_textarea_field(wp_unslash($_POST['preload_fonts'] ?? '')));
        update_option('phantom_performance_heartbeat', sanitize_key($_POST['heartbeat'] ?? 'default'));

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Performance settings saved.', 'phantom-core') . '</p></div>';
    }
}
