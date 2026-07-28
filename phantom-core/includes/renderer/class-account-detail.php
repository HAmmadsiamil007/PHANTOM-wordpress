<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Account_Detail extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('account-detail') ?: $this->default_template();
  }

  public function render(array $data): string {
    $avatar = get_avatar($data['email'], 80) ?: '<div class="account-avatar-placeholder">' . esc_html(substr($data['display_name'], 0, 1)) . '</div>';

    $edit_btn = '<a href="' . esc_url($data['edit_url']) . '" class="btn btn-outline btn-sm">Edit Profile</a>';

    return $this->inject($this->template, [
      'avatar' => $avatar,
      'greeting' => esc_html($data['greeting'] ?? 'Hello, ' . $data['display_name']),
      'email' => esc_html($data['email']),
      'first_name' => esc_html($data['first_name']),
      'last_name' => esc_html($data['last_name']),
      'display_name' => esc_html($data['display_name']),
      'phone' => esc_html($data['phone'] ?? ''),
      'edit_btn' => $edit_btn,
    ]);
  }

  private function default_template(): string {
    return '<div class="account-detail">
      <div class="account-detail-header">
        <div class="account-detail-avatar">{{AVATAR}}</div>
        <div class="account-detail-greeting"><h3>{{GREETING}}</h3><p>{{EMAIL}}</p></div>
      </div>
      <div class="account-detail-info">
        <div class="account-detail-row"><span class="account-detail-label">First Name</span><span class="account-detail-value">{{FIRST_NAME}}</span></div>
        <div class="account-detail-row"><span class="account-detail-label">Last Name</span><span class="account-detail-value">{{LAST_NAME}}</span></div>
        <div class="account-detail-row"><span class="account-detail-label">Display Name</span><span class="account-detail-value">{{DISPLAY_NAME}}</span></div>
        <div class="account-detail-row"><span class="account-detail-label">Phone</span><span class="account-detail-value">{{PHONE}}</span></div>
      </div>
      <div class="account-detail-actions">{{EDIT_BTN}}</div>
    </div>';
  }
}
