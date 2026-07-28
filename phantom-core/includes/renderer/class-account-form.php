<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Account_Form extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('account-form') ?: $this->default_template();
  }

  public function render(array $data): string {
    $save_btn = '<button type="submit" class="btn btn-primary">' . esc_html($data['save_text'] ?? 'Save Changes') . '</button>';
    $cancel_btn = '<a href="' . esc_url($data['cancel_url']) . '" class="btn btn-outline">Cancel</a>';

    return $this->inject($this->template, [
      'action_url' => esc_url($data['action_url']),
      'first_name_label' => esc_html($data['first_name_label'] ?? 'First Name'),
      'first_name' => esc_html($data['first_name'] ?? ''),
      'last_name_label' => esc_html($data['last_name_label'] ?? 'Last Name'),
      'last_name' => esc_html($data['last_name'] ?? ''),
      'email_label' => esc_html($data['email_label'] ?? 'Email'),
      'email' => esc_html($data['email'] ?? ''),
      'phone_label' => esc_html($data['phone_label'] ?? 'Phone'),
      'phone' => esc_html($data['phone'] ?? ''),
      'save_btn' => $save_btn,
      'cancel_btn' => $cancel_btn,
    ]);
  }

  private function default_template(): string {
    return '<div class="account-form">
      <form class="account-form-fields" method="post" action="{{ACTION_URL}}">
        <div class="account-form-row"><label for="account-first-name">{{FIRST_NAME_LABEL}}</label><input type="text" id="account-first-name" name="first_name" value="{{FIRST_NAME}}" required></div>
        <div class="account-form-row"><label for="account-last-name">{{LAST_NAME_LABEL}}</label><input type="text" id="account-last-name" name="last_name" value="{{LAST_NAME}}" required></div>
        <div class="account-form-row"><label for="account-email">{{EMAIL_LABEL}}</label><input type="email" id="account-email" name="email" value="{{EMAIL}}" required></div>
        <div class="account-form-row"><label for="account-phone">{{PHONE_LABEL}}</label><input type="tel" id="account-phone" name="phone" value="{{PHONE}}"></div>
        <div class="account-form-actions">{{SAVE_BTN}}{{CANCEL_BTN}}</div>
      </form>
    </div>';
  }
}
