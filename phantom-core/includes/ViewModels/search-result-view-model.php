<?php
declare(strict_types=1);

namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

defined('ABSPATH') || exit;

final class SearchResult_ViewModel implements ViewModelInterface {

  public string $type;
  public int $id;
  public string $title;
  public string $excerpt;
  public string $permalink;
  public string $image_url;
  public string $price;
  public string $date;
  public float $score;

  public static function from_adapter_output(array $data): self {
    $vm = new self();
    $vm->type = (string) ($data['type'] ?? '');
    $vm->id = (int) ($data['id'] ?? 0);
    $vm->title = (string) ($data['title'] ?? '');
    $vm->excerpt = (string) ($data['excerpt'] ?? '');
    $vm->permalink = (string) ($data['permalink'] ?? '');
    $vm->image_url = (string) ($data['image_url'] ?? '');
    $vm->price = (string) ($data['price'] ?? '');
    $vm->date = (string) ($data['date'] ?? '');
    $vm->score = (float) ($data['score'] ?? 0);
    return $vm;
  }

  public function type_label(): string {
    $labels = [
      'product' => 'Product',
      'post'    => 'Article',
      'page'    => 'Page',
    ];
    return $labels[$this->type] ?? ucfirst($this->type);
  }

  public function to_array(): array {
    return [
      'type'      => $this->type,
      'type_label' => $this->type_label(),
      'id'        => $this->id,
      'title'     => $this->title,
      'excerpt'   => $this->excerpt,
      'permalink' => $this->permalink,
      'image_url' => $this->image_url,
      'price'     => $this->price,
      'date'      => $this->date,
      'score'     => $this->score,
    ];
  }
}
