<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

final class Tag_ViewModel implements ViewModelInterface {

  public int $id;
  public string $name;
  public string $slug;
  public string $description;
  public int $count;
  public string $link;
  public int $term_group;
  public array $posts;

  public static function from_adapter_output(array $data): self {
    $vm = new self();
    $vm->id = (int) ($data['id'] ?? 0);
    $vm->name = (string) ($data['name'] ?? '');
    $vm->slug = (string) ($data['slug'] ?? '');
    $vm->description = (string) ($data['description'] ?? '');
    $vm->count = (int) ($data['count'] ?? 0);
    $vm->link = (string) ($data['link'] ?? '');
    $vm->term_group = (int) ($data['term_group'] ?? 0);
    $vm->posts = (array) ($data['posts'] ?? []);
    return $vm;
  }

  public function to_array(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'slug' => $this->slug,
      'description' => $this->description,
      'count' => $this->count,
      'link' => $this->link,
      'term_group' => $this->term_group,
      'posts' => $this->posts,
    ];
  }
}
