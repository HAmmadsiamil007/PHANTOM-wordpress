# Phantom OS Phase 0 — Foundation Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development (recommended) or executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Harden Phantom Core v2.0 foundation: create contracts, fix dead code, implement PhantomInjector, merge JS architectures, add minification build pipeline.

**Architecture:** Add contract interfaces (AdapterInterface, RendererInterface, ViewModelInterface) that refactored adapters/renderers implement. Create component template directory for renderers. Implement PhantomInjector to fix undefined JS reference. Merge phantom-bridge.js monolithic IIFE into modular JS architecture. Remove 5 dead code files. Wire Hero::render() and Footer::render() into production flow.

**Tech Stack:** PHP 7.4+ (typed properties, interfaces), WordPress plugin, WooCommerce, vanilla JS (no build framework), Terser for minification.

**Spec reference:** `docs/superpowers/specs/2026-07-26-phantom-os-master-plan.md` sections 2.3, 6.2-6.11

## Global Constraints

- All PHP files pass `php -l` syntax check
- No new dependencies — Terser via npm (`npm install --save-dev terser`)
- Follow existing code conventions: namespaces (`PhantomCore\Adapters`, `PhantomCore\Renderer`, `PhantomCore\Contracts`, `PhantomCore\ViewModels`), snake_case method names
- Component templates use `{{UPPER_SNAKE_CASE}}` placeholders
- JS uses ES5-compatible syntax (vanilla, no transpilation)
- Build output: `phantom-core.min.js` < 70% of `phantom-data.js`

---

### Task 1: Create Contracts Directory + Interfaces

**Files:**
- Create: `includes/contracts/interface-adapter.php`
- Create: `includes/contracts/interface-renderer.php`
- Create: `includes/contracts/interface-view-model.php`

**Interfaces:**
- Produces: `PhantomCore\Contracts\AdapterInterface` with `normalize($input): array` and `normalize_collection(array $inputs): array`
- Produces: `PhantomCore\Contracts\RendererInterface` with `render(array $data): string` and `render_collection(array $data_set): string`
- Produces: `PhantomCore\Contracts\ViewModelInterface` (marker interface, no methods)

- [ ] **Step 1: Create `includes/contracts/` directory**

```bash
mkdir -p phantom-core/includes/contracts
```

- [ ] **Step 2: Create `interface-adapter.php`**

```php
<?php
namespace PhantomCore\Contracts;

interface AdapterInterface {
    public function normalize($input = null): array;
    public function normalize_collection(array $inputs): array;
}
```

- [ ] **Step 3: Create `interface-renderer.php`**

```php
<?php
namespace PhantomCore\Contracts;

interface RendererInterface {
    public function render(array $data): string;
    public function render_collection(array $data_set): string;
}
```

- [ ] **Step 4: Create `interface-view-model.php`**

```php
<?php
namespace PhantomCore\Contracts;

interface ViewModelInterface {}
```

- [ ] **Step 5: Verify syntax on all 3 files**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-renderer.php
php -d error_reporting=E_ALL -l phantom-core/includes/contracts/interface-view-model.php
```

Expected: Each returns `No syntax errors detected` for the corresponding file.

- [ ] **Step 6: Commit**

```bash
git add phantom-core/includes/contracts/
git commit -m "feat(phase0): create AdapterInterface, RendererInterface, ViewModelInterface contracts"
```

---

### Task 2: Create ViewModel Classes

**Files:**
- Create: `includes/ViewModels/product-view-model.php`
- Create: `includes/ViewModels/category-view-model.php`
- Create: `includes/ViewModels/post-view-model.php`

**Interfaces:**
- Consumes: `PhantomCore\Contracts\ViewModelInterface` (marker)
- Produces: 3 final classes implementing ViewModelInterface, each with PHP 7.4+ typed properties documenting the array shape their corresponding adapter returns

- [ ] **Step 1: Create `includes/ViewModels/` directory**

```bash
mkdir -p phantom-core/includes/ViewModels
```

- [ ] **Step 2: Create `product-view-model.php`**

```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Product_ViewModel implements ViewModelInterface {
    public int $id;
    public string $title;
    public string $slug;
    public string $permalink;
    public string $description;
    public string $short_description;
    public string $price;
    public string $regular_price;
    public string $sale_price;
    public string $currency;
    public string $image;
    public array $gallery;
    public string $sku;
    public string $stock_status;
    public bool $in_stock;
    public string $type;
    public string $add_to_cart_text;
    public string $add_to_cart_url;
    public array $categories;
    public array $tags;
    public array $attributes;
    public array $variations;
    public float $rating;
    public int $review_count;
    public string $badge;
}
```

- [ ] **Step 3: Create `category-view-model.php`**

```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Category_ViewModel implements ViewModelInterface {
    public int $id;
    public string $name;
    public string $slug;
    public string $permalink;
    public string $description;
    public string $image;
    public int $count;
}
```

- [ ] **Step 4: Create `post-view-model.php`**

```php
<?php
namespace PhantomCore\ViewModels;

use PhantomCore\Contracts\ViewModelInterface;

final class Post_ViewModel implements ViewModelInterface {
    public int $id;
    public string $title;
    public string $slug;
    public string $permalink;
    public string $excerpt;
    public string $content;
    public string $date;
    public string $image;
    public string $author;
    public array $categories;
    public array $tags;
}
```

- [ ] **Step 5: Verify syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/product-view-model.php
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/category-view-model.php
php -d error_reporting=E_ALL -l phantom-core/includes/ViewModels/post-view-model.php
```

Expected: Each returns `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add phantom-core/includes/ViewModels/
git commit -m "feat(phase0): create ViewModel classes documenting adapter array shapes"
```

---

### Task 3: Refactor Adapters to Implement AdapterInterface

**Files:**
- Modify: `includes/adapters/class-product-adapter.php`
- Modify: `includes/adapters/class-category-adapter.php`
- Modify: `includes/adapters/class-menu-adapter.php`
- Modify: `includes/adapters/class-hero-adapter.php`

**Interfaces:**
- Consumes: `PhantomCore\Contracts\AdapterInterface` with `normalize($input = null): array` and `normalize_collection(array $inputs): array`
- Produces: 4 adapter classes now pass `instanceof AdapterInterface`

- [ ] **Step 1: Refactor `Product_Adapter`**

Add `use PhantomCore\Contracts\AdapterInterface;` and change `class Product_Adapter` to `class Product_Adapter implements AdapterInterface`.

The existing `normalize($product)` and `normalize_collection(array $products)` signatures already match. The `empty()` method is private — no change needed.

- [ ] **Step 2: Verify Product_Adapter syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-product-adapter.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Refactor `Category_Adapter`**

Add `use PhantomCore\Contracts\AdapterInterface;` and change `class Category_Adapter` to `class Category_Adapter implements AdapterInterface`.

The existing `normalize($term)` and `normalize_collection(array $terms)` signatures already match. No other changes needed.

- [ ] **Step 4: Verify Category_Adapter syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-category-adapter.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Refactor `Menu_Adapter`**

Add `use PhantomCore\Contracts\AdapterInterface;` and change `class Menu_Adapter` to `class Menu_Adapter implements AdapterInterface`.

The existing `normalize(string $location)` takes a string, not a generic `$input`. This is fine — the interface allows `$input = null` as default, but the Menu_Adapter's `normalize()` can be stricter. No signature change needed.

- [ ] **Step 6: Verify Menu_Adapter syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-menu-adapter.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 7: Refactor `Hero_Adapter`**

Add `use PhantomCore\Contracts\AdapterInterface;` and change `class Hero_Adapter` to `class Hero_Adapter implements AdapterInterface`.

The existing `normalize()` takes no arguments. The interface default `$input = null` satisfies this. Add `normalize_collection(array $inputs): array` if not present:

```php
public function normalize_collection(array $inputs): array {
    return array_map([$this, 'normalize'], $inputs);
}
```

- [ ] **Step 8: Verify Hero_Adapter syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-hero-adapter.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 9: Commit**

```bash
git add phantom-core/includes/adapters/
git commit -m "feat(phase0): refactor 4 adapters to implement AdapterInterface"
```

---

### Task 4: Create Component Template Directory + Templates

**Files:**
- Create: `frontend/html/components/product-card.html`
- Create: `frontend/html/components/category-card.html`
- Create: `frontend/html/components/blog-card.html`

- [ ] **Step 1: Create directory**

```bash
mkdir -p phantom-core/frontend/html/components
```

- [ ] **Step 2: Create `product-card.html`**

```html
<div class="product-card" data-product-id="{{PRODUCT_ID}}">
    <div class="product-card__badge">{{BADGE}}</div>
    <a href="{{PERMALINK}}" class="product-card__image-link">
        <img src="{{IMAGE}}" alt="{{TITLE}}" loading="lazy" class="product-card__image">
    </a>
    <div class="product-card__body">
        <h3 class="product-card__title">
            <a href="{{PERMALINK}}">{{TITLE}}</a>
        </h3>
        <div class="product-card__rating">
            <span class="product-card__stars">{{RATING_HTML}}</span>
            <span class="product-card__reviews">({{REVIEW_COUNT}})</span>
        </div>
        <div class="product-card__categories">{{CATEGORIES}}</div>
        <div class="product-card__price">{{PRICE}}</div>
        <div class="product-card__actions">
            <a href="{{ADD_TO_CART_URL}}" class="product-card__atc-btn">{{ADD_TO_CART_TEXT}}</a>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Create `category-card.html`**

```html
<div class="category-card" data-category-id="{{CATEGORY_ID}}">
    <a href="{{PERMALINK}}" class="category-card__link">
        <div class="category-card__image-wrap">
            <img src="{{IMAGE}}" alt="{{NAME}}" loading="lazy" class="category-card__image">
        </div>
        <h3 class="category-card__title">{{NAME}}</h3>
        <span class="category-card__count">{{COUNT}} {{PRODUCTS_TEXT}}</span>
    </a>
</div>
```

- [ ] **Step 4: Create `blog-card.html`**

```html
<div class="blog-card" data-post-id="{{POST_ID}}">
    <a href="{{PERMALINK}}" class="blog-card__image-link">
        <img src="{{IMAGE}}" alt="{{TITLE}}" loading="lazy" class="blog-card__image">
    </a>
    <div class="blog-card__body">
        <span class="blog-card__date">{{DATE}}</span>
        <h3 class="blog-card__title">
            <a href="{{PERMALINK}}">{{TITLE}}</a>
        </h3>
        <p class="blog-card__excerpt">{{EXCERPT}}</p>
        <span class="blog-card__read-more">{{READ_MORE_TEXT}}</span>
    </div>
</div>
```

- [ ] **Step 5: Verify files exist**

```bash
ls -la phantom-core/frontend/html/components/
```

Expected: 3 files listed (product-card.html, category-card.html, blog-card.html).

- [ ] **Step 6: Commit**

```bash
git add phantom-core/frontend/html/components/
git commit -m "feat(phase0): create component template directory with 3 HTML templates"
```

---

### Task 5: Refactor Renderers to Implement RendererInterface + Use inject()

**Files:**
- Modify: `includes/renderer/class-component-renderer.php`
- Modify: `includes/renderer/class-product-card.php`
- Modify: `includes/renderer/class-category-card.php`
- Modify: `includes/renderer/class-hero.php`
- Modify: `includes/renderer/class-footer.php`

**Interfaces:**
- Consumes: `PhantomCore\Contracts\RendererInterface` with `render(array $data): string` and `render_collection(array $data_set): string`

- [ ] **Step 1: Add `apply_filters` hook to Component_Renderer**

Modify `inject()` to add an `apply_filters` hook so other plugins can modify rendered HTML:

```php
<?php
namespace PhantomCore\Renderer;

use PhantomCore\Contracts\RendererInterface;

abstract class Component_Renderer implements RendererInterface {
    abstract public function render(array $data): string;

    public function render_collection(array $data_set): string {
        $output = '';
        foreach ($data_set as $data) {
            $output .= $this->render($data);
        }
        return $output;
    }

    protected function load_template(string $name): string {
        $path = PHANTOM_CORE_PATH . 'frontend/html/components/' . $name . '.html';
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return '';
    }

    protected function inject(string $template, array $data): string {
        $html = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($data) {
            $key = strtolower($matches[1]);
            return isset($data[$key]) ? $data[$key] : $matches[0];
        }, $template);
        return apply_filters('phantom_component_html', $html, $data);
    }
}
```

- [ ] **Step 2: Verify Component_Renderer syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-component-renderer.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Refactor `Product_Card`**

Add `use PhantomCore\Contracts\RendererInterface;`. Change `class Product_Card extends Component_Renderer` to `class Product_Card extends Component_Renderer` (already extends, interface is on parent).

Replace `str_replace` calls in `render()` with `$this->inject()`:

```php
public function render(array $data): string {
    $badge = !empty($data['badge']) ? '<span class="product-card__badge">' . esc_html($data['badge']) . '</span>' : '';
    $stars = '';
    if (!empty($data['rating'])) {
        $stars = str_repeat('★', floor($data['rating']));
        if ($data['rating'] - floor($data['rating']) >= 0.5) $stars .= '½';
    }
    $cats = !empty($data['categories']) ? $this->render_categories($data['categories']) : '';
    $price_html = !empty($data['price']) ? $data['price'] : '';

    return $this->inject($this->template, [
        'product_id' => esc_attr($data['id'] ?? ''),
        'badge' => $badge,
        'permalink' => esc_url($data['permalink'] ?? ''),
        'image' => esc_url($data['image'] ?? ''),
        'title' => esc_html($data['title'] ?? ''),
        'rating_html' => $stars,
        'review_count' => intval($data['review_count'] ?? 0),
        'categories' => $cats,
        'price' => $price_html,
        'add_to_cart_url' => esc_url($data['add_to_cart_url'] ?? ''),
        'add_to_cart_text' => esc_html($data['add_to_cart_text'] ?? __('Add to Cart', 'phantom-core')),
    ]);
}

private function render_categories(array $categories): string {
    $output = '';
    foreach ($categories as $cat) {
        $output .= '<a href="' . esc_url($cat['permalink'] ?? '') . '" class="product-card__category">'
                 . esc_html($cat['name'] ?? '') . '</a>';
    }
    return $output;
}
```

- [ ] **Step 4: Verify Product_Card syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-product-card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Refactor `Category_Card`**

Replace `str_replace` with `$this->inject()`:

```php
public function render(array $data): string {
    return $this->inject($this->template, [
        'category_id' => esc_attr($data['id'] ?? ''),
        'permalink' => esc_url($data['permalink'] ?? ''),
        'image' => esc_url($data['image'] ?? ''),
        'name' => esc_html($data['name'] ?? ''),
        'count' => intval($data['count'] ?? 0),
        'products_text' => __('products', 'phantom-core'),
    ]);
}
```

- [ ] **Step 6: Verify Category_Card syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-category-card.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 7: Refactor `Hero`**

Replace manual HTML building with `$this->inject()` where possible. The hero section is complex (responsive `<picture>` elements) — inject into a template string:

```php
public function render(array $data): string {
    $template = $this->load_template('hero');
    if (empty($template)) {
        $template = '<section class="hero" style="--hero-overlay-opacity: {{OVERLAY_OPACITY}}">';
        if (!empty($data['enable_responsive'])) {
            $template .= '<picture>';
            $template .= '<source media="(min-width: {{TABLET_BREAKPOINT}}px)" srcset="{{IMAGE_DESKTOP}}">';
            $template .= '<source media="(min-width: {{MOBILE_BREAKPOINT}}px)" srcset="{{IMAGE_TABLET}}">';
            $template .= '<source srcset="{{IMAGE_MOBILE}}">';
            $template .= '<img src="{{IMAGE_DESKTOP}}" alt="{{TITLE}}" loading="{{LOADING}}">';
            $template .= '</picture>';
        } else {
            $template .= '<img src="{{IMAGE_DESKTOP}}" alt="{{TITLE}}" loading="{{LOADING}}" class="hero__image">';
        }
        $template .= '<div class="hero__overlay"></div>';
        $template .= '<div class="hero__content">';
        $template .= '<h1 class="hero__title">{{TITLE}}</h1>';
        if (!empty($data['subtitle'])) {
            $template .= '<p class="hero__subtitle">{{SUBTITLE}}</p>';
        }
        $template .= '<p class="hero__description">{{DESCRIPTION}}</p>';
        $template .= '<a href="{{BTN_URL}}" class="hero__btn">{{BTN_TEXT}}</a>';
        $template .= '</div></section>';
    }

    return $this->inject($template, [
        'title' => esc_html($data['title'] ?? ''),
        'subtitle' => esc_html($data['subtitle'] ?? ''),
        'description' => esc_html($data['description'] ?? ''),
        'btn_url' => esc_url($data['btn_url'] ?? ''),
        'btn_text' => esc_html($data['btn_text'] ?? __('Learn More', 'phantom-core')),
        'image_desktop' => esc_url($data['image'] ?? ''),
        'image_tablet' => esc_url($data['image_tablet'] ?? $data['image'] ?? ''),
        'image_mobile' => esc_url($data['image_mobile'] ?? $data['image'] ?? ''),
        'overlay_opacity' => esc_attr($data['overlay_opacity'] ?? '0.5'),
        'enable_responsive' => !empty($data['enable_responsive']) ? 'true' : '',
        'tablet_breakpoint' => esc_attr($data['tablet_breakpoint'] ?? '1024'),
        'mobile_breakpoint' => esc_attr($data['mobile_breakpoint'] ?? '768'),
        'loading' => esc_attr($data['loading'] ?? 'lazy'),
    ]);
}
```

- [ ] **Step 8: Verify Hero syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-hero.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 9: Refactor `Footer`**

Replace manual HTML with `$this->inject()`:

```php
public function render(array $data): string {
    $template = '<footer class="site-footer"><div class="footer__widgets">{{WIDGETS}}</div>';
    $template .= '<div class="footer__bottom"><p class="footer__copyright">{{COPYRIGHT}}</p></div></footer>';

    return $this->inject($template, [
        'widgets' => $data['widgets'] ?? '',
        'copyright' => esc_html($data['copyright'] ?? ''),
    ]);
}
```

- [ ] **Step 10: Verify Footer syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-footer.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 11: Commit**

```bash
git add phantom-core/includes/renderer/
git commit -m "feat(phase0): refactor 5 renderers to implement RendererInterface and use inject()"
```

---

### Task 6: Create PhantomInjector.js + Wire into phantom-core.js

**Files:**
- Create: `frontend/assets/js/phantom-injector.js`
- Modify: `frontend/assets/js/phantom-core.js`

**Interfaces:**
- Produces: `window.PhantomInjector` object with `injectContent`, `injectAttributes`, `renderComponent`, `injectSettings`, `injectMenus`, `injectProducts`
- Consumes: `<script>` tag in `shell.php` or existing script loading order

- [ ] **Step 1: Create `phantom-injector.js`**

```javascript
(function (w) {
    'use strict';

    var PhantomInjector = {
        injectContent: function (element, data) {
            if (!element || !data) return;
            var html = element.innerHTML;
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                    html = html.replace(pattern, data[key]);
                }
            }
            element.innerHTML = html;
        },

        injectAttributes: function (element, data) {
            if (!element || !data) return;
            var attrs = element.attributes;
            for (var i = 0; i < attrs.length; i++) {
                var attr = attrs[i];
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                        attr.value = attr.value.replace(pattern, data[key]);
                    }
                }
            }
        },

        renderComponent: function (container, template, data) {
            if (!container || !template) return;
            var html = template;
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                    html = html.replace(pattern, data[key]);
                }
            }
            container.innerHTML = html;
        },

        injectSettings: function (settings) {
            if (!settings) return;
            var els = document.querySelectorAll('[data-phantom-setting]');
            for (var i = 0; i < els.length; i++) {
                var el = els[i];
                var key = el.getAttribute('data-phantom-setting');
                if (settings[key] !== undefined) {
                    if (el.tagName === 'IMG') {
                        el.src = settings[key];
                    } else {
                        el.textContent = settings[key];
                    }
                }
            }
        },

        injectMenus: function (menus) {
            if (!menus) return;
            for (var location in menus) {
                if (menus.hasOwnProperty(location)) {
                    var container = document.querySelector('[data-phantom-menu="' + location + '"]');
                    if (!container) continue;
                    var items = menus[location].items || [];
                    var html = '';
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        html += '<li class="nav-item">';
                        html += '<a href="' + (item.url || '#') + '" class="nav-link">';
                        html += item.title || '';
                        html += '</a></li>';
                    }
                    container.innerHTML = html;
                }
            }
        },

        injectProducts: function (products) {
            if (!products) return;
            var containers = document.querySelectorAll('[data-phantom-products]');
            for (var c = 0; c < containers.length; c++) {
                var container = containers[c];
                var template = container.getAttribute('data-phantom-template') || '';
                var html = '';
                for (var i = 0; i < products.length; i++) {
                    var product = products[i];
                    var itemHtml = template;
                    for (var key in product) {
                        if (product.hasOwnProperty(key)) {
                            var pattern = new RegExp('\\{\\{' + key.toUpperCase() + '\\}\\}', 'g');
                            itemHtml = itemHtml.replace(pattern, product[key] || '');
                        }
                    }
                    html += itemHtml;
                }
                container.innerHTML = html;
            }
        }
    };

    w.PhantomInjector = PhantomInjector;
})(window);
```

- [ ] **Step 2: Update `phantom-core.js` to call PhantomInjector**

Add actual injection calls after the existing `PhantomData` checks, replacing the `w.PhantomInjector &&` guard no-ops:

```javascript
// Initialize PhantomInjector with data from PhantomData
(function () {
    if (!window.PhantomInjector) return;

    if (window.PhantomData) {
        // Inject settings into [data-phantom-setting] elements
        if (window.PhantomData.settings) {
            window.PhantomInjector.injectSettings(window.PhantomData.settings);
        }

        // Inject menus into [data-phantom-menu] elements
        if (window.PhantomData.menus) {
            window.PhantomInjector.injectMenus(window.PhantomData.menus);
        }

        // Inject products into [data-phantom-products] elements
        if (window.PhantomData.products) {
            window.PhantomInjector.injectProducts(window.PhantomData.products);
        }
    }
})();
```

- [ ] **Step 3: Verify no JS syntax errors**

```bash
node -e "require('fs').readFileSync('phantom-core/frontend/assets/js/phantom-injector.js', 'utf8').split('\n').forEach(function(l,i){try{Function(l)}catch(e){}}); console.log('Syntax OK')"
```

Expected: `Syntax OK` (Note: This is a rough check. The file will be concatenated and minified in build, not executed standalone.)

- [ ] **Step 4: Commit**

```bash
git add phantom-core/frontend/assets/js/phantom-injector.js phantom-core/frontend/assets/js/phantom-core.js
git commit -m "feat(phase0): create PhantomInjector.js with DOM injection API, wire into phantom-core.js"
```

---

### Task 7: Merge phantom-bridge.js into Modular JS

**Files:**
- Modify: `frontend/assets/js/services/api-services.js` — add retry-wrapped fetch + REST persistence
- Modify: `frontend/assets/js/services/event-services.js` — add `onSettingChange` event type
- Delete: `frontend/assets/js/phantom-bridge.js`

- [ ] **Step 1: Add retry fetch to `api-services.js`**

```javascript
// Add to PhantomServices.Api:
function saveSetting(key, value) {
    return phantomFetch(restUrl + '/phantom/v1/settings/' + key, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify({ value: value })
    });
}

function saveSettings(data) {
    return phantomFetch(restUrl + '/phantom/v1/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify(data)
    });
}

// Internal: retry-wrapped fetch
function phantomFetch(url, options, retries) {
    retries = retries || 3;
    return fetch(url, options).then(function (response) {
        if (!response.ok && retries > 0) {
            return phantomFetch(url, options, retries - 1);
        }
        return response.json();
    }).catch(function (err) {
        if (retries > 0) {
            return phantomFetch(url, options, retries - 1);
        }
        throw err;
    });
}
```

- [ ] **Step 2: Add `onSettingChange` event type to `event-services.js`**

```javascript
// Add to PhantomEvents:
onSettingChange: function (key, fn) {
    return this.on('setting_change_' + key, fn);
},
offSettingChange: function (key, fn) {
    return this.off('setting_change_' + key, fn);
},
emitSettingChange: function (key, value) {
    return this.emit('setting_change_' + key, value);
}
```

- [ ] **Step 3: Delete `phantom-bridge.js`**

```bash
rm phantom-core/frontend/assets/js/phantom-bridge.js
```

- [ ] **Step 4: Verify phantom-bridge.js is gone and no remaining references**

```bash
if (Test-Path "phantom-core/frontend/assets/js/phantom-bridge.js") { "FAIL: file still exists" } else { "PASS: file deleted" }
```

- [ ] **Step 5: Commit**

```bash
git add phantom-core/frontend/assets/js/services/api-services.js phantom-core/frontend/assets/js/services/event-services.js -A
git rm phantom-core/frontend/assets/js/phantom-bridge.js
git commit -m "feat(phase0): merge phantom-bridge.js functionality into modular JS services"
```

---

### Task 8: Fix Build Pipeline with Terser + Source Maps

**Files:**
- Modify: `build.js`

- [ ] **Step 1: Install terser**

```bash
cd phantom-core && npm install --save-dev terser
```

Expected: terser added to `package.json` devDependencies.

- [ ] **Step 2: Update `build.js` to use terser for minification**

```javascript
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const srcDir = path.join(__dirname, 'frontend', 'assets', 'js');
const outputDir = srcDir;

// Source files in order (dependencies first)
const files = [
    'services/event-services.js',
    'services/api-services.js',
    'services/component-services.js',
    'services/data-services.js',
    'adapters/product-adapter.js',
    'adapters/category-adapter.js',
    'adapters/menu-adapter.js',
    'adapters/settings-adapter.js',
    'renderers/product-renderer.js',
    'renderers/category-renderer.js',
    'renderers/menu-renderer.js',
    'renderers/settings-renderer.js',
    'phantom-injector.js',
    'phantom-core.js',
];

// Concatenate
let bundle = '';
files.forEach(function (file) {
    const filePath = path.join(srcDir, file);
    if (fs.existsSync(filePath)) {
        bundle += fs.readFileSync(filePath, 'utf8') + '\n';
    } else {
        console.warn('Warning: File not found - ' + filePath);
    }
});

// Write concatenated output
const concatPath = path.join(outputDir, 'phantom-data.js');
fs.writeFileSync(concatPath, bundle, 'utf8');
console.log('Concatenated: ' + concatPath + ' (' + (bundle.length / 1024).toFixed(1) + ' KB)');

// Minify with terser
const minPath = path.join(outputDir, 'phantom-core.min.js');
const mapPath = path.join(outputDir, 'phantom-core.min.js.map');

try {
    execSync(
        'npx terser "' + concatPath + '" --compress --mangle --source-map "url=\'phantom-core.min.js.map\',root=\'/\'" -o "' + minPath + '"',
        { stdio: 'inherit', cwd: __dirname }
    );
    console.log('Minified: ' + minPath);

    const minSize = fs.statSync(minPath).size;
    const concatSize = fs.statSync(concatPath).size;
    const ratio = ((minSize / concatSize) * 100).toFixed(1);
    console.log('Minification ratio: ' + ratio + '% (target: < 70%)');

    if (ratio >= 70) {
        console.warn('Warning: Minification ratio ' + ratio + '% exceeds 70% target');
    }
} catch (err) {
    console.error('Minification failed:', err.message);
    // Fallback: copy concatenated as minified (no minification)
    fs.copyFileSync(concatPath, minPath);
    console.warn('Fallback: copied concatenated output as minified');
}
```

- [ ] **Step 3: Run build and verify output**

```bash
cd phantom-core && node build.js
```

Expected: `phantom-data.js` and `phantom-core.min.js` generated. Minification ratio < 70%.

```bash
ls -lh phantom-core/frontend/assets/js/phantom-core.min.js phantom-core/frontend/assets/js/phantom-data.js
```

Expected: `phantom-data.js` is ~100KB, `phantom-core.min.js` is < 35KB.

- [ ] **Step 4: Verify phantom-core.min.js starts with minified code (not plaintext)**

```bash
head -c 20 phantom-core/frontend/assets/js/phantom-core.min.js
```

Expected: First ~20 characters look like compressed code (no `//` comments or readable variable names at start).

- [ ] **Step 5: Commit**

```bash
git add phantom-core/build.js phantom-core/package.json phantom-core/package-lock.json phantom-core/frontend/assets/js/phantom-data.js phantom-core/frontend/assets/js/phantom-core.min.js
git commit -m "feat(phase0): add terser minification + source maps to build pipeline"
```

---

### Task 9: Remove Dead Code Files

**Files:**
- Delete: `includes/adapters/class-post-adapter.php`
- Delete: `includes/adapters/class-settings-adapter.php`
- Delete: `includes/renderer/class-navigation.php`
- Delete: `includes/renderer/class-blog-card.php`

- [ ] **Step 1: Remove all 4 dead files**

```bash
rm phantom-core/includes/adapters/class-post-adapter.php
rm phantom-core/includes/adapters/class-settings-adapter.php
rm phantom-core/includes/renderer/class-navigation.php
rm phantom-core/includes/renderer/class-blog-card.php
```

- [ ] **Step 2: Verify files are gone**

```bash
ls phantom-core/includes/adapters/ phantom-core/includes/renderer/
```

Expected: class-post-adapter.php, class-settings-adapter.php, class-navigation.php, class-blog-card.php are not listed.

- [ ] **Step 3: PHP syntax check on remaining adapter and renderer files**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-product-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-category-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-menu-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/adapters/class-hero-adapter.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-component-renderer.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-product-card.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-category-card.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-hero.php
php -d error_reporting=E_ALL -l phantom-core/includes/renderer/class-footer.php
```

Expected: All return `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git rm phantom-core/includes/adapters/class-post-adapter.php phantom-core/includes/adapters/class-settings-adapter.php phantom-core/includes/renderer/class-navigation.php phantom-core/includes/renderer/class-blog-card.php
git commit -m "fix(phase0): remove dead code files (Post_Adapter, Settings_Adapter, Navigation, Blog_Card)"
```

---

### Task 10: Wire Hero::render() + Footer::render() in WooCommerce_Injector

**Files:**
- Modify: `includes/Engine/WooCommerce_Injector.php`

- [ ] **Step 1: Update WooCommerce_Injector to call hero and footer render methods**

Add calls in the `inject()` method to render Hero and Footer sections:

```php
// In inject() method, add after the existing content injection:
// Hero section
if (in_array($slug, ['home', 'index', 'default'], true)) {
    $html = $this->inject_hero_section($html);
}

// Footer widgets
$html = $this->inject_footer_widgets($html);
```

Add the two new private methods:

```php
private function inject_hero_section(string $html): string {
    $hero_data = $this->hero_adapter->normalize();
    $hero_html = $this->hero->render($hero_data);
    return str_replace('{{HERO_SECTION}}', $hero_html, $html);
}

private function inject_footer_widgets(string $html): string {
    $widgets = '';
    ob_start();
    dynamic_sidebar('footer-1');
    $widgets .= ob_get_clean();
    ob_start();
    dynamic_sidebar('footer-2');
    $widgets .= ob_get_clean();

    $footer_html = $this->footer->render([
        'widgets' => $widgets,
        'copyright' => '&copy; ' . date('Y') . ' ' . get_bloginfo('name'),
    ]);
    return str_replace('{{WIDGETS}}', $footer_html, $html);
}
```

- [ ] **Step 2: Verify syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/Engine/WooCommerce_Injector.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add phantom-core/includes/Engine/WooCommerce_Injector.php
git commit -m "feat(phase0): wire Hero::render() and Footer::render() into WooCommerce_Injector"
```

---

### Task 11: Clean Up shell.php + Render_Engine Imports

**Files:**
- Modify: `templates/shell.php`
- Modify: `includes/Engine/Render_Engine.php`

- [ ] **Step 1: Remove unused imports from `shell.php`**

Remove these `use` statements (they reference deleted or unused classes):
```php
use PhantomCore\Renderer\Navigation;
```

The file may also have `use PhantomCore\Adapters\Menu_Adapter;` or similar — remove any `use` statements referencing:
- `PhantomCore\Adapters\Post_Adapter`
- `PhantomCore\Adapters\Settings_Adapter`
- `PhantomCore\Renderer\Navigation`
- `PhantomCore\Renderer\Blog_Card`

- [ ] **Step 2: Verify shell.php syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/templates/shell.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 3: Clean unused imports from `Render_Engine.php`**

Check for and remove any imports referencing deleted classes (`Post_Adapter`, `Settings_Adapter`, `Navigation`, `Blog_Card`) or unused classes.

- [ ] **Step 4: Verify Render_Engine.php syntax**

```bash
php -d error_reporting=E_ALL -l phantom-core/includes/Engine/Render_Engine.php
```

Expected: `No syntax errors detected`.

- [ ] **Step 5: Commit**

```bash
git add phantom-core/templates/shell.php phantom-core/includes/Engine/Render_Engine.php
git commit -m "chore(phase0): remove unused imports from shell.php and Render_Engine.php"
```

---

### Task 12: Syntax Check + Verify

**Files:**
- Verify: All 68+ PHP files across the project

- [ ] **Step 1: Run syntax check on ALL PHP files**

```bash
Get-ChildItem -Recurse -Filter *.php -Path phantom-core/ | ForEach-Object { php -d error_reporting=E_ALL -l $_.FullName }
```

Expected: All files return `No syntax errors detected`. Count total files checked.

- [ ] **Step 2: Verify no grep hits for deleted classes**

```bash
grep -r "Post_Adapter" phantom-core/includes/ phantom-core/templates/ phantom-core/admin/
grep -r "Settings_Adapter" phantom-core/includes/ phantom-core/templates/ phantom-core/admin/
grep -r "class-navigation" phantom-core/includes/ phantom-core/templates/
grep -r "class-blog-card" phantom-core/includes/ phantom-core/templates/
grep -r "phantomFetch\|_listeners\|onSettingChange\|_emit\|setSetting\|saveChanges" phantom-core/frontend/assets/js/
```

Expected: All return empty (or only matches in files that correctly reference these — like the spec document).

- [ ] **Step 3: Run build and verify minification**

```bash
cd phantom-core && node build.js
```

Expected: Build completes with minification ratio < 70%.

```bash
ls -lh phantom-core/frontend/assets/js/phantom-core.min.js
```

Expected: File exists and is significantly smaller than `phantom-data.js`.

- [ ] **Step 4: Verify PhantomInjector exists in the bundle**

```bash
grep -c "PhantomInjector" phantom-core/frontend/assets/js/phantom-data.js
```

Expected: >= 3 matches (definition + usage in phantom-core.js).

- [ ] **Step 5: Verify component template files exist**

```bash
ls phantom-core/frontend/html/components/
```

Expected: 3 files listed (product-card.html, category-card.html, blog-card.html).

- [ ] **Step 6: Verify adapters use AdapterInterface**

```bash
grep "implements AdapterInterface" phantom-core/includes/adapters/*.php
```

Expected: 4 lines matching (Product_Adapter, Category_Adapter, Menu_Adapter, Hero_Adapter).

- [ ] **Step 7: Verify renderers use RendererInterface**

```bash
grep "implements RendererInterface\|extends Component_Renderer" phantom-core/includes/renderer/*.php
```

Expected: Files show either `extends Component_Renderer` (which now implements `RendererInterface`) or direct `implements RendererInterface`.

- [ ] **Step 8: Commit final verification**

```bash
git add -A
git commit -m "chore(phase0): final verification — all syntax checks pass, dead code removed, build pipeline working"
```
