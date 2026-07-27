<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class SEOPage {
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

        $home_title = get_option('phantom_seo_home_title', '');
        $home_desc = get_option('phantom_seo_home_description', '');
        $og_enabled = get_option('phantom_seo_og_enabled', '1');
        $twitter_cards = get_option('phantom_seo_twitter_cards', '1');
        $sitemap_enabled = get_option('phantom_seo_sitemap_enabled', '0');
        $canonical = get_option('phantom_seo_canonical', '1');
        $meta_desc = get_option('phantom_seo_meta_description', '');
        $meta_keywords = get_option('phantom_seo_meta_keywords', '');
        $fb_app_id = get_option('phantom_seo_fb_app_id', '');
        $twitter_handle = get_option('phantom_seo_twitter_handle', '');
        $noindex_archives = get_option('phantom_seo_noindex_archives', '0');
        $noindex_tags = get_option('phantom_seo_noindex_tags', '0');
        $schema_type = get_option('phantom_seo_schema_type', 'Organization');
        $schema_name = get_option('phantom_seo_schema_name', get_bloginfo('name'));
        ?>
        <div class="wrap phantom-seo">
            <h1><?php esc_html_e('SEO', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Search engine optimization settings for better visibility in search results.', 'phantom-core'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('phantom_seo_save', 'phantom_seo_nonce'); ?>
                <input type="hidden" name="action" value="save_seo" />

                <!-- Home Page SEO -->
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Home Page', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="home_title"><?php esc_html_e('SEO Title', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="home_title" name="home_title" class="large-text" value="<?php echo esc_attr($home_title); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
                                <p class="description"><?php esc_html_e('Custom title tag for the homepage. Leave empty to use the site title.', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="home_desc"><?php esc_html_e('Meta Description', 'phantom-core'); ?></label></th>
                            <td>
                                <textarea id="home_desc" name="home_desc" class="large-text" rows="3"><?php echo esc_textarea($home_desc); ?></textarea>
                                <p class="description"><?php esc_html_e('A compelling description of your site (120-160 characters recommended).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Global SEO -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Global Settings', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="meta_desc"><?php esc_html_e('Default Meta Description', 'phantom-core'); ?></label></th>
                            <td>
                                <textarea id="meta_desc" name="meta_desc" class="large-text" rows="3"><?php echo esc_textarea($meta_desc); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="meta_keywords"><?php esc_html_e('Meta Keywords', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="meta_keywords" name="meta_keywords" class="large-text" value="<?php echo esc_attr($meta_keywords); ?>" placeholder="<?php esc_attr_e('e.g., kids, toys, educational', 'phantom-core'); ?>" />
                                <p class="description"><?php esc_html_e('Comma-separated keywords (less important for modern SEO).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Canonical URLs', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="canonical" value="1" <?php checked('1', $canonical); ?> />
                                <?php esc_html_e('Auto-generate canonical link tags to prevent duplicate content.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Noindex Archives', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="noindex_archives" value="1" <?php checked('1', $noindex_archives); ?> />
                                <?php esc_html_e('Add noindex to date and author archives.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Noindex Tags', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="noindex_tags" value="1" <?php checked('1', $noindex_tags); ?> />
                                <?php esc_html_e('Add noindex to tag archives.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Social / Open Graph -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Social & Open Graph', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Open Graph Tags', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="og_enabled" value="1" <?php checked('1', $og_enabled); ?> />
                                <?php esc_html_e('Generate Open Graph meta tags for Facebook, LinkedIn, and other social platforms.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Twitter Cards', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="twitter_cards" value="1" <?php checked('1', $twitter_cards); ?> />
                                <?php esc_html_e('Generate Twitter Card meta tags for rich Twitter previews.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="fb_app_id"><?php esc_html_e('Facebook App ID', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="fb_app_id" name="fb_app_id" class="regular-text" value="<?php echo esc_attr($fb_app_id); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="twitter_handle"><?php esc_html_e('Twitter Username', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="twitter_handle" name="twitter_handle" class="regular-text" value="<?php echo esc_attr($twitter_handle); ?>" placeholder="@yourhandle" />
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Sitemap -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('XML Sitemap', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Sitemap', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="sitemap_enabled" value="1" <?php checked('1', $sitemap_enabled); ?> />
                                <?php esc_html_e('Generate a basic XML sitemap at /sitemap.xml.', 'phantom-core'); ?></label>
                                <?php if ('1' === $sitemap_enabled): ?>
                                    <p><a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" class="button button-small"><?php esc_html_e('View Sitemap', 'phantom-core'); ?> ↗</a></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Schema -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Schema.org (Structured Data)', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="schema_type"><?php esc_html_e('Organization Type', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="schema_type" name="schema_type">
                                    <option value="Organization" <?php selected('Organization', $schema_type); ?>><?php esc_html_e('Organization', 'phantom-core'); ?></option>
                                    <option value="Corporation" <?php selected('Corporation', $schema_type); ?>><?php esc_html_e('Corporation', 'phantom-core'); ?></option>
                                    <option value="LocalBusiness" <?php selected('LocalBusiness', $schema_type); ?>><?php esc_html_e('Local Business', 'phantom-core'); ?></option>
                                    <option value="OnlineStore" <?php selected('OnlineStore', $schema_type); ?>><?php esc_html_e('Online Store', 'phantom-core'); ?></option>
                                    <option value="EducationalOrganization" <?php selected('EducationalOrganization', $schema_type); ?>><?php esc_html_e('Educational', 'phantom-core'); ?></option>
                                    <option value="NGO" <?php selected('NGO', $schema_type); ?>><?php esc_html_e('Non-Profit / NGO', 'phantom-core'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="schema_name"><?php esc_html_e('Organization Name', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="text" id="schema_name" name="schema_name" class="regular-text" value="<?php echo esc_attr($schema_name); ?>" />
                                <p class="description"><?php esc_html_e('Used in JSON-LD structured data.', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save SEO Settings', 'phantom-core'), 'primary', 'save_seo'); ?>
            </form>
        </div>
        <?php
    }

    private function handle_save(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_seo_nonce'])) return;
        if (!wp_verify_nonce(wp_unslash($_POST['phantom_seo_nonce']), 'phantom_seo_save')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'phantom-core'));
        }

        update_option('phantom_seo_home_title', sanitize_text_field(wp_unslash($_POST['home_title'] ?? '')));
        update_option('phantom_seo_home_description', sanitize_textarea_field(wp_unslash($_POST['home_desc'] ?? '')));
        update_option('phantom_seo_og_enabled', isset($_POST['og_enabled']) ? '1' : '0');
        update_option('phantom_seo_twitter_cards', isset($_POST['twitter_cards']) ? '1' : '0');
        update_option('phantom_seo_sitemap_enabled', isset($_POST['sitemap_enabled']) ? '1' : '0');
        update_option('phantom_seo_canonical', isset($_POST['canonical']) ? '1' : '0');
        update_option('phantom_seo_meta_description', sanitize_textarea_field(wp_unslash($_POST['meta_desc'] ?? '')));
        update_option('phantom_seo_meta_keywords', sanitize_text_field(wp_unslash($_POST['meta_keywords'] ?? '')));
        update_option('phantom_seo_fb_app_id', sanitize_text_field(wp_unslash($_POST['fb_app_id'] ?? '')));
        update_option('phantom_seo_twitter_handle', sanitize_text_field(wp_unslash($_POST['twitter_handle'] ?? '')));
        update_option('phantom_seo_noindex_archives', isset($_POST['noindex_archives']) ? '1' : '0');
        update_option('phantom_seo_noindex_tags', isset($_POST['noindex_tags']) ? '1' : '0');
        update_option('phantom_seo_schema_type', sanitize_key($_POST['schema_type'] ?? 'Organization'));
        update_option('phantom_seo_schema_name', sanitize_text_field(wp_unslash($_POST['schema_name'] ?? '')));

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('SEO settings saved.', 'phantom-core') . '</p></div>';
    }
}
