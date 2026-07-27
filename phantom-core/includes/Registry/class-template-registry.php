<?php
declare(strict_types=1);

namespace PhantomCore\Registry;

defined('ABSPATH') || exit;

class Template_Registry {
    private static ?self $instance = null;
    private array $routes = [];
    private array $patterns = [];
    private bool $defaults_registered = false;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a template route.
     *
     * @param string $slug    URL slug (e.g. 'shop', 'about').
     * @param string $file    Template filename (e.g. 'shop.html').
     * @param string $label   Human-readable label.
     * @param string $category Template category.
     * @param string $pack    Default template pack.
     */
    public function register(
        string $slug,
        string $file = '',
        string $label = '',
        string $category = 'pages',
        string $pack = 'kids'
    ): void {
        $this->routes[$slug] = new Template($slug, $file, $label, $category, $pack);
    }

    /**
     * Register a template with a pattern match (regex).
     * E.g. 'product/{slug}' => 'product-detail.html'.
     *
     * @param string $pattern   Regex pattern (e.g. '/^product\\/(.+)$/').
     * @param string $file      Template filename.
     */
    public function register_pattern(string $pattern, string $file): void {
        $this->patterns[$pattern] = $file;
    }

    /**
     * Resolve a slug to a template filename.
     */
    public function resolve(string $slug): string {
        // Direct match
        if (isset($this->routes[$slug])) {
            return $this->routes[$slug]->file;
        }

        // Strip .html suffix
        $without_ext = preg_replace('/\\.html$/', '', $slug);
        if ($without_ext !== $slug && isset($this->routes[$without_ext])) {
            return $this->routes[$without_ext]->file;
        }

        // Pattern match
        foreach ($this->patterns as $pattern => $file) {
            if (preg_match($pattern, $slug)) {
                return $file;
            }
        }

        return '404.html';
    }

    /**
     * Check if a slug has a registered template.
     */
    public function has(string $slug): bool {
        if (isset($this->routes[$slug])) return true;

        $without_ext = preg_replace('/\\.html$/', '', $slug);
        if ($without_ext !== $slug && isset($this->routes[$without_ext])) return true;

        foreach ($this->patterns as $pattern => $file) {
            if (preg_match($pattern, $slug)) return true;
        }

        return false;
    }

    /**
     * Get a registered template by slug.
     */
    public function get(string $slug): ?Template {
        return $this->routes[$slug] ?? null;
    }

    /**
     * Get all registered templates, optionally filtered by category.
     */
    public function get_all(?string $category = null): array {
        if (null === $category) {
            return $this->routes;
        }
        return array_filter(
            $this->routes,
            fn(Template $t) => $t->category === $category
        );
    }

    /**
     * Deregister a template.
     */
    public function deregister(string $slug): bool {
        if (!isset($this->routes[$slug])) return false;
        unset($this->routes[$slug]);
        return true;
    }

    /**
     * Register the default Phantom Core routes.
     */
    public function register_defaults(): void {
        if ($this->defaults_registered) return;

        $defaults = [
            ''                => ['file' => 'index.html', 'label' => 'Home', 'category' => 'home'],
            'index'           => ['file' => 'index.html', 'label' => 'Home', 'category' => 'home'],
            'shop'            => ['file' => 'shop.html', 'label' => 'Shop', 'category' => 'shop'],
            'product'         => ['file' => 'product-detail.html', 'label' => 'Product Detail', 'category' => 'shop'],
            'product-detail'  => ['file' => 'product-detail.html', 'label' => 'Product Detail', 'category' => 'shop'],
            'about'           => ['file' => 'about.html', 'label' => 'About', 'category' => 'pages'],
            'blog'            => ['file' => 'blog.html', 'label' => 'Blog', 'category' => 'blog'],
            'post'            => ['file' => 'single-blog.html', 'label' => 'Single Post', 'category' => 'blog'],
            'single-blog'     => ['file' => 'single-blog.html', 'label' => 'Single Post', 'category' => 'blog'],
            'contact'         => ['file' => 'contact.html', 'label' => 'Contact', 'category' => 'pages'],
            'cart'            => ['file' => 'cart.html', 'label' => 'Cart', 'category' => 'shop'],
            'checkout'        => ['file' => 'checkout.html', 'label' => 'Checkout', 'category' => 'shop'],
            'my-account'      => ['file' => 'account.html', 'label' => 'My Account', 'category' => 'shop'],
            'account'         => ['file' => 'account.html', 'label' => 'Account', 'category' => 'shop'],
            'coming-soon'     => ['file' => 'coming-soon.html', 'label' => 'Coming Soon', 'category' => 'special'],
            'faq'             => ['file' => 'faq.html', 'label' => 'FAQ', 'category' => 'pages'],
            'team'            => ['file' => 'team.html', 'label' => 'Team', 'category' => 'pages'],
            'testimonials'    => ['file' => 'testimonials.html', 'label' => 'Testimonials', 'category' => 'pages'],
            'join-now'        => ['file' => 'join-now.html', 'label' => 'Join Now', 'category' => 'auth'],
            'login'           => ['file' => 'login.html', 'label' => 'Login', 'category' => 'auth'],
            'register'        => ['file' => 'join-now.html', 'label' => 'Register', 'category' => 'auth'],
            'thank-you'       => ['file' => 'thank-you.html', 'label' => 'Thank You', 'category' => 'special'],
            'wishlist'        => ['file' => 'wishlist.html', 'label' => 'Wishlist', 'category' => 'shop'],
            'privacy-policy'  => ['file' => 'privacy-policy.html', 'label' => 'Privacy Policy', 'category' => 'legal'],
            'term-of-use'     => ['file' => 'term-of-use.html', 'label' => 'Terms of Use', 'category' => 'legal'],
            'cookie-policy'   => ['file' => 'cookie-policy.html', 'label' => 'Cookie Policy', 'category' => 'legal'],
            '404'             => ['file' => '404.html', 'label' => '404', 'category' => 'error', 'is_404' => true],
        ];

        foreach ($defaults as $slug => $cfg) {
            $this->register(
                (string) $slug, // Cast to string because PHP coerces '404' to int 404
                $cfg['file'],
                $cfg['label'] ?? '',
                $cfg['category'] ?? 'pages',
                $cfg['pack'] ?? 'kids'
            );
        }

        // Patterns for dynamic routes
        $this->register_pattern('/^product\/(.+)$/', 'product-detail.html');
        $this->register_pattern('/^blog\/(.+)$/', 'single-blog.html');
        $this->register_pattern('/^category\/(.+)$/', 'shop.html');
        $this->register_pattern('/^tag\/(.+)$/', '404.html');

        $this->defaults_registered = true;
    }

    /**
     * Get all supported template filenames.
     */
    public function get_supported_templates(): array {
        $files = [];
        foreach ($this->routes as $template) {
            $files[$template->file] = true;
        }
        return array_keys($files);
    }

    /**
     * Return count of registered templates.
     */
    public function count(): int {
        return count($this->routes);
    }

    /**
     * Get all registered route patterns.
     *
     * @return array<string, string> Pattern => template file map.
     */
    public function get_patterns(): array {
        $result = [];
        foreach ($this->patterns as $pattern => $file) {
            $result[] = [
                'pattern' => $pattern,
                'template' => $file,
            ];
        }
        return $result;
    }
}
