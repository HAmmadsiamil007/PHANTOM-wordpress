<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

/**
 * ResponseBuilder — handles final output assembly, hook execution, and
 * Customizer-specific modifications.
 *
 * ChatGPT P2: Separated from Render_Engine to keep each class single-responsibility.
 */
class ResponseBuilder {

    /**
     * Add Customizer head/footer scripts if in preview mode.
     */
    public function add_customizer_scripts(string $html, string $slug): string {
        $html = preg_replace(
            '/<script[^>]*src="[^"]*jquery(?:-\d[\w.]*)?(?:\.min)?\.js[^"]*"[^>]*><\/script>/i',
            '',
            $html
        );
        ob_start();
        wp_head();
        $html = str_replace('</head>', ob_get_clean() . '</head>', $html);
        ob_start();
        wp_footer();
        $html = str_replace('</body>', ob_get_clean() . '</body>', $html);
        return $html;
    }

    /**
     * Execute the phantom_before_output action hook and prepend output to HTML.
     */
    public function apply_before_output_hook(string $template, string $slug, string $html): string {
        ob_start();
        do_action('phantom_before_output', $template, $slug);
        return ob_get_clean() . $html;
    }

    /**
     * Assemble the complete response.
     */
    public function build(string $html, string $template, string $slug, bool $is_customizer, RequestRouter $router): string {
        $router->set_status_header($template);

        if ($is_customizer && !$router->is_404($template)) {
            $html = $this->add_customizer_scripts($html, $slug);
        }

        $html = $this->apply_before_output_hook($template, $slug, $html);
        return $html;
    }
}
