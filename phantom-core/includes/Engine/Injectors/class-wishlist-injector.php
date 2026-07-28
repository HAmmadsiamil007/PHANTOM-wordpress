<?php
declare(strict_types=1);

namespace PhantomCore\Engine\Injectors;

use PhantomCore\Feature\Feature_Registry;

defined('ABSPATH') || exit;

class Wishlist_Injector extends Base_Injector {

  public function inject(string $html): string {
    return $html;
  }

  public function inject_wishlist_content(string $html): string {
    $wishlist_enabled = Feature_Registry::get_instance()->enabled('wishlist');
    if (!$wishlist_enabled) {
      return str_replace(
        '[wishlist_content]',
        '<div class="wishlist-page"><p class="text-center">Wishlist feature is currently disabled.</p></div>',
        $html
      );
    }
    return str_replace(
      '[wishlist_content]',
      '<div class="wishlist-page"><p class="text-center">Your wishlist is currently empty.</p><a href="' . esc_url(home_url('/shop')) . '" class="btn btn-primary">Browse Products</a></div>',
      $html
    );
  }
}
