<?php
declare(strict_types=1);
namespace PhantomCore\Bridges;
defined('ABSPATH') || exit;

class Mailchimp_Bridge extends Plugin_Bridge {
  protected array $supported_hooks = ['init', 'wp_ajax_nopriv_phantom_mailchimp_subscribe', 'wp_ajax_phantom_mailchimp_subscribe'];

  public function __construct() {
    $this->id = 'mailchimp';
    $this->label = 'Mailchimp for WP';
  }

  public function is_active(): bool {
    return function_exists('mc4wp_get_api') || defined('MC4WP_VERSION');
  }

  public function init(): void {
    if (!$this->is_active()) {
      return;
    }
    add_action('wp_ajax_nopriv_phantom_mailchimp_subscribe', [$this, 'handle_subscribe']);
    add_action('wp_ajax_phantom_mailchimp_subscribe', [$this, 'handle_subscribe']);
    add_filter('phantom_core/newsletter/endpoint', [$this, 'get_endpoint']);
  }

  public function get_endpoint(): string {
    return admin_url('admin-ajax.php?action=phantom_mailchimp_subscribe');
  }

  public function handle_subscribe(): void {
    if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'phantom_newsletter')) {
      wp_send_json_error(['message' => 'Invalid nonce']);
    }

    $email = sanitize_email($_POST['email'] ?? '');
    if (!is_email($email)) {
      wp_send_json_error(['message' => 'Invalid email address']);
    }

    $api = mc4wp_get_api();
    $list_id = get_option('mc4wp_default_list_id', '');

    if (!$list_id) {
      wp_send_json_error(['message' => 'No list configured']);
    }

    try {
      $result = $api->add_list_member($list_id, ['email_address' => $email, 'status' => 'subscribed']);
      if (!empty($result['error'])) {
        wp_send_json_error(['message' => $result['error']]);
      }
      wp_send_json_success(['message' => 'Subscribed successfully']);
    } catch (\Throwable $e) {
      wp_send_json_error(['message' => 'Subscription failed']);
    }
  }
}
