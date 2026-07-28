<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Address_Form extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('address-form') ?: $this->default_template();
  }

  public function render(array $data): string {
    $save_btn = '<button type="submit" class="btn btn-primary">' . esc_html($data['save_text'] ?? 'Save Address') . '</button>';
    $cancel_btn = '<a href="' . esc_url($data['cancel_url']) . '" class="btn btn-outline">Cancel</a>';

    return $this->inject($this->template, [
      'action_url' => esc_url($data['action_url']),
      'name_label' => esc_html($data['name_label'] ?? 'Full Name'),
      'name' => esc_html($data['name'] ?? ''),
      'line1_label' => esc_html($data['line1_label'] ?? 'Address Line 1'),
      'line1' => esc_html($data['line1'] ?? ''),
      'line2_label' => esc_html($data['line2_label'] ?? 'Address Line 2'),
      'line2' => esc_html($data['line2'] ?? ''),
      'city_label' => esc_html($data['city_label'] ?? 'City'),
      'city' => esc_html($data['city'] ?? ''),
      'state_label' => esc_html($data['state_label'] ?? 'State'),
      'state' => esc_html($data['state'] ?? ''),
      'zip_label' => esc_html($data['zip_label'] ?? 'ZIP Code'),
      'zip' => esc_html($data['zip'] ?? ''),
      'country_label' => esc_html($data['country_label'] ?? 'Country'),
      'country_options' => $data['country_options'] ?? '',
      'save_btn' => $save_btn,
      'cancel_btn' => $cancel_btn,
    ]);
  }

  private function default_template(): string {
    return '<div class="address-form">
      <form class="address-form-fields" method="post" action="{{ACTION_URL}}">
        <div class="address-form-row"><label for="address-name">{{NAME_LABEL}}</label><input type="text" id="address-name" name="name" value="{{NAME}}" required></div>
        <div class="address-form-row"><label for="address-line1">{{LINE1_LABEL}}</label><input type="text" id="address-line1" name="line1" value="{{LINE1}}" required></div>
        <div class="address-form-row"><label for="address-line2">{{LINE2_LABEL}}</label><input type="text" id="address-line2" name="line2" value="{{LINE2}}"></div>
        <div class="address-form-row"><label for="address-city">{{CITY_LABEL}}</label><input type="text" id="address-city" name="city" value="{{CITY}}" required></div>
        <div class="address-form-row"><label for="address-state">{{STATE_LABEL}}</label><input type="text" id="address-state" name="state" value="{{STATE}}" required></div>
        <div class="address-form-row"><label for="address-zip">{{ZIP_LABEL}}</label><input type="text" id="address-zip" name="zip" value="{{ZIP}}" required></div>
        <div class="address-form-row"><label for="address-country">{{COUNTRY_LABEL}}</label><select id="address-country" name="country">{{COUNTRY_OPTIONS}}</select></div>
        <div class="address-form-actions">{{SAVE_BTN}}{{CANCEL_BTN}}</div>
      </form>
    </div>';
  }
}
