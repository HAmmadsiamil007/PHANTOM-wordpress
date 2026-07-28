<?php
declare(strict_types=1);

namespace PhantomCore\Setup;

defined('ABSPATH') || exit;

class Activation_Wizard {

  private const AJAX_ACTION = 'phantom_wizard_step';
  private const NONCE_KEY = 'phantom_wizard_nonce';
  private const OPTION_KEY = 'phantom_wizard_completed';

  public function __construct() {
    add_action('admin_menu', [$this, 'add_wizard_page'], 99);
    add_action('wp_ajax_' . self::AJAX_ACTION, [$this, 'handle_ajax']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
  }

  public static function is_completed(): bool {
    return (bool) get_option(self::OPTION_KEY, false);
  }

  public static function mark_completed(): void {
    update_option(self::OPTION_KEY, true);
  }

  public static function reset(): void {
    delete_option(self::OPTION_KEY);
  }

  public function add_wizard_page(): void {
    if (self::is_completed()) return;
    add_theme_page(
      'Setup Wizard',
      'Setup Wizard',
      'manage_options',
      'phantom-setup-wizard',
      [$this, 'render_page']
    );
  }

  public function render_page(): void {
    ?>
    <div class="wrap phantom-wizard-wrap">
      <div id="phantom-wizard-root">
        <div class="wizard-loading">Loading Setup Wizard...</div>
      </div>
    </div>
    <?php
  }

  public function enqueue_assets(string $hook): void {
    if ($hook !== 'appearance_page_phantom-setup-wizard') return;
    wp_enqueue_style('phantom-wizard', PHANTOM_CORE_URL . 'admin/css/wizard.css', [], PHANTOM_CORE_VERSION);
    wp_enqueue_script('phantom-wizard', PHANTOM_CORE_URL . 'admin/js/wizard.js', ['jquery'], PHANTOM_CORE_VERSION, true);
    wp_localize_script('phantom-wizard', 'phantomWizard', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce(self::NONCE_KEY),
      'completed' => self::is_completed(),
      'steps' => $this->get_steps_config(),
    ]);
  }

  public function handle_ajax(): void {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', self::NONCE_KEY)) {
      wp_send_json_error(['message' => 'Invalid nonce']);
    }
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized']);
    }
    $step = sanitize_text_field($_POST['step'] ?? '');
    $data = isset($_POST['data']) ? (array) $_POST['data'] : [];
    $result = $this->execute_step($step, $data);
    if (is_wp_error($result)) {
      wp_send_json_error(['message' => $result->get_error_message()]);
    }
    wp_send_json_success($result);
  }

  public function get_steps_config(): array {
    return [
      'welcome' => [
        'title' => 'Welcome',
        'description' => 'Set up your Phantom Core site in minutes.',
      ],
      'pack' => [
        'title' => 'Choose Template Pack',
        'description' => 'Select a visual style for your site.',
      ],
      'content' => [
        'title' => 'Demo Content',
        'description' => 'Import sample pages, products, and posts.',
      ],
      'complete' => [
        'title' => 'Done!',
        'description' => 'Your site is ready.',
      ],
    ];
  }

  private function execute_step(string $step, array $data) {
    switch ($step) {
      case 'get_packs':
        $loader = new \PhantomCore\Engine\Template_Loader();
        return ['packs' => $loader->get_packs()];

      case 'set_pack':
        $pack = sanitize_text_field($data['pack'] ?? '');
        if (!$pack) return new \WP_Error('invalid', 'No pack selected');
        update_option('phantom_template_pack', $pack);
        return ['pack' => $pack];

      case 'generate_content':
        $generator = new Demo_Content_Generator();
        $result = $generator->generate_all();
        return $result;

      case 'complete':
        self::mark_completed();
        do_action('phantom_wizard_completed');
        return ['redirect' => admin_url()];

      default:
        return new \WP_Error('unknown', "Unknown step: $step");
    }
  }
}
