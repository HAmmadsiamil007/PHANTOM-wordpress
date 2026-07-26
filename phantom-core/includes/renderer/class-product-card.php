<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Product_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('product-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $badge = '';
    if (!empty($data['on_sale'])) {
      $badge = '<span class="product-badge badge-sale">Sale</span>';
    } elseif (!empty($data['is_featured'])) {
      $badge = '<span class="product-badge badge-new">New</span>';
    }

    $rating = '';
    if (!empty($data['rating'])) {
      $full = floor((float) $data['rating']);
      $stars = '';
      for ($i = 0; $i < 5; $i++) {
        $stars .= $i < $full ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
      }
      $rating = '<div class="product-rating">' . $stars . '<span>(' . (int) $data['reviews_count'] . ')</span></div>';
    }

    $categories = '';
    if (!empty($data['categories'])) {
      $cats = array_slice($data['categories'], 0, 2);
      $categories = '<div class="product-tagline">' . esc_html(implode(', ', array_column($cats, 'name'))) . '</div>';
    }

    $price = esc_html($data['price']);
    if (!empty($data['on_sale'])) {
      $price = '<span class="price-sale">' . esc_html($data['sale_price']) . '</span>' .
               '<span class="price-original">' . esc_html($data['regular_price']) . '</span>';
    }

    $atc = '<a href="' . esc_url($data['url']) . '" class="btn btn-sm btn-primary" data-magnetic="0.12">View Details</a>';

    return $this->inject($this->template, [
      'badge' => $badge,
      'url' => esc_url($data['url']),
      'image' => esc_url($data['image']),
      'name' => esc_attr($data['name']),
      'rating' => $rating,
      'categories' => $categories,
      'price' => $price,
      'atc_button' => $atc,
    ]);
  }

  private function default_template(): string {
    return '<div class="product-card" data-tilt data-reveal-item>
      <div class="product-image" data-image-zoom>
        {{BADGE}}
        <a href="{{URL}}"><img loading="lazy" src="{{IMAGE}}" alt="{{NAME}}"></a>
      </div>
      <div class="product-info">
        {{RATING}}
        {{CATEGORIES}}
        <div class="product-price-row">
          <span class="product-price">{{PRICE}}</span>
          {{ATC_BUTTON}}
        </div>
      </div>
    </div>';
  }
}
