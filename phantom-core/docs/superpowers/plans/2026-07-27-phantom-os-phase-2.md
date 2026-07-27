# Phase 2 — Three-Engine Split Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Goal:** Split monolithic Render_Engine into Data_Engine, View_Engine, Asset_Engine with thin coordinator.

**Architecture:** Data_Engine holds resolved state (product/post/category IDs). View_Engine wraps Template_Loader + HTML pipeline. Asset_Engine replaces Asset_Loader + Security_Headers. Render_Engine becomes < 100 line coordinator.

**Tech Stack:** PHP 7.4+, WordPress, PHPUnit 9.6

## Global Constraints
- All classes in `PhantomCore\Engine` namespace
- All files pass `php -l` syntax check
- All 40+ existing PHPUnit tests pass
- WooCommerce_Injector untouched (Phase 5)
- Backward-compatible public API on Render_Engine for WooCommerce_Injector

---

### Task 1: Data_Engine

**Files:**
- Create: `includes/Engine/Data_Engine.php`
- Test: `tests/Data_Engine_Test.php`

**Interfaces:**
- Consumes: nothing
- Produces: `PhantomCore\Engine\Data_Engine` with getters/setters

- [ ] **Step 1: Write the failing Data_Engine test**

```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Data_Engine;

class Data_Engine_Test extends TestCase {
    private Data_Engine $engine;
    protected function setUp(): void { $this->engine = new Data_Engine(); }

    public function test_defaults_are_null(): void {
        $this->assertNull($this->engine->get_resolved_product_id());
        $this->assertNull($this->engine->get_resolved_post_id());
        $this->assertNull($this->engine->get_category_slug());
    }

    public function test_set_and_get_product_id(): void {
        $result = $this->engine->with_product_id(42);
        $this->assertSame($result, $this->engine);
        $this->assertSame(42, $this->engine->get_resolved_product_id());
    }

    public function test_set_and_get_post_id(): void {
        $this->engine->with_post_id(99);
        $this->assertSame(99, $this->engine->get_resolved_post_id());
    }

    public function test_set_and_get_category_slug(): void {
        $this->engine->with_category('shoes');
        $this->assertSame('shoes', $this->engine->get_category_slug());
    }

    public function test_chaining(): void {
        $this->engine->with_product_id(1)->with_post_id(2)->with_category('test');
        $this->assertSame(1, $this->engine->get_resolved_product_id());
        $this->assertSame(2, $this->engine->get_resolved_post_id());
        $this->assertSame('test', $this->engine->get_category_slug());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter Data_Engine tests/Data_Engine_Test.php`
Expected: FAIL with "Class 'PhantomCore\Engine\Data_Engine' not found"

- [ ] **Step 3: Write Data_Engine implementation**

`includes/Engine/Data_Engine.php`:
```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Data_Engine {
    private ?int $resolved_product_id = null;
    private ?int $resolved_post_id = null;
    private ?string $category_slug = null;

    public function with_product_id(int $id): self {
        $this->resolved_product_id = $id;
        return $this;
    }

    public function with_post_id(int $id): self {
        $this->resolved_post_id = $id;
        return $this;
    }

    public function with_category(string $slug): self {
        $this->category_slug = $slug;
        return $this;
    }

    public function get_resolved_product_id(): ?int {
        return $this->resolved_product_id;
    }

    public function get_resolved_post_id(): ?int {
        return $this->resolved_post_id;
    }

    public function get_category_slug(): ?string {
        return $this->category_slug;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter Data_Engine tests/Data_Engine_Test.php`
Expected: PASS (5 assertions)

- [ ] **Step 5: Commit**
```bash
git add includes/Engine/Data_Engine.php tests/Data_Engine_Test.php
git commit -m "feat: add Data_Engine for resolved state"
```

---

### Task 2: Asset_Engine

**Files:**
- Create: `includes/Engine/Asset_Engine.php`
- Delete: `includes/Engine/Asset_Loader.php`
- Modify: `includes/Engine/Container_Config.php` (change Asset_Loader to Asset_Engine)
- Test: `tests/Asset_Engine_Test.php`

**Interfaces:**
- Consumes: Security_Headers (constructor dep)
- Produces: `Asset_Engine::inject_all(string, string, bool): string`, `Asset_Engine::send_security_headers(bool): void`

- [ ] **Step 1: Write the failing Asset_Engine test**

`tests/Asset_Engine_Test.php`:
```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\Asset_Engine;
use PhantomCore\Engine\Security_Headers;

class Asset_Engine_Test extends TestCase {
    public function test_inject_all_returns_modified_html(): void {
        $engine = new Asset_Engine(new Security_Headers());
        $result = $engine->inject_all('<html><head></head><body></body></html>', 'test', false);
        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('</html>', $result);
    }

    public function test_inject_all_adds_js_script(): void {
        $engine = new Asset_Engine(new Security_Headers());
        $result = $engine->inject_all('<html><head></head><body></body></html>', 'test', false);
        $this->assertStringContainsString('<script', $result);
    }

    public function test_send_security_headers_does_not_throw(): void {
        $engine = new Asset_Engine(new Security_Headers());
        $engine->send_security_headers(false);
        $this->assertTrue(true);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter Asset_Engine tests/Asset_Engine_Test.php`
Expected: FAIL with "Class 'PhantomCore\Engine\Asset_Engine' not found"

- [ ] **Step 3: Write Asset_Engine implementation**

Asset_Engine copies all methods from Asset_Loader verbatim, adds Security_Headers delegation:

`includes/Engine/Asset_Engine.php`:
```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Asset_Engine {
    private Security_Headers $security;

    public function __construct(Security_Headers $security) {
        $this->security = $security;
    }

    public function inject_all(string $html, string $slug, bool $is_customizer_preview): string {
        $html = $this->inject_css_by_route($html, $slug);
        $html = $this->inject_images($html);
        $html = $this->inject_google_fonts($html);
        $html = $this->inject_minified_js($html);
        $html = $this->inject_cdn_fallbacks($html);
        $html = $this->inject_lazy_loading($html);
        $html = $this->inject_woo_scripts($html);
        $html = $this->inject_a11y($html);
        $html = $this->inject_scroll_reveal($html);
        return $html;
    }

    public function send_security_headers(bool $is_customizer_preview = false): void {
        $this->security->send($is_customizer_preview);
    }

    // All private methods below are copied verbatim from Asset_Loader

    private function inject_css_by_route(string $html, string $slug): string {
        $theme_css = PHANTOM_CORE_URL . '../phantom-theme/assets/css/';
        $v = '?v=' . PHANTOM_CORE_VERSION;
        if (in_array($slug, ['blog', 'post', 'single-blog'], true)) {
            $html = str_replace('</head>', '<link rel="stylesheet" href="' . esc_url($theme_css . 'blog.css' . $v) . '" media="all">' . "\n" . '</head>', $html);
        }
        if (in_array($slug, ['shop', 'product', 'product-detail', 'cart', 'checkout', 'wishlist', 'account', 'my-account'], true)) {
            $html = str_replace('</head>', '<link rel="stylesheet" href="' . esc_url($theme_css . 'shop.css' . $v) . '" media="all">' . "\n" . '<link rel="stylesheet" href="' . esc_url($theme_css . 'woocommerce.css' . $v) . '" media="all">' . "\n" . '</head>', $html);
        }
        return $html;
    }

    private function inject_images(string $html): string {
        $logo = get_option('phantom_custom_logo', '');
        if ($logo) {
            $html = preg_replace('/<img[^>]*class="[^"]*site-logo[^"]*"[^>]*src="[^"]*"[^>]*>/i', '<img src="' . esc_url($logo) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="site-logo">', $html);
        }
        $favicon = get_option('phantom_custom_favicon', '');
        if ($favicon) {
            $html = preg_replace('/<link[^>]*rel="(icon|shortcut icon)"[^>]*>/i', '<link rel="icon" href="' . esc_url($favicon) . '" sizes="32x32">', $html);
        }
        return $html;
    }

    private function inject_google_fonts(string $html): string {
        $fonts = [];
        $fonts[] = get_option('phantom_typography_body_font', 'Archivo');
        $heading_font = get_option('phantom_typography_heading_font', '');
        if ($heading_font) $fonts[] = $heading_font;
        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h) {
            $font = get_option('phantom_typography_' . $h . '_font', '');
            if ($font) $fonts[] = $font;
        }
        $fonts = array_unique(array_filter($fonts));
        if (empty($fonts)) return $html;
        $url = \PhantomCore\Fonts::instance()->get_enqueue_url($fonts);
        $link = '<link rel="stylesheet" id="phantom-google-fonts" href="' . esc_url($url) . '" media="all" />';
        return str_replace('</head>', $link . "\n" . '</head>', $html);
    }

    private function inject_minified_js(string $html): string {
        $js_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-core.min.js';
        if (!file_exists(PHANTOM_CORE_PATH . 'frontend/assets/js/phantom-core.min.js')) {
            $js_url = PHANTOM_CORE_URL . 'frontend/assets/js/phantom-data.js';
        }
        $html = str_replace('</body>', '<script src="' . esc_url($js_url) . '?v=' . PHANTOM_CORE_VERSION . '" id="phantom-core-js"></script>' . "\n" . '</body>', $html);
        return $html;
    }

    private function inject_cdn_fallbacks(string $html): string {
        return str_replace('</body>',
            '<script>window.jQuery||document.write(\'<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"><\/script>\')</script>' . "\n" .
            '<script>window.bootstrap||document.write(\'<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css"><\/link>\')</script>' . "\n" .
            '</body>', $html);
    }

    private function inject_lazy_loading(string $html): string {
        return str_replace('</body>',
            '<script id="phantom-lazy-load">document.addEventListener("DOMContentLoaded",function(){var obs=new IntersectionObserver(function(e){e.forEach(function(e){if(e.isIntersecting){var i=e.target;i.dataset.src&&(i.src=i.dataset.src,delete i.dataset.src);i.dataset.srcset&&(i.srcset=i.dataset.srcset,delete i.dataset.srcset);obs.unobserve(i)}})});document.querySelectorAll("img[data-src]").forEach(function(i){obs.observe(i)})});</script>' . "\n" .
            '</body>', $html);
    }

    private function inject_woo_scripts(string $html): string {
        if (!class_exists('WooCommerce')) return $html;
        $nonce = wp_create_nonce('wc_store_api');
        return str_replace('</head>', '<meta name="woocommerce-store-api-nonce" content="' . esc_attr($nonce) . '" />' . "\n" . '</head>', $html);
    }

    private function inject_a11y(string $html): string {
        $css = '<style id="phantom-a11y-css">'
            . '.skip-link{position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;z-index:100000}'
            . '.skip-link:focus{position:fixed;left:16px;top:16px;width:auto;height:auto;padding:12px 24px;background:#7635d5;color:#fff;font-size:16px;text-decoration:none;border-radius:4px;box-shadow:0 0 0 4px rgba(118,53,213,0.3);z-index:100000;outline:2px solid #fff;outline-offset:2px}'
            . ':focus-visible{outline:2px solid #7635d5;outline-offset:2px}'
            . '</style>';
        $html = str_replace('</head>', $css . '</head>', $html);
        $a11y_css_url = PHANTOM_CORE_URL . 'frontend/assets/css/a11y.css?v=' . PHANTOM_CORE_VERSION;
        $html = str_replace('</head>', '<link rel="stylesheet" id="phantom-a11y-css" href="' . esc_url($a11y_css_url) . '" media="all" />' . "\n" . '</head>', $html);
        $js = '<script id="phantom-a11y-js">(function(){function setAriaCurrent(){var path=window.location.pathname;document.querySelectorAll(".nav-link,.mobile-nav-link,.footer-menu a,.primary-menu a").forEach(function(l){var h=l.getAttribute("href");if(h&&(h===path||h===path.replace(/\/$/,"")||(h.indexOf("#")!==-1&&path===h.split("#")[0]))){l.setAttribute("aria-current","page")}else if(l.getAttribute("aria-current")==="page"){l.removeAttribute("aria-current")}})}document.addEventListener("DOMContentLoaded",function(){setAriaCurrent();var mainEl=document.getElementById("main-content")||document.querySelector("main");if(mainEl&&!mainEl.hasAttribute("tabindex")){mainEl.setAttribute("tabindex","-1")}});var _pdObs=new MutationObserver(function(){setAriaCurrent()});_pdObs.observe(document.body,{childList:true,subtree:true});})();</script>';
        return str_replace('</body>', $js . '</body>', $html);
    }

    private function inject_scroll_reveal(string $html): string {
        $js = '<script id="phantom-scroll-reveal">(function(){if(window._phantomRevealInited)return;window._phantomRevealInited=true;var style=document.createElement("style");style.textContent=".pr-reveal{opacity:0;transform:translateY(24px);transition:opacity .6s ease,transform .6s ease}.pr-reveal.pr-visible{opacity:1;transform:translateY(0)}";document.head.appendChild(style);function observe(){var els=document.querySelectorAll(".pr-reveal:not(.pr-visible)");if(!els.length&&window.IntersectionObserver){els=document.querySelectorAll("[data-reveal]:not(.pr-visible),[data-aos]:not(.pr-visible)");els.forEach(function(e){e.classList.add("pr-reveal")})}var obs=new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add("pr-visible");obs.unobserve(e.target)}})},{threshold:.1});els.forEach(function(e){obs.observe(e)})}document.addEventListener("DOMContentLoaded",observe);})();</script>';
        return str_replace('</body>', $js . '</body>', $html);
    }
}
```

- [ ] **Step 4: Update Container_Config to use Asset_Engine**

Edit `Container_Config.php`, change `Asset_Loader::class` to `Asset_Engine::class`:

```php
$c->get(Asset_Engine::class),
```

And add Asset_Engine registration before Render_Engine:

```php
// 1.5 Security_Headers
$container->singleton(Security_Headers::class, function ($c) {
    return new Security_Headers();
});

// 1.6 Asset_Engine — singleton
$container->singleton(Asset_Engine::class, function ($c) {
    return new Asset_Engine($c->get(Security_Headers::class));
});
```

- [ ] **Step 5: Run tests to verify Asset_Engine passes**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter Asset_Engine`
Expected: PASS

- [ ] **Step 6: Delete Asset_Loader.php**
```bash
git rm includes/Engine/Asset_Loader.php
```

- [ ] **Step 7: Run all tests to verify nothing broke**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml`
Expected: All existing tests still pass

- [ ] **Step 8: Commit**
```bash
git add includes/Engine/Asset_Engine.php includes/Engine/Container_Config.php
git rm includes/Engine/Asset_Loader.php
git add tests/Asset_Engine_Test.php
git commit -m "feat: add Asset_Engine replacing Asset_Loader + Security_Headers"
```

---

### Task 3: View_Engine

**Files:**
- Create: `includes/Engine/View_Engine.php`
- Test: `tests/View_Engine_Test.php`
- Modify: `tests/bootstrap.php` (add Engine requires)

**Interfaces:**
- Consumes: Template_Loader, SEO_Engine, Asset_Engine, EventDispatcher (constructor)
- Produces: `View_Engine` with all HTML pipeline methods

- [ ] **Step 1: Write the failing View_Engine test**

`tests/View_Engine_Test.php`:
```php
<?php
use PHPUnit\Framework\TestCase;
use PhantomCore\Engine\View_Engine;
use PhantomCore\Engine\Template_Loader;
use PhantomCore\Engine\SEO_Engine;
use PhantomCore\Engine\Asset_Engine;
use PhantomCore\Engine\Security_Headers;
use PhantomCore\Engine\EventDispatcher;
use PhantomCore\Engine\Data_Engine;

class View_Engine_Test extends TestCase {
    private View_Engine $view;

    protected function setUp(): void {
        $seo = new SEO_Engine();
        $assets = new Asset_Engine(new Security_Headers());
        $events = new EventDispatcher();
        $this->view = new View_Engine(new Template_Loader(), $seo, $assets, $events);
    }

    public function test_resolve_returns_template_name(): void {
        $this->assertSame('index.html', $this->view->resolve(''));
    }

    public function test_load_returns_string(): void {
        $html = $this->view->load('index.html');
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<html', $html);
    }

    public function test_prepare_html_adds_skip_link(): void {
        $result = $this->view->prepare_html('<html><head></head><body>Content</body></html>');
        $this->assertStringContainsString('skip-link', $result);
    }

    public function test_prepare_html_adds_loading_div(): void {
        $result = $this->view->prepare_html('<html><head></head><body>Content</body></html>');
        $this->assertStringContainsString('phantom-loading', $result);
    }

    public function test_prepare_html_adds_aria_roles(): void {
        $result = $this->view->prepare_html('<html><head></head><body><header>H</header><main>M</main><footer>F</footer></body></html>');
        $this->assertStringContainsString('role="banner"', $result);
        $this->assertStringContainsString('role="main"', $result);
        $this->assertStringContainsString('role="contentinfo"', $result);
    }

    public function test_inject_auth_nonces_adds_script(): void {
        $result = $this->view->inject_auth_nonces('<html><head></head><body></body></html>');
        $this->assertStringContainsString('PhantomNonces', $result);
    }

    public function test_inject_customizer_css_returns_html_unchanged_without_css(): void {
        $result = $this->view->inject_customizer_css('<html><head></head><body></body></html>');
        $this->assertStringContainsString('</html>', $result);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter View_Engine tests/View_Engine_Test.php`
Expected: FAIL

- [ ] **Step 3: Write View_Engine implementation**

`includes/Engine/View_Engine.php`:
```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class View_Engine {
    private Template_Loader $template_loader;
    private SEO_Engine $seo;
    private Asset_Engine $assets;
    private EventDispatcher $events;

    public function __construct(
        Template_Loader $template_loader,
        SEO_Engine $seo,
        Asset_Engine $assets,
        EventDispatcher $events
    ) {
        $this->template_loader = $template_loader;
        $this->seo = $seo;
        $this->assets = $assets;
        $this->events = $events;
    }

    public function resolve(string $slug): string {
        return $this->template_loader->resolve($slug);
    }

    public function load(string $template): string {
        return $this->template_loader->load($template);
    }

    public function prepare_html(string $html): string {
        if (!empty($_COOKIE['phantom_dark_mode']) && '1' === $_COOKIE['phantom_dark_mode']) {
            $html = preg_replace('/<body(\s[^>]*)?>/', '<body$1 data-phantom-dark-mode="true">', $html, 1);
        }
        $html = preg_replace('/<body(\s[^>]*)?>/',
            '<body$1>' . "\n" . '<a class="skip-link screen-reader-text" href="#phantom-main-content">Skip to main content</a>',
            $html, 1);
        $loading = '<div id="phantom-loading" role="status" aria-hidden="true" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:var(--bg-color,#fff);z-index:9999;align-items:center;justify-content:center;transition:opacity .3s"><div style="width:40px;height:40px;border:3px solid var(--border-color,#e5e7eb);border-top-color:var(--accent-color,#6366f1);border-radius:50%;animation:phantom-spin .8s linear infinite"></div></div>';
        $html = preg_replace('/<body[^>]*>/', '$0' . "\n" . $loading, $html, 1);
        $html = preg_replace('/<header([^>]*)>/i', '<header$1 role="banner">', $html, 1);
        if (!preg_match('/<main[^>]*role=/i', $html)) {
            $html = preg_replace('/<main([^>]*)>/i', '<main$1 role="main">', $html, 1);
        }
        $html = preg_replace('/<footer([^>]*)>(?!.*role=)/i', '<footer$1 role="contentinfo">', $html, 1);
        $html = str_replace('</head>', '<style id="phantom-loading-css">@keyframes phantom-spin{to{transform:rotate(360deg)}}</style></head>', $html);
        return $html;
    }

    public function inject_seo(SEO_Engine $seo, string $html, string $slug, Data_Engine $data): string {
        $seo->with_product_id($data->get_resolved_product_id() ?? 0)
            ->with_post_id($data->get_resolved_post_id() ?? 0);
        return $seo->inject($html, $slug);
    }

    public function inject_assets(string $html, string $slug, bool $is_customizer_preview): string {
        if (!$is_customizer_preview) {
            $html = $this->assets->inject_all($html, $slug, $is_customizer_preview);
        }
        return $html;
    }

    public function inject_customizer_css(string $html): string {
        if (!class_exists('\Phantom_Custom_CSS')) return $html;
        $all_css = \Phantom_Custom_CSS::instance()->render_style();
        if ('' === $all_css) return $html;
        return str_replace('</head>', $all_css . '</head>', $html);
    }

    public function inject_bridge_data(string $html, Data_Engine $data): string {
        $payload = [
            'rest_url' => function_exists('rest_url') ? rest_url('phantom/v1') : '',
            'home_url' => function_exists('home_url') ? home_url('/') : '/',
            'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('wp_rest') : '',
            'api_nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('phantom_api') : '',
            'ajax_url' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '',
            'wc_ajax_url' => class_exists('WooCommerce') && function_exists('\WC_AJAX::get_endpoint')
                ? \WC_AJAX::get_endpoint('%%endpoint%%') : '',
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol')
                ? get_woocommerce_currency_symbol() : '$',
            'user_logged_in' => function_exists('is_user_logged_in') ? is_user_logged_in() : false,
            'routes' => [
                'shop' => function_exists('home_url') ? home_url('/shop') : '/shop',
                'cart' => function_exists('home_url') ? home_url('/cart') : '/cart',
                'checkout' => function_exists('home_url') ? home_url('/checkout') : '/checkout',
                'account' => function_exists('home_url') ? home_url('/account') : '/account',
            ],
            'theme' => [
                'name' => function_exists('wp_get_theme') ? (wp_get_theme()->get('Name') ?: 'Phantom Theme') : 'Phantom Theme',
                'version' => function_exists('wp_get_theme') ? (wp_get_theme()->get('Version') ?: '1.0') : '1.0',
            ],
        ];
        if ($data->get_resolved_product_id()) {
            $payload['product_id'] = $data->get_resolved_product_id();
        }
        if ($data->get_resolved_post_id()) {
            $payload['post_id'] = $data->get_resolved_post_id();
        }
        if ($data->get_category_slug()) {
            $payload['category'] = $data->get_category_slug();
        }
        $json = function_exists('wp_json_encode')
            ? wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $script = '<script id="phantom-bridge-data" type="application/json">' . $json . '</script>';
        $script .= '<script id="phantom-bridge-js">'
            . 'window.PhantomData=' . $json . ';'
            . 'window.PhantomData.api_nonce=document.getElementById("phantom-bridge-data")'
            . '?document.getElementById("phantom-bridge-data").textContent:JSON.stringify(window.PhantomData);'
            . 'try{window.PhantomData=JSON.parse(document.getElementById("phantom-bridge-data").textContent)}catch(e){}'
            . '</script>';
        return str_replace('</head>', $script . "\n" . '</head>', $html);
    }

    public function inject_auth_nonces(string $html): string {
        $nonces = [
            'wp_rest' => function_exists('wp_create_nonce') ? wp_create_nonce('wp_rest') : '',
            'phantom_api' => function_exists('wp_create_nonce') ? wp_create_nonce('phantom_api') : '',
            'woocommerce_cart' => function_exists('wp_create_nonce') ? wp_create_nonce('woocommerce-cart') : '',
        ];
        $json = json_encode($nonces);
        $script = '<script id="phantom-nonces">window.PhantomNonces=' . $json . ';</script>';
        return str_replace('</body>', $script . '</body>', $html);
    }

    public function inject_plugin_hooks(string $html): string {
        if (!function_exists('do_action')) return $html;
        ob_start();
        do_action('phantom_before_head_close');
        $head_hook = ob_get_clean();
        if ($head_hook) {
            $html = str_replace('</head>', $head_hook . '</head>', $html);
        }
        ob_start();
        do_action('phantom_before_body_close');
        $body_hook = ob_get_clean();
        if ($body_hook) {
            $html = str_replace('</body>', $body_hook . '</body>', $html);
        }
        ob_start();
        do_action('phantom_before_output');
        $output_hook = ob_get_clean();
        if ($output_hook) {
            $html = $output_hook . $html;
        }
        return $html;
    }

    public function inject_customizer_preview(string $html): string {
        if (!function_exists('wp_head') || !function_exists('wp_footer')) return $html;
        $html = preg_replace('/<script[^>]*src="[^"]*jquery(?:-\d[\w.]*)?(?:\.min)?\.js[^"]*"[^>]*><\/script>/i', '', $html);
        ob_start();
        wp_head();
        $html = str_replace('</head>', ob_get_clean() . '</head>', $html);
        ob_start();
        wp_footer();
        $html = str_replace('</body>', ob_get_clean() . '</body>', $html);
        return $html;
    }

    public function get_template_loader(): Template_Loader {
        return $this->template_loader;
    }
}
```

- [ ] **Step 4: Update bootstrap.php to load new Engine requires**

Add to `tests/bootstrap.php` after the existing Engine requires:
```php
require_once PHANTOM_CORE_PATH . 'includes/Engine/Asset_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/View_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Data_Engine.php';
```

- [ ] **Step 5: Run tests to verify View_Engine passes**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml --filter View_Engine`
Expected: PASS

- [ ] **Step 6: Commit**
```bash
git add includes/Engine/View_Engine.php tests/View_Engine_Test.php tests/bootstrap.php
git commit -m "feat: add View_Engine for HTML pipeline"
```

---

### Task 4: Rewrite Render_Engine as Thin Coordinator

**Files:**
- Rewrite: `includes/Engine/Render_Engine.php`

**Interfaces:**
- Consumes: View_Engine, Data_Engine, Asset_Engine, EventDispatcher, WooCommerce_Injector
- Produces: `Render_Engine::render(string): string` — identical output
- Maintains: `with_product_id()`, `with_post_id()`, `with_category()`, get_resolved_*(), get_category_slug(), get_template_loader() — delegates to Data_Engine/View_Engine

- [ ] **Step 1: Rewrite Render_Engine**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Render_Engine {
    private View_Engine $view;
    private Data_Engine $data;
    private Asset_Engine $assets;
    private EventDispatcher $events;
    private WooCommerce_Injector $wc_injector;

    public function __construct(
        View_Engine $view,
        Data_Engine $data,
        Asset_Engine $assets,
        EventDispatcher $events
    ) {
        $this->view = $view;
        $this->data = $data;
        $this->assets = $assets;
        $this->events = $events;
        $this->wc_injector = new WooCommerce_Injector($this, $this->events);
    }

    // Backward-compat: WooCommerce_Injector uses these

    public function with_product_id(int $id): self {
        $this->data->with_product_id($id);
        return $this;
    }

    public function with_post_id(int $id): self {
        $this->data->with_post_id($id);
        return $this;
    }

    public function with_category(string $slug): self {
        $this->data->with_category($slug);
        return $this;
    }

    public function get_resolved_product_id(): ?int {
        return $this->data->get_resolved_product_id();
    }

    public function get_resolved_post_id(): ?int {
        return $this->data->get_resolved_post_id();
    }

    public function get_category_slug(): ?string {
        return $this->data->get_category_slug();
    }

    public function get_template_loader(): Template_Loader {
        return $this->view->get_template_loader();
    }

    public function render(string $slug): string {
        $is_customizer_preview = isset($_GET['customize_changeset_uuid']);
        $template = $this->view->resolve($slug);

        status_header($template === '404.html' ? 404 : 200);

        $html = $this->view->load($template);
        if (empty($html)) {
            status_header(404);
            return '';
        }

        $html = $this->view->prepare_html($html);

        // WooCommerce content (Phase 5: extract to WooCommerceBridge)
        if (class_exists('WooCommerce')) {
            $html = $this->wc_injector->inject($html, $slug);
        }

        $html = $this->view->inject_seo($this->view, $html, $slug, $this->data);
        $html = $this->view->inject_assets($html, $slug, $is_customizer_preview);
        $html = $this->view->inject_customizer_css($html);
        $html = $this->view->inject_bridge_data($html, $this->data);
        $html = $this->view->inject_auth_nonces($html);
        $html = $this->view->inject_plugin_hooks($html);

        if ($is_customizer_preview) {
            $html = $this->view->inject_customizer_preview($html);
        }

        $this->assets->send_security_headers($is_customizer_preview);

        return $html;
    }
}
```

- [ ] **Step 2: Run all tests to verify nothing broke**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml`
Expected: All 40+ tests pass

- [ ] **Step 3: Verify Render_Engine is under 100 lines**
Run: `(Get-Content includes/Engine/Render_Engine.php).Count`
Expected: Less than 100

- [ ] **Step 4: Commit**
```bash
git add includes/Engine/Render_Engine.php
git commit -m "refactor: rewrite Render_Engine as thin coordinator (< 100 lines)"
```

---

### Task 5: Wire Everything Together

**Files:**
- Rewrite: `includes/Engine/Container_Config.php` (full wire-up)
- Update: `phantom-core.php` (add new requires)
- Update: `templates/shell.php` (verify imports)

- [ ] **Step 1: Rewrite Container_Config with all engines**

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Engine;

defined('ABSPATH') || exit;

class Container_Config {
    public static function configure(Container $container): void
    {
        // 1. Security_Headers — singleton
        $container->singleton(Security_Headers::class, function ($c) {
            return new Security_Headers();
        });

        // 2. Asset_Engine — singleton
        $container->singleton(Asset_Engine::class, function ($c) {
            return new Asset_Engine($c->get(Security_Headers::class));
        });

        // 3. EventDispatcher — singleton
        $container->singleton(EventDispatcher::class, function ($c) {
            return new EventDispatcher();
        });

        // 4. Data_Engine — factory (per-request state)
        $container->set(Data_Engine::class, function ($c) {
            return new Data_Engine();
        });

        // 5. View_Engine — singleton
        $container->singleton(View_Engine::class, function ($c) {
            return new View_Engine(
                $c->get(Template_Loader::class),
                $c->get(SEO_Engine::class),
                $c->get(Asset_Engine::class),
                $c->get(EventDispatcher::class)
            );
        });

        // 6. Render_Engine — singleton with pack resolution
        $container->singleton(Render_Engine::class, function ($c) {
            $pack = 'kids';
            if (class_exists('\PhantomCore\Settings_Registry')) {
                $registry = \PhantomCore\Settings_Registry::get_instance();
                if ($registry->has('template_pack')) {
                    $pack = $registry->get('template_pack');
                }
            }
            $engine = new Render_Engine(
                $c->get(View_Engine::class),
                $c->get(Data_Engine::class),
                $c->get(Asset_Engine::class),
                $c->get(EventDispatcher::class)
            );
            $engine->get_template_loader()->set_pack($pack);
            return $engine;
        });

        // 7. WooCommerce_Injector — factory (per-request)
        $container->set(WooCommerce_Injector::class, function ($c) {
            return new WooCommerce_Injector(
                $c->get(Render_Engine::class),
                $c->get(EventDispatcher::class)
            );
        });
    }
}
```

- [ ] **Step 2: Update phantom-core.php requires**

Add these lines after the existing Engine requires (around line 105):
```php
require_once PHANTOM_CORE_PATH . 'includes/Engine/Asset_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/View_Engine.php';
require_once PHANTOM_CORE_PATH . 'includes/Engine/Data_Engine.php';
```

Remove the obsolete Asset_Loader.php require line.

- [ ] **Step 3: Verify shell.php imports**

`templates/shell.php` already imports:
```php
use PhantomCore\Engine\Container;
use PhantomCore\Engine\Container_Config;
use PhantomCore\Engine\Render_Engine;
```
No changes needed — Shell only uses Container, Container_Config, and Render_Engine.

- [ ] **Step 4: Run all tests to verify everything works**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml`
Expected: All tests pass

- [ ] **Step 5: Syntax check all PHP files**
Run: `Get-ChildItem -Path includes\Engine -Filter *.php | ForEach-Object { php -l $_.FullName } 2>&1`
Expected: All 12 files show "No syntax errors detected"

- [ ] **Step 6: Commit**
```bash
git add includes/Engine/Container_Config.php phantom-core.php
git commit -m "feat: wire three-engine split in Container_Config"
```

---

### Task 6: Final Verification

- [ ] **Step 1: Full PHPUnit test suite**
Run: `php C:\Users\hamma\Downloads\wordpress\phpunit.phar --configuration tests\phpunit.xml`
Expected: OK (40+ tests, all pass)

- [ ] **Step 2: Full PHP syntax check**
Run: `Get-ChildItem -Recurse -Path includes -Filter *.php | ForEach-Object { php -l $_.FullName } 2>&1 | Select-String "Parse error|Fatal error"`
Expected: No output (zero errors)

- [ ] **Step 3: Verify Render_Engine line count**
Run: `(Get-Content includes/Engine/Render_Engine.php).Count`
Expected: ≤ 100

- [ ] **Step 4: Verify no Asset_Loader.php remains**
Run: `Test-Path includes/Engine/Asset_Loader.php`
Expected: False

- [ ] **Step 5: Commit final**
```bash
git add -A
git commit -m "chore: Phase 2 final verification"
```
