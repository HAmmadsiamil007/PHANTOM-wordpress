<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Address_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('address-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $type_class = 'address-type--' . sanitize_html_class($data['type']);
    $edit_btn = '<a href="' . esc_url($data['edit_url']) . '" class="btn btn-outline btn-sm address-edit">Edit</a>';
    $delete_btn = '<button class="btn btn-sm address-delete" data-id="' . (int) $data['id'] . '">Delete</button>';

    return $this->inject($this->template, [
      'type_class' => $type_class,
      'type' => esc_html($data['type']),
      'name' => esc_html($data['name']),
      'line1' => esc_html($data['line1']),
      'line2' => esc_html($data['line2'] ?? ''),
      'city' => esc_html($data['city']),
      'state' => esc_html($data['state']),
      'zip' => esc_html($data['zip']),
      'country' => esc_html($data['country']),
      'phone' => esc_html($data['phone'] ?? ''),
      'edit_btn' => $edit_btn,
      'delete_btn' => $delete_btn,
    ]);
  }

  private function default_template(): string {
    return '<div class="address-card">
      <div class="address-card-type {{TYPE_CLASS}}">{{TYPE}}</div>
      <div class="address-card-body">
        <div class="address-card-name">{{NAME}}</div>
        <div class="address-card-line">{{LINE1}}</div>
        <div class="address-card-line">{{LINE2}}</div>
        <div class="address-card-city">{{CITY}}, {{STATE}} {{ZIP}}</div>
        <div class="address-card-country">{{COUNTRY}}</div>
        <div class="address-card-phone">{{PHONE}}</div>
      </div>
      <div class="address-card-actions">{{EDIT_BTN}}{{DELETE_BTN}}</div>
    </div>';
  }
}
