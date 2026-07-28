<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Post_Content extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('post-content') ?: $this->default_template();
  }

  public function render(array $data): string {
    $author = !empty($data['author']) ? '<span class="post-content-author">By ' . esc_html($data['author']) . '</span>' : '';
    $category = !empty($data['category']) ? '<span class="post-content-category">' . esc_html($data['category']) . '</span>' : '';
    $tags = !empty($data['tags']) ? '<div class="post-content-tags">' . wp_kses_post($data['tags']) . '</div>' : '';
    $share = !empty($data['share']) ? '<div class="post-content-share">' . wp_kses_post($data['share']) . '</div>' : '';

    return $this->inject($this->template, [
      'title' => esc_html($data['title']),
      'author' => $author,
      'date' => esc_html($data['date']),
      'category' => $category,
      'image' => esc_url($data['image']),
      'content' => wp_kses_post($data['content']),
      'tags' => $tags,
      'share' => $share,
    ]);
  }

  private function default_template(): string {
    return '<article class="post-content">
      <header class="post-content-header">
        <h1 class="post-content-title">{{TITLE}}</h1>
        <div class="post-content-meta">{{AUTHOR}}{{DATE}}{{CATEGORY}}</div>
        <div class="post-content-featured"><img loading="lazy" src="{{IMAGE}}" alt="{{TITLE}}"></div>
      </header>
      <div class="post-content-body">{{CONTENT}}</div>
      <footer class="post-content-footer">{{TAGS}}{{SHARE}}</footer>
    </article>';
  }
}
