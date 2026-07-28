<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Comment_Adapter implements AdapterInterface {

  public function normalize($input = null): array {
    $comment = $input;
    if (is_numeric($comment)) {
      $comment = get_comment((int) $comment);
    }
    if (!$comment || !($comment instanceof \WP_Comment)) {
      return $this->empty();
    }

    return [
      'id'           => $comment->comment_ID,
      'post_id'      => (int) $comment->comment_post_ID,
      'author_name'  => $comment->comment_author,
      'author_email' => $comment->comment_author_email,
      'author_url'   => $comment->comment_author_url,
      'content'      => $comment->comment_content,
      'date'         => $comment->comment_date,
      'status'       => wp_get_comment_status($comment->comment_ID),
      'parent'       => (int) $comment->comment_parent,
      'avatar_url'   => get_avatar_url($comment->comment_author_email),
    ];
  }

  public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
  }

  private function empty(): array {
    return [
      'id' => 0, 'post_id' => 0, 'author_name' => '', 'author_email' => '',
      'author_url' => '', 'content' => '', 'date' => '', 'status' => '',
      'parent' => 0, 'avatar_url' => '',
    ];
  }
}
