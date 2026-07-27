<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

/**
 * User_ViewModel transforms user data into a typed view-model object.
 * Bridge between adapters and template renderers.
 */
final class User_ViewModel implements ViewModelInterface {
	public int $id;
	public string $display_name;
	public string $email;
	public string $avatar;
	public array $roles;
	public string $registered_date;
	public int $posts_count;
	public bool $is_logged_in;

	/**
	 * Create from raw user data array.
	 */
	public static function from_adapter_output(array $data): self {
		$vm = new self();
		$vm->id = (int) ($data['id'] ?? 0);
		$vm->display_name = (string) ($data['display_name'] ?? '');
		$vm->email = (string) ($data['email'] ?? '');
		$vm->avatar = (string) ($data['avatar'] ?? '');
		$vm->roles = (array) ($data['roles'] ?? []);
		$vm->registered_date = (string) ($data['registered_date'] ?? '');
		$vm->posts_count = (int) ($data['posts_count'] ?? 0);
		$vm->is_logged_in = (bool) ($data['is_logged_in'] ?? false);
		return $vm;
	}

	/**
	 * Check if user has a specific role.
	 */
	public function has_role(string $role): bool {
		return in_array($role, $this->roles, true);
	}

	/**
	 * Convert to array for template rendering.
	 */
	public function to_array(): array {
		return [
			'id' => $this->id,
			'display_name' => $this->display_name,
			'email' => $this->email,
			'avatar' => $this->avatar,
			'roles' => $this->roles,
			'registered_date' => $this->registered_date,
			'posts_count' => $this->posts_count,
			'is_logged_in' => $this->is_logged_in,
			'has_role' => $this->roles,
		];
	}
}
