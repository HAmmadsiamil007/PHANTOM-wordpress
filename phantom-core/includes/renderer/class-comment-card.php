<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Comment_Card extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('comment-card') ?: $this->default_template();
  }

  public function render(array $data): string {
    $avatar = get_avatar($data['author_email'], 48) ?: '<div class="comment-avatar-placeholder">' . esc_html(substr($data['author_name'], 0, 1)) . '</div>';
    $reply_btn = !empty($data['reply_url']) ? '<a href="' . esc_url($data['reply_url']) . '" class="comment-reply-btn">Reply</a>' : '';

    return $this->inject($this->template, [
      'id' => (int) $data['id'],
      'avatar' => $avatar,
      'author' => esc_html($data['author_name']),
      'date' => esc_html($data['date']),
      'content' => wp_kses_post($data['content']),
      'reply_btn' => $reply_btn,
      'replies' => $data['replies'] ?? '',
    ]);
  }

  private function default_template(): string {
    return '<div class="comment-card" id="comment-{{ID}}">
      <div class="comment-card-avatar">{{AVATAR}}</div>
      <div class="comment-card-body">
        <div class="comment-card-header"><span class="comment-card-author">{{AUTHOR}}</span><span class="comment-card-date">{{DATE}}</span></div>
        <div class="comment-card-content">{{CONTENT}}</div>
        <div class="comment-card-actions">{{REPLY_BTN}}</div>
        <div class="comment-card-replies">{{REPLIES}}</div>
      </div>
    </div>';
  }
}
