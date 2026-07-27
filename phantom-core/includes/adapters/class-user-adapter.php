<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class User_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $user = $input;
    if (is_numeric($user)) {
      $user = get_user_by('id', (int) $user);
    }
    if (is_null($user)) {
      $user = wp_get_current_user();
    }
    if (!$user || !($user instanceof \WP_User)) {
      return $this->empty();
    }

    return [
      'id' => $user->ID,
      'display_name' => $user->display_name,
      'email' => $user->user_email,
      'avatar' => get_avatar_url($user->ID),
      'roles' => array_values($user->roles),
      'registered_date' => $user->user_registered,
      'posts_count' => count_user_posts($user->ID),
    ];
  }

  public function normalize_collection(array $users): array {
    return array_map([$this, 'normalize'], $users);
  }

  private function empty(): array {
    return [
      'id' => 0, 'display_name' => '', 'email' => '', 'avatar' => '',
      'roles' => [], 'registered_date' => '', 'posts_count' => 0,
    ];
  }
}