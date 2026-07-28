<?php
declare(strict_types=1);

namespace PhantomCore\Engine\Injectors;

use PhantomCore\Adapters\Post_Adapter;
use PhantomCore\Adapters\SearchResult_Adapter;
use PhantomCore\ViewModels\Post_ViewModel;

defined('ABSPATH') || exit;

class Content_Injector extends Base_Injector {

  public function inject(string $html): string {
    return $html;
  }

  public function inject_blog_content(string $html): string {
    global $wp_query;

    $blog_card = $this->get_renderer('blog_card');
    if (!$blog_card) {
      return $html;
    }

    $paged = absint($_GET['page'] ?? 1);
    $query = new \WP_Query([
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'posts_per_page' => 10,
      'paged'          => $paged,
    ]);

    if (!$query->have_posts()) {
      $empty = '<div class="blog-empty"><p>No posts found.</p></div>';
      return $this->replace_inner_by_component($html, 'blog-grid', $empty);
    }

    $post_adapter = new Post_Adapter();
    $cards = '';
    while ($query->have_posts()) {
      $query->the_post();
      $post = get_post();

      $raw = $post_adapter->normalize($post);
      $vm = Post_ViewModel::from_adapter_output($raw);
      $normalized = $vm->to_array();

      $cat_name = '';
      $cats = get_the_category();
      if (!empty($cats)) {
        $cat_name = $cats[0]->name;
      }

      $data = [
        'title'       => $normalized['title'],
        'url'         => $normalized['url'],
        'image'       => $normalized['image'],
        'excerpt'     => $normalized['excerpt'],
        'date'        => $normalized['date'],
        'author'      => $normalized['author'],
        'author_url'  => get_author_posts_url($post->post_author),
        'category'    => $cat_name,
        'reading_time'=> $this->estimate_reading_time($post->post_content),
      ];

      $cards .= $blog_card->render($data);
    }
    wp_reset_postdata();

    $html = $this->replace_inner_by_component($html, 'blog-grid', $cards);

    if ($query->max_num_pages > 1) {
      $pagination = '<div class="blog-pagination">';
      for ($i = 1; $i <= $query->max_num_pages; $i++) {
        $pagination .= sprintf(
          '<a href="%s" class="pagination-page%s">%d</a>',
          esc_url(add_query_arg('page', $i)),
          $i === $paged ? ' active' : '',
          $i
        );
      }
      $pagination .= '</div>';

      $html = preg_replace(
        '/<div class="blog-pagination">.*?<\/div>\s*/s',
        $pagination,
        $html,
        1
      );
    }

    return $html;
  }

  public function inject_post_content(string $html): string {
    $post_id = $this->engine->get_resolved_post_id();

    if (!$post_id) {
      $slug = sanitize_title(wp_unslash($_GET['slug'] ?? ''));
      if ($slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');
        $post_id = $post ? $post->ID : 0;
      }
    }

    if (!$post_id) {
      return str_replace(
        ['[post_content]', '[post_title]', '[post_date]'],
        ['<p class="text-center">Post not found.</p>', '', ''],
        $html
      );
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post') {
      return preg_replace(
        '/<div class="post-content"[^>]*>.*?<\/div>\s*<\/section>/s',
        '<div class="post-content"><p class="text-center">Post not found.</p></div></section>',
        $html,
        1
      );
    }

    $post_renderer = $this->get_renderer('post_content');
    if (!$post_renderer) {
      return $html;
    }

    setup_postdata($post);

    $post_adapter = new Post_Adapter();
    $vm = Post_ViewModel::from_adapter_output($post_adapter->normalize($post));
    $normalized = $vm->to_array();

    $cats = get_the_category($post_id);
    $post_tags = get_the_tags($post_id);

    $tags_html = '';
    if ($post_tags) {
      foreach ($post_tags as $tag) {
        $tags_html .= '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="post-tag">' . esc_html($tag->name) . '</a>';
      }
    }

    $raw_content = apply_filters('the_content', $post->post_content);
    $raw_content = apply_filters('phantom_core/render/post_content', $raw_content, $post);
    $raw_content = apply_filters('phantom_core/render/page_content', $raw_content, $post_id);

    $data = [
      'title'    => $normalized['title'],
      'author'   => $normalized['author'],
      'date'     => $normalized['date'],
      'category' => !empty($normalized['categories']) ? $normalized['categories'][0]['name'] : '',
      'image'    => $normalized['image'],
      'content'  => $raw_content,
      'tags'     => $tags_html,
      'share'    => '',
    ];

    $content_html = $post_renderer->render($data);
    wp_reset_postdata();

    return str_replace(
      ['[post_content]', '[post_title]', '[post_date]'],
      [$content_html, esc_html($post->post_title), get_the_date('F j, Y', $post)],
      $html
    );
  }

  public function inject_search_content(string $html): string {
    $search_term = sanitize_text_field(wp_unslash($_GET['s'] ?? $_GET['search'] ?? ''));

    if (empty($search_term)) {
      $empty = '<div class="search-empty"><p>Enter a search term to find results.</p></div>';
      return str_replace(
        '[search_query]',
        '',
        $this->replace_inner_by_component($html, 'search-grid', $empty)
      );
    }

    $search_card = $this->get_renderer('search_card');
    if (!$search_card) {
      return str_replace('[search_query]', esc_html($search_term), $html);
    }

    $paged = absint($_GET['page'] ?? 1);
    $query = new \WP_Query([
      's'              => $search_term,
      'post_type'      => ['post', 'page', 'product'],
      'post_status'    => 'publish',
      'posts_per_page' => 10,
      'paged'          => $paged,
    ]);

    $html = str_replace('[search_query]', esc_html($search_term), $html);

    if (!$query->have_posts()) {
      $empty = '<div class="search-empty"><p>No results found for "' . esc_html($search_term) . '".</p></div>';
      return $this->replace_inner_by_component($html, 'search-grid', $empty);
    }

    $search_adapter = new SearchResult_Adapter();
    $cards = '';
    while ($query->have_posts()) {
      $query->the_post();
      $post = get_post();
      $normalized = $search_adapter->normalize($post);

      $data = [
        'title'      => $normalized['title'],
        'url'        => $normalized['permalink'],
        'excerpt'    => $normalized['excerpt'],
        'type'       => 'product' === $normalized['type'] ? 'Product' : ('page' === $normalized['type'] ? 'Page' : 'Article'),
        'date'       => $normalized['date'],
        'image'      => $normalized['image_url'],
      ];

      $cards .= $search_card->render($data);
    }
    wp_reset_postdata();

    $html = $this->replace_inner_by_component($html, 'search-grid', $cards);

    return $html;
  }

  private function estimate_reading_time(string $content): string {
    $words = str_word_count(wp_strip_all_tags($content));
    $minutes = max(1, (int) ceil($words / 200));
    return $minutes . ' min read';
  }
}
