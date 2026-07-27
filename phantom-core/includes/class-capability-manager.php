<?php
declare(strict_types=1);

namespace PhantomCore;

defined('ABSPATH') || exit;

class Capability_Manager {
  private static ?Capability_Manager $instance = null;
  private array $caps = [];

  public static function get_instance(): self {
    if (null === self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public function add_cap(string $role, string $cap): void {
    $role_obj = get_role($role);
    if (null === $role_obj) {
      return;
    }
    $role_obj->add_cap($cap);
    $this->caps[$cap][] = $role;
  }

  public function remove_cap(string $role, string $cap): void {
    $role_obj = get_role($role);
    if (null === $role_obj) {
      return;
    }
    $role_obj->remove_cap($cap);
  }

  public function current_user_can(string $cap): bool {
    return current_user_can($cap);
  }

  public function user_can(int $user_id, string $cap): bool {
    $user = get_user_by('id', $user_id);
    if (false === $user) {
      return false;
    }
    return $user->has_cap($cap);
  }

  public function register_phantom_caps(): void {
    $caps = [
      'manage_phantom'          => 'administrator',
      'edit_phantom_settings'   => 'administrator',
      'edit_phantom_design'     => 'administrator',
      'edit_phantom_assets'     => 'administrator',
      'edit_phantom_templates'  => 'administrator',
      'edit_phantom_animations' => 'administrator',
      'export_phantom_data'     => 'administrator',
      'import_phantom_data'     => 'administrator',
    ];

    foreach ($caps as $cap => $role) {
      $this->add_cap($role, $cap);
    }

    add_action(
      'phantom_core/deactivate',
      function (): void {
        $roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
        $phantom_caps = [
          'manage_phantom',
          'edit_phantom_settings',
          'edit_phantom_design',
          'edit_phantom_assets',
          'edit_phantom_templates',
          'edit_phantom_animations',
          'export_phantom_data',
          'import_phantom_data',
        ];
        foreach ($roles as $role_name) {
          $role_obj = get_role($role_name);
          if (null === $role_obj) {
            continue;
          }
          foreach ($phantom_caps as $cap) {
            $role_obj->remove_cap($cap);
          }
        }
      }
    );
  }
}