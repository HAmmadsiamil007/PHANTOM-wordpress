<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

final class Comment_ViewModel implements ViewModelInterface {

  public int $id;
  public int $post_id;
  public string $author_name;
  public string $author_email;
  public string $author_url;
  public string $content;
  public string $date;
  public string $status;
  public int $parent;
  public string $avatar_url;

  public static function from_adapter_output(array $data): self {
    $vm = new self();
    $vm->id = (int) ($data['id'] ?? 0);
    $vm->post_id = (int) ($data['post_id'] ?? 0);
    $vm->author_name = (string) ($data['author_name'] ?? '');
    $vm->author_email = (string) ($data['author_email'] ?? '');
    $vm->author_url = (string) ($data['author_url'] ?? '');
    $vm->content = (string) ($data['content'] ?? '');
    $vm->date = (string) ($data['date'] ?? '');
    $vm->status = (string) ($data['status'] ?? '');
    $vm->parent = (int) ($data['parent'] ?? 0);
    $vm->avatar_url = (string) ($data['avatar_url'] ?? '');
    return $vm;
  }

  public function formatted_date(): string {
    return mysql2date('F j, Y', $this->date);
  }

  public function content_html(): string {
    return wpautop(wp_kses_post($this->content));
  }

  public function is_approved(): bool {
    return $this->status === 'approved';
  }

  public function to_array(): array {
    return [
      'id' => $this->id,
      'post_id' => $this->post_id,
      'author_name' => $this->author_name,
      'author_email' => $this->author_email,
      'author_url' => $this->author_url,
      'content' => $this->content_html(),
      'date' => $this->formatted_date(),
      'status' => $this->status,
      'is_approved' => $this->is_approved(),
      'parent' => $this->parent,
      'avatar_url' => $this->avatar_url,
    ];
  }
}
