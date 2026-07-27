<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

/**
 * RequestRouter — handles route analysis and HTTP status code determination.
 *
 * ChatGPT P2: Separated from Render_Engine to keep each class single-responsibility.
 */
class RequestRouter {

    /**
     * Detect if the current request is a Customizer preview.
     */
    public function is_customizer_preview(): bool {
        return isset($_GET['customize_changeset_uuid']);
    }

    /**
     * Detect the route type from a slug.
     */
    public function detect_route_type(string $slug): string {
        $productSlugs = ['product', 'product-detail', 'shop'];
        $blogSlugs    = ['blog', 'post', 'single-blog'];
        $cartSlugs    = ['cart', 'checkout', 'wishlist', 'account'];

        if (in_array($slug, $productSlugs, true)) return 'product';
        if (in_array($slug, $blogSlugs, true))    return 'blog';
        if (in_array($slug, $cartSlugs, true))    return 'cart';
        return 'page';
    }

    /**
     * Set the correct HTTP status header based on the template.
     */
    public function set_status_header(string $template): void {
        $code = $template === '404.html' ? 404 : 200;
        status_header($code);
    }

    /**
     * Check if the template is a 404.
     */
    public function is_404(string $template): bool {
        return $template === '404.html';
    }
}
