<?php
declare(strict_types=1);

namespace PhantomCore\Admin;

defined('ABSPATH') || exit;

class AnimationStudioPage {
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

        $animations_enabled = get_option('phantom_animations_enabled', '1');
        $scroll_reveal = get_option('phantom_animations_scroll_reveal', 'fade-up');
        $scroll_duration = get_option('phantom_animations_scroll_duration', '0.6');
        $scroll_delay = get_option('phantom_animations_scroll_delay', '0.1');
        $parallax_enabled = get_option('phantom_animations_parallax', '0');
        $parallax_speed = get_option('phantom_animations_parallax_speed', '0.3');
        $hover_enabled = get_option('phantom_animations_hover', '1');
        $hover_type = get_option('phantom_animations_hover_type', 'lift');
        $page_transition = get_option('phantom_animations_page_transition', 'fade');
        $page_duration = get_option('phantom_animations_page_duration', '0.4');
        $tilt_enabled = get_option('phantom_animations_tilt', '1');
        $gsap_loaded = get_option('phantom_animations_gsap_loaded', '1');
        $three_enabled = get_option('phantom_animations_three', '0');
        $lenis_smooth = get_option('phantom_animations_lenis', '1');
        $swiper_effects = get_option('phantom_animations_swiper_effects', 'slide');
        $lottie_enabled = get_option('phantom_animations_lottie', '0');

        $reveal_options = [
            'fade-up' => __('Fade Up', 'phantom-core'),
            'fade-down' => __('Fade Down', 'phantom-core'),
            'fade-left' => __('Fade Left', 'phantom-core'),
            'fade-right' => __('Fade Right', 'phantom-core'),
            'zoom-in' => __('Zoom In', 'phantom-core'),
            'zoom-out' => __('Zoom Out', 'phantom-core'),
            'flip-up' => __('Flip Up', 'phantom-core'),
            'flip-down' => __('Flip Down', 'phantom-core'),
            'slide-up' => __('Slide Up', 'phantom-core'),
            'slide-down' => __('Slide Down', 'phantom-core'),
            'none' => __('None', 'phantom-core'),
        ];
        $hover_options = [
            'lift' => __('Lift', 'phantom-core'),
            'glow' => __('Glow', 'phantom-core'),
            'scale' => __('Scale', 'phantom-core'),
            'shine' => __('Shine', 'phantom-core'),
            'none' => __('None', 'phantom-core'),
        ];
        $transition_options = [
            'fade' => __('Fade', 'phantom-core'),
            'slide-left' => __('Slide Left', 'phantom-core'),
            'slide-up' => __('Slide Up', 'phantom-core'),
            'zoom' => __('Zoom', 'phantom-core'),
            'none' => __('None', 'phantom-core'),
        ];
        $swiper_options = [
            'slide' => __('Slide', 'phantom-core'),
            'fade' => __('Fade', 'phantom-core'),
            'cube' => __('Cube', 'phantom-core'),
            'coverflow' => __('Coverflow', 'phantom-core'),
            'flip' => __('Flip', 'phantom-core'),
            'creative' => __('Creative', 'phantom-core'),
        ];
        ?>
        <div class="wrap phantom-animation-studio">
            <h1><?php esc_html_e('Animation Studio', 'phantom-core'); ?></h1>
            <p class="description"><?php esc_html_e('Configure motion design, scroll animations, and interactive effects for your site.', 'phantom-core'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('phantom_animation_save', 'phantom_animation_nonce'); ?>
                <input type="hidden" name="action" value="save_animations" />

                <!-- Library Loading -->
                <div class="phantom-section" style="margin-top:20px;">
                    <h2><?php esc_html_e('Animation Libraries', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Load GSAP', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="gsap_loaded" value="1" <?php checked('1', $gsap_loaded); ?> />
                                <?php esc_html_e('Load GreenSock Animation Platform (GSAP) library.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Smooth Scroll (Lenis)', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="lenis_smooth" value="1" <?php checked('1', $lenis_smooth); ?> />
                                <?php esc_html_e('Enable smooth scrolling with Lenis.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable 3D (Three.js)', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="three_enabled" value="1" <?php checked('0', $three_enabled); ?> />
                                <?php esc_html_e('Load Three.js for 3D scenes and WebGL effects.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Lottie', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="lottie_enabled" value="1" <?php checked('0', $lottie_enabled); ?> />
                                <?php esc_html_e('Load Lottie for After Effects-style vector animations.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Master Toggle -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Master Control', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Animations Enabled', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="animations_enabled" value="1" <?php checked('1', $animations_enabled); ?> />
                                <?php esc_html_e('Master toggle. Disable to turn off ALL frontend animations.', 'phantom-core'); ?></label>
                                <?php if ('0' === $animations_enabled): ?>
                                    <p><strong style="color:#dc3232;"><?php esc_html_e('Animations are currently disabled site-wide.', 'phantom-core'); ?></strong></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Scroll Reveal -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Scroll Reveal Animations', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="scroll_reveal"><?php esc_html_e('Default Reveal Effect', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="scroll_reveal" name="scroll_reveal">
                                    <?php foreach ($reveal_options as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($scroll_reveal, $val); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="scroll_duration"><?php esc_html_e('Duration (seconds)', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="number" id="scroll_duration" name="scroll_duration" value="<?php echo esc_attr($scroll_duration); ?>" min="0.1" max="3" step="0.1" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="scroll_delay"><?php esc_html_e('Stagger Delay (seconds)', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="number" id="scroll_delay" name="scroll_delay" value="<?php echo esc_attr($scroll_delay); ?>" min="0" max="1" step="0.05" class="small-text" />
                                <p class="description"><?php esc_html_e('Delay between each animated element in a group.', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Parallax -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Parallax Effects', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Parallax', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="parallax_enabled" value="1" <?php checked('1', $parallax_enabled); ?> />
                                <?php esc_html_e('Enable parallax scrolling effects on hero and background sections.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="parallax_speed"><?php esc_html_e('Parallax Speed', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="number" id="parallax_speed" name="parallax_speed" value="<?php echo esc_attr($parallax_speed); ?>" min="0.05" max="1" step="0.05" class="small-text" />
                                <p class="description"><?php esc_html_e('Lower = slower (more subtle). Higher = faster (more dramatic).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Hover & Tilt -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Hover & Interactive', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Hover Effects', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="hover_enabled" value="1" <?php checked('1', $hover_enabled); ?> />
                                <?php esc_html_e('Enable hover animations on cards, buttons, and interactive elements.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="hover_type"><?php esc_html_e('Hover Effect Type', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="hover_type" name="hover_type">
                                    <?php foreach ($hover_options as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($hover_type, $val); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable 3D Tilt', 'phantom-core'); ?></th>
                            <td>
                                <label><input type="checkbox" name="tilt_enabled" value="1" <?php checked('1', $tilt_enabled); ?> />
                                <?php esc_html_e('Enable 3D tilt effect on product cards and featured images.', 'phantom-core'); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Page Transitions -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Page Transitions', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="page_transition"><?php esc_html_e('Transition Type', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="page_transition" name="page_transition">
                                    <?php foreach ($transition_options as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($page_transition, $val); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Smooth transition effect between SPA page loads.', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="page_duration"><?php esc_html_e('Transition Duration', 'phantom-core'); ?></label></th>
                            <td>
                                <input type="number" id="page_duration" name="page_duration" value="<?php echo esc_attr($page_duration); ?>" min="0.1" max="2" step="0.1" class="small-text" />
                                <span><?php esc_html_e('seconds', 'phantom-core'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Swiper -->
                <div class="phantom-section" style="margin-top:30px;">
                    <h2><?php esc_html_e('Carousel / Slider Effects', 'phantom-core'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="swiper_effects"><?php esc_html_e('Swiper Effect', 'phantom-core'); ?></label></th>
                            <td>
                                <select id="swiper_effects" name="swiper_effects">
                                    <?php foreach ($swiper_options as $val => $label): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($swiper_effects, $val); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e('Default transition effect for Swiper sliders (hero, product galleries, testimonials).', 'phantom-core'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Animation Settings', 'phantom-core'), 'primary', 'save_animations'); ?>
            </form>
        </div>
        <?php
    }

    private function handle_save(): void {
        if ('POST' !== strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) return;
        if (!isset($_POST['phantom_animation_nonce'])) return;
        if (!wp_verify_nonce(wp_unslash($_POST['phantom_animation_nonce']), 'phantom_animation_save')) {
            wp_die(esc_html__('Security check failed.', 'phantom-core'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission.', 'phantom-core'));
        }

        update_option('phantom_animations_enabled', isset($_POST['animations_enabled']) ? '1' : '0');
        update_option('phantom_animations_scroll_reveal', sanitize_key($_POST['scroll_reveal'] ?? 'fade-up'));
        update_option('phantom_animations_scroll_duration', floatval($_POST['scroll_duration'] ?? 0.6));
        update_option('phantom_animations_scroll_delay', floatval($_POST['scroll_delay'] ?? 0.1));
        update_option('phantom_animations_parallax', isset($_POST['parallax_enabled']) ? '1' : '0');
        update_option('phantom_animations_parallax_speed', floatval($_POST['parallax_speed'] ?? 0.3));
        update_option('phantom_animations_hover', isset($_POST['hover_enabled']) ? '1' : '0');
        update_option('phantom_animations_hover_type', sanitize_key($_POST['hover_type'] ?? 'lift'));
        update_option('phantom_animations_page_transition', sanitize_key($_POST['page_transition'] ?? 'fade'));
        update_option('phantom_animations_page_duration', floatval($_POST['page_duration'] ?? 0.4));
        update_option('phantom_animations_tilt', isset($_POST['tilt_enabled']) ? '1' : '0');
        update_option('phantom_animations_gsap_loaded', isset($_POST['gsap_loaded']) ? '1' : '0');
        update_option('phantom_animations_three', isset($_POST['three_enabled']) ? '1' : '0');
        update_option('phantom_animations_lenis', isset($_POST['lenis_smooth']) ? '1' : '0');
        update_option('phantom_animations_swiper_effects', sanitize_key($_POST['swiper_effects'] ?? 'slide'));
        update_option('phantom_animations_lottie', isset($_POST['lottie_enabled']) ? '1' : '0');

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Animation settings saved.', 'phantom-core') . '</p></div>';
    }
}
