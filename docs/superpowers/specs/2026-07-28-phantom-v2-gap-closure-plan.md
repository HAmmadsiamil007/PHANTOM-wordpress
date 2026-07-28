# Phantom Core v2 — Enterprise Gap Closure Plan

**Version**: 1.0  
**Date**: 2026-07-28  
**Target**: v2.0 — Full Component Renderer pipeline, Complete Data Layer, Enterprise Compatibility, 3 Template Packs  
**Current baseline**: v1.5.4 — Working Component Renderer pattern, 10 Adapters, 6 ViewModels, 49 REST routes, 22 SPA templates  

---

## Architecture Vision

```
Current (v1.5.4)                               Target (v2.0)
┌─────────────┐    ┌──────────────────┐       ┌─────────────┐    ┌──────────────────────┐
│ 22 SPA HTML  │───▶│ WooCommerce_     │       │ 22 SPA HTML  │───▶│ WooCommerce_Injector │
│ Templates    │    │ Injector         │       │ Templates    │    │ (13/13 mapped)      │
└─────────────┘    │ (8/13 mapped,     │       └─────────────┘    └──────────────────────┘
                   │  3 use do_shortcode│       └─────────────┘    └──────────────────────┘
                   └──────────────────┘                                │
                           │                                  ┌─────────┼──────────────┐
                           ▼                                  ▼         ▼              ▼
                   ┌──────────────────┐              ┌──────────┐ ┌──────────┐ ┌──────────┐
                   │ do_shortcode()    │              │Component │ │ Adapter  │ │ViewModel │
                   │ (fallback =       │              │Renderer  │ │  Layer   │ │  Layer   │
                   │  cards break)     │              │  (20/20)  │ │  (15/15)  │ │  (11/11)  │
                   └──────────────────┘              └──────────┘ └──────────┘ └──────────┘
```

**The gap is wiring completeness.** Every architectural pattern exists. Coverage is missing.

---

## Phase Structure

Each phase follows the **Loop Engineering Protocol**:

```
IMPLEMENT → REVIEW (Level 3: Specialist Agents) → IMPROVE → VERIFY (Level 4: Tool Feedback)
  → EXIT CHECK (all scores ≥ 95, all tests pass) → NEXT PHASE
```

**Quality gate**: After every phase, aggregate score must reach **100/100** before proceeding.

---

## PHASE 0 — Build Pipeline & Scaffolding

### Goal
Establish the build infrastructure and scaffolding so all subsequent phases can be implemented and verified efficiently.

### Files to Create

#### 1. SCSS Build Pipeline — `build-css.js`
```
Purpose: Compile SCSS → CSS with autoprefixer, sourcemaps, minification
Dependencies: sass, postcss, autoprefixer, cssnano
Input:  frontend/scss/*.scss, phantom-theme/scss/*.scss
Output: frontend/assets/css/*.css (minified), phantom-theme/assets/css/*.css
```

Key features:
- Watch mode for development
- Sourcemaps in dev, none in production
- Autoprefixer targeting "last 2 versions"
- cssnano for minification
- Generates .map files alongside .css

#### 2. Component Scaffold Generator — `bin/generate-component.js`
```
Purpose: Generate a complete component scaffold (renderer + template + test)
Usage:   node bin/generate-component.js ProductCard
Creates:
  includes/renderer/class-{name}.php         ← extends Component_Renderer
  frontend/html/components/{name}.html        ← {{PLACEHOLDER}} template
  tests/renderer/{Name}Test.php               ← extends TestBase
```

Template for renderer (follows existing `Product_Card` / `Blog_Card` pattern):
```php
<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class {Name} extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('{slug}') ?: $this->default_template();
  }

  public function render(array $data): string {
    return $this->inject($this->template, [
      // 'placeholder' => esc_html($data['key']),
    ]);
  }

  private function default_template(): string {
    return '<div class="{slug}">
      <!-- {{PLACEHOLDER}} matches lowercase key in data array -->
    </div>';
  }
}
```

#### 3. `package.json` Updates
```json
{
  "scripts": {
    "build:css": "node build-css.js",
    "build:css:watch": "node build-css.js --watch",
    "build:js": "node build.js",
    "build": "npm run build:css && npm run build:js",
    "watch": "concurrently \"npm run build:css:watch\" \"npm run build:js -- --watch\""
  },
  "devDependencies": {
    "sass": "^1.77.0",
    "postcss": "^8.4.0",
    "autoprefixer": "^10.4.0",
    "cssnano": "^7.0.0",
    "concurrently": "^8.2.0"
  }
}
```

#### 4. Test Scaffold — `tests/renderer/ComponentRendererTestBase.php`
Abstract test base class that all renderer tests extend:
```php
abstract class Component_Renderer_Test_Base extends WP_UnitTestCase {
    protected function assert_placeholder_replaced(string $output, string $placeholder): void {
        // Verify {{PLACEHOLDER}} is NOT in output (was replaced)
    }
    protected function assert_default_applied(string $output, string $key, $expected): void {
        // Verify default value appears in rendered output
    }
    protected function assert_missing_placeholder_throws(string $template, string $placeholder): void {
        // Verify missing placeholder returns original template unchanged
    }
}
```

### Implementation Steps
1. Create `build-css.js` with full SCSS pipeline
2. Create `bin/generate-component.js` scaffold
3. Update `package.json` with new dependencies
4. Install dependencies
5. Create test base class
6. Create initial SCSS directory structure
7. Convert existing CSS files to SCSS entry points

### Verification
```
✓ npm run build produces no errors (both CSS + JS)
✓ npm run build:css -- --watch watches file changes
✓ bin/generate-component.js ProductCard creates 3 files
✓ phpunit --filter ComponentRendererTestBase passes
✓ Sourcemaps present in dev build, absent in prod
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | eslint on build scripts, php -l on test base |
| Security | ≥95 | No eval, no shell injection in build scripts |
| Performance | ≥95 | Build takes <5s, output <100KB |
| **Aggregate** | **≥95** | |

---

## PHASE 1 — Component Templates & Renderers (Core Fix)

### Goal
Create all 14 missing component HTML templates and their corresponding PHP renderers, then wire them into a new dispatch table in `WooCommerce_Injector`.

### Gap Analysis
**Existing (6):**
| Template | Renderer | Status |
|----------|----------|--------|
| product-card.html | class-product-card.php | ✅ Working |
| blog-card.html | class-blog-card.php | ❌ Not wired |
| category-card.html | class-category-card.php | ✅ Working |
| hero.html | class-hero.php | ✅ Working |
| footer.html | class-footer.php | ✅ Working |
| (header) | Not HTML | ⚠️ PHP directly |

**Missing (14):**
| # | Component | Template Path | Renderer Class |
|---|-----------|---------------|----------------|
| 1 | cart-item.html | frontend/html/components/cart-item.html | class-cart-item.php |
| 2 | checkout-item.html | frontend/html/components/checkout-item.html | class-checkout-item.php |
| 3 | checkout-form.html | frontend/html/components/checkout-form.html | class-checkout-form.php |
| 4 | order-card.html | frontend/html/components/order-card.html | class-order-card.php |
| 5 | order-table.html | frontend/html/components/order-table.html | class-order-table.php |
| 6 | account-detail.html | frontend/html/components/account-detail.html | class-account-detail.php |
| 7 | account-form.html | frontend/html/components/account-form.html | class-account-form.php |
| 8 | address-card.html | frontend/html/components/address-card.html | class-address-card.php |
| 9 | address-form.html | frontend/html/components/address-form.html | class-address-form.php |
| 10 | post-card.html | frontend/html/components/post-card.html | class-post-card.php |
| 11 | post-content.html | frontend/html/components/post-content.html | class-post-content.php |
| 12 | comment-card.html | frontend/html/components/comment-card.html | class-comment-card.php |
| 13 | search-card.html | frontend/html/components/search-card.html | class-search-card.php |
| 14 | nav-menu.html | frontend/html/components/nav-menu.html | class-nav-menu.php |

### Component Template Pattern
Every HTML template follows the exact same structure:

```html
<!-- /frontend/html/components/product-card.html -->
<article class="product-card" data-product-id="{{PRODUCT_ID}}">
  <figure class="product-card__image">
    <img src="{{IMAGE_URL}}" alt="{{TITLE}}" loading="lazy" width="{{IMAGE_WIDTH}}" height="{{IMAGE_HEIGHT}}">
    <span class="product-card__badge {{BADGE_CLASS}}">{{BADGE_TEXT}}</span>
  </figure>
  <div class="product-card__body">
    <h3 class="product-card__title">
      <a href="{{PERMALINK}}">{{TITLE}}</a>
    </h3>
    <div class="product-card__price">{{PRICE_HTML}}</div>
    <div class="product-card__rating">
      <span class="stars">{{STARS_HTML}}</span>
      <span class="count">({{REVIEW_COUNT}})</span>
    </div>
    <p class="product-card__excerpt">{{EXCERPT}}</p>
    <a href="{{PERMALINK}}" class="btn btn-primary product-card__cta">
      {{CTA_TEXT}}
    </a>
  </div>
</article>
```

### Renderer Pattern (Must Match Existing Convention)
Every renderer follows the EXACT pattern established by `Product_Card`, `Blog_Card`, and `Category_Card`:

```php
<?php
declare(strict_types=1);

namespace PhantomCore\Renderer;

defined('ABSPATH') || exit;

class Cart_Item extends Component_Renderer {

  private string $template;

  public function __construct() {
    $this->template = $this->load_template('cart-item') ?: $this->default_template();
  }

  public function render(array $data): string {
    // Build HTML fragments for computed values
    $qty_input = '<input type="number" value="' . (int) $data['quantity'] . '" min="1" class="cart-qty-input">';
    $remove_btn = '<a href="' . esc_url($data['remove_url']) . '" class="cart-remove" aria-label="Remove item"><i class="fas fa-times"></i></a>';

    return $this->inject($this->template, [
      'item_key'   => esc_attr($data['item_key']),
      'image_url'  => esc_url($data['image_url']),
      'title'      => esc_html($data['title']),
      'permalink'  => esc_url($data['permalink']),
      'price'      => wp_kses_post($data['price']),
      'quantity'   => $qty_input,
      'subtotal'   => wp_kses_post($data['subtotal']),
      'remove_url' => esc_url($data['remove_url']),
    ]);
  }

  private function default_template(): string {
    return '<tr class="cart-item" data-key="{{ITEM_KEY}}">
      <td class="cart-item-image"><img src="{{IMAGE_URL}}" alt="{{TITLE}}" loading="lazy"></td>
      <td class="cart-item-name"><a href="{{PERMALINK}}">{{TITLE}}</a></td>
      <td class="cart-item-price">{{PRICE}}</td>
      <td class="cart-item-qty">{{QUANTITY}}</td>
      <td class="cart-item-subtotal">{{SUBTOTAL}}</td>
      <td class="cart-item-remove">{{REMOVE_URL}}</td>
    </tr>';
  }
}
```

Key pattern rules:
- **Namespace**: `PhantomCore\Renderer\` (all renderers)
- **Class naming**: `Cart_Item` → filename `class-cart-item.php`
- **Template loading**: In constructor via `$this->load_template('name')` with inline fallback
- **Rendering**: `render(array $data): string` — build computed HTML, then `$this->inject($template, $data)`
- **Placeholder matching**: `inject()` runs `strtolower()` on `{{PLACEHOLDER}}` — `{{TITLE}}` matches `$data['title']`
- **No get_name()**: Renderers don't need this method — the Component_Registry entry maps the name

### Component Registration (Must Add in Component_Registry)
Every new renderer must be registered in `Component_Registry::register_defaults()` with a full metadata array:

```php
// In class-component-registry.php :: register_defaults()
'cart_item' => [
    'name'      => 'cart_item',
    'label'     => 'Cart Item',
    'category'  => 'shop',
    'class_name'=> 'PhantomCore\\Renderer\\Cart_Item',
    'version'   => '1.0.0',
    'author'    => 'Phantom Core',
    'description' => 'Single cart item row with image, title, price, quantity, subtotal.',
    'dependencies' => ['phantom-core'],
    'required_features' => ['woocommerce'],
],
```

The `class_name` maps to the fully qualified class. `Component::instance()` does `new $class_name()` to instantiate. The registry key (`cart_item`) is what `WooCommerce_Injector` uses in `Component_Registry::get_instance()->get('cart_item')`.

### Blog_Card Registration Fix
`Blog_Card` class already exists at `PhantomCore\Renderer\Blog_Card` but is **NOT registered** in `register_defaults()`. Must add:

```php
'blog_card' => [
    'name'      => 'blog_card',
    'label'     => 'Blog Card',
    'category'  => 'content',
    'class_name'=> 'PhantomCore\\Renderer\\Blog_Card',
    // ...
],
```

### WooCommerce_Injector Dispatch Table Refactor

The current `WooCommerce_Injector` has a `render_page()` method with a `switch` statement. Refactor to a **dispatch table**:

```php
private function get_dispatch_table(): array {
    return [
        'shop'        => ['renderer' => 'Product_Card',   'template' => 'shop'],
        'product'     => ['renderer' => null,              'template' => 'product'], // special: uses WC
        'cart'        => ['renderer' => 'Cart_Item',       'template' => 'cart'],
        'checkout'    => ['renderer' => 'Checkout_Form',   'template' => 'checkout'],
        'account'     => ['renderer' => 'Account_Detail',  'template' => 'account'],
        'orders'      => ['renderer' => 'Order_Card',      'template' => 'orders'],
        'order-detail'=> ['renderer' => 'Order_Table',     'template' => 'order-detail'],
        'category'    => ['renderer' => 'Category_Card',   'template' => 'category'],
        'blog'        => ['renderer' => 'Blog_Card',       'template' => 'blog'],
        'post'        => ['renderer' => 'Post_Card',       'template' => 'post'],
        'search'      => ['renderer' => 'Search_Card',     'template' => 'search'],
        'home'        => ['renderer' => null,              'template' => 'home'],
    ];
}
```

Each dispatch entry maps to:
1. **renderer**: The component renderer class suffix (or null for special handling)
2. **template**: The SPA HTML shell template slug

### Blog Card Wiring Fix
The existing `class-blog-card.php` is registered but `WooCommerce_Injector::inject_blog_content()` is **never called**. Fix:
1. Add `'blog' => ['renderer' => 'Blog_Card', 'template' => 'blog']` to dispatch table
2. `inject_blog_content()` calls `Component_Registry::render('Blog_Card', $args)` for each post
3. Post list flows through the component pipeline instead of raw `do_shortcode`

### Nav Menu Wiring Fix
The `Menu_Adapter` exists but menus are hardcoded in HTML templates. Fix:
1. Create `nav-menu.html` with {{MENU_ITEMS}} placeholder
2. Create `class-nav-menu.php` renderer
3. `Menu_Adapter::get_menu_items()` feeds the renderer
4. `Render_Engine` injects nav menus into all shell templates

### Files to Create (28)
```
frontend/html/components/cart-item.html
frontend/html/components/checkout-item.html
frontend/html/components/checkout-form.html
frontend/html/components/order-card.html
frontend/html/components/order-table.html
frontend/html/components/account-detail.html
frontend/html/components/account-form.html
frontend/html/components/address-card.html
frontend/html/components/address-form.html
frontend/html/components/post-card.html
frontend/html/components/post-content.html
frontend/html/components/comment-card.html
frontend/html/components/search-card.html
frontend/html/components/nav-menu.html
includes/renderer/class-cart-item.php
includes/renderer/class-checkout-item.php
includes/renderer/class-checkout-form.php
includes/renderer/class-order-card.php
includes/renderer/class-order-table.php
includes/renderer/class-account-detail.php
includes/renderer/class-account-form.php
includes/renderer/class-address-card.php
includes/renderer/class-address-form.php
includes/renderer/class-post-card.php
includes/renderer/class-post-content.php
includes/renderer/class-comment-card.php
includes/renderer/class-search-card.php
includes/renderer/class-nav-menu.php
```

### Files to Modify (2)
```
includes/Engine/WooCommerce_Injector.php  ← dispatch table, inject_blog_content
includes/Engine/Render_Engine.php         ← nav menu injection
```

### Verification
```
✓ php -l on all 28 new files (14 HTML + 14 PHP)
✓ phpunit --filter test_render passes for each of 14 renderers
✓ Blog listing renders Blog_Card instead of do_shortcode
✓ Nav menus render via Menu_Adapter + Nav_Menu renderer
✓ Cart page renders Cart_Item components
✓ Checkout page renders Checkout_Form component
✓ Account page renders Account_Detail component
✓ All 22 SPA templates load without errors
✓ No do_shortcode fallback warnings in debug log
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | php -l all files, eslint JS templates |
| Security | ≥95 | XSS audit on all {{PLACEHOLDER}} outputs |
| Performance | ≥95 | Render time <50ms per component instance |
| UI/UX | ≥95 | All templates match premium design spec |
| **Aggregate** | **≥100** | Loop-engineering Level 4 validation |

---

## PHASE 2 — Adapters & ViewModels (Data Layer)

### Goal
Complete the data layer by creating 5 missing adapters and 5 missing viewmodels, ensuring every data entity has a canonical adapter → viewmodel → renderer pipeline.

### Current Inventory
**Existing Adapters (10):** Product, Post, Page, User, Footer, Settings, Cart, Category, Hero, Menu

**Missing ViewModels (5):**
| ViewModel | Has Adapter? | Purpose |
|-----------|-------------|---------|
| Order_ViewModel | ❌ Needs Order_Adapter | Maps WC_Order → normalized order data |
| Coupon_ViewModel | ❌ Needs Coupon_Adapter | Maps WC_Coupon → normalized coupon data |
| Comment_ViewModel | ❌ Needs Comment_Adapter | Maps WP_Comment → normalized comment data |
| Tag_ViewModel | ❌ Needs Tag_Adapter | Maps WP_Term (tag) → normalized tag data |
| SearchResult_ViewModel | ❌ Needs SearchResult_Adapter | Maps mixed WP/WC results → normalized search result |

### MVP Decision
**Adapters + ViewModels are created together** — each adapter feeds exactly one ViewModel. No orphan adapters, no viewmodels without data sources.

### Implementation Steps

1. **Create `Order_Adapter`** — Follow existing `Product_Adapter` pattern:
```php
<?php
declare(strict_types=1);

namespace PhantomCore\Adapters;

use PhantomCore\Contracts\AdapterInterface;

defined('ABSPATH') || exit;

class Order_Adapter implements AdapterInterface {
    public function normalize($input = null): array {
        // Input: WC_Order, order ID, or order data array
        // Output: ['id', 'status', 'total', 'subtotal', 'tax_total',
        //          'shipping_total', 'currency', 'date_created', 'date_modified',
        //          'line_items', 'shipping_address', 'billing_address',
        //          'payment_method', 'customer_note', 'coupon_lines']
        // Returns $this->empty() for invalid input
    }
    public function normalize_collection(array $inputs): array {
        return array_map([$this, 'normalize'], $inputs);
    }
    private function empty(): array { ... }
}
```

**IMPORTANT**: All adapters implement `AdapterInterface` which requires `normalize($input = null): array` and `normalize_collection(array $inputs): array`. The method is `normalize()`, NOT `adapt()`.

2. **Create `Coupon_Adapter`**
```php
class Coupon_Adapter implements AdapterInterface {
    public function normalize($input = null): array {
        // Input: WC_Coupon or coupon ID/code
        // Output: ['id', 'code', 'description', 'discount_type', 'amount',
        //          'minimum_amount', 'maximum_amount', 'expiry_date',
        //          'product_ids', 'excluded_product_ids', 'usage_limit',
        //          'usage_count', 'free_shipping', 'individual_use']
    }
}
```

3. **Create `Comment_Adapter`**
```php
class Comment_Adapter implements AdapterInterface {
    public function normalize($input = null): array {
        // Input: WP_Comment or comment ID
        // Output: ['id', 'post_id', 'author_name', 'author_email', 'author_url',
        //          'content', 'date', 'status', 'parent', 'avatar_url']
    }
}
```

4. **Create `Tag_Adapter`**
```php
class Tag_Adapter implements AdapterInterface {
    public function normalize($input = null): array {
        // Input: WP_Term (tag taxonomy) or term ID
        // Output: ['id', 'name', 'slug', 'description', 'count', 'link',
        //          'term_group', 'posts']
    }
}
```

5. **Create `SearchResult_Adapter`**
```php
class SearchResult_Adapter implements AdapterInterface {
    public function normalize($input = null): array {
        // Input: WP_Post or WC_Product
        // Output: ['type', 'id', 'title', 'excerpt', 'permalink', 'image_url',
        //          'price' (if product), 'date' (if post), 'score']
    }
}
```

6. **Create corresponding ViewModels** following the `Product_ViewModel` pattern:
```php
final class Order_ViewModel implements ViewModelInterface {
    // ViewModelInterface is a marker interface (no required methods)

    public static function from_adapter_output(array $data): self { ... }
    public function to_array(): array { ... }
    public function formatted_status(): string { ... }   // computed helpers
    public function formatted_total(): string { ... }
}
```

Each ViewModel uses `from_adapter_output()` static factory (not constructor) and provides `to_array()` for the renderer pipeline. Additional computed helpers (`formatted_price()`, `rating_stars()`, etc.) are per-ViewModel.

7. **Adapters are instantiated directly** (NOT registered in Container_Config):
```php
// In WooCommerce_Injector or wherever needed:
$this->order_adapter = new Order_Adapter();
```
Following the existing pattern: `Product_Adapter`, `Category_Adapter`, `Hero_Adapter` are all `new`'d inline. Only `Data_Normalizer` (a utility) is in the container. Do NOT register adapters in Container_Config.

8. **Update autoloader** in `phantom-core.php` if new namespace directories are needed.

### Files to Create (10)
```
includes/adapters/class-order-adapter.php
includes/adapters/class-coupon-adapter.php
includes/adapters/class-comment-adapter.php
includes/adapters/class-tag-adapter.php
includes/adapters/class-searchresult-adapter.php
includes/ViewModels/class-order-viewmodel.php
includes/ViewModels/class-coupon-viewmodel.php
includes/ViewModels/class-comment-viewmodel.php
includes/ViewModels/class-tag-viewmodel.php
includes/ViewModels/class-searchresult-viewmodel.php
```

### Files to Modify (1)
```
phantom-core.php  ← autoloader namespaces if needed
```

**Note**: Adapters are NOT registered in Container_Config. They follow the existing pattern of direct instantiation (`new Order_Adapter()`) in `WooCommerce_Injector` and other consumers. ViewModels are also NOT registered — they use `from_adapter_output()` static factories.

### Verification
```
✓ php -l on all 10 new files
✓ phpunit --filter test_normalize passes for each adapter
✓ phpunit --filter test_viewmodel passes for each viewmodel
✓ Order_Adapter::normalize() handles valid WC_Order
✓ Order_Adapter::normalize() handles invalid input → empty()
✓ SearchResult_Adapter::normalize() handles both WP_Post and WC_Product
✓ php -l on modified phantom-core.php autoloader
✓ ViewModel::from_adapter_output() creates valid instance from adapter data
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | php -l, phpunit, DRY check against existing adapters |
| Security | ≥95 | Input validation, output escaping, no SQL injection |
| Performance | ≥95 | Adapter completes in <10ms for all data sizes |
| **Aggregate** | **≥100** | Loop-engineering Level 4 validation |

---

## PHASE 3 — WooCommerce Injector Full Dispatch + Test Coverage

### Goal
Rewrite `WooCommerce_Injector` to use the full dispatch table, map ALL 14 page types to their component renderers, eliminate ALL `do_shortcode` fallbacks, and achieve comprehensive test coverage.

### Current vs Target Dispatch Table

| Route | Current Behavior | Target |
|-------|-----------------|--------|
| /shop | Product_Card renderer (working) | ✅ Same |
| /product/{slug} | [placeholder] tokens (inconsistent) | → Component pipeline |
| /cart | do_shortcode('[woocommerce_cart]') [line 369] | → Cart_Item renderer |
| /checkout | do_shortcode('[woocommerce_checkout]') [line 377] | → Checkout_Form renderer |
| /my-account | do_shortcode('[woocommerce_my_account]') [line 385] | → Account_Detail renderer |
| /category/{slug} | Category_Card renderer (working) | ✅ Same |
| /blog | Route not handled (missing from switch) | → Blog_Card renderer |
| /post/{slug} | Route not handled (missing from switch) | → Post_Card + Post_Content renderers |
| /search | Route not handled (missing from switch) | → Search_Card renderer |
| /orders | Route not handled (missing from switch) | → Order_Card renderer |
| /order/{id} | Route not handled (missing from switch) | → Order_Table renderer |
| / | Featured products + blog (working) | ✅ Keep + enhance dispatch |

### Implementation Steps

1. **Extend `WooCommerce_Injector::inject()` switch statement**
   The existing `inject()` uses `switch(true)` with pattern matching. Add 5 new route cases and replace 3 `do_shortcode()` calls:

```php
switch (true) {
    // EXISTING (keep as-is):
    case 'shop' === $slug:
    case strpos($slug, 'category/') === 0:
        $html = $this->inject_shop_content($html);      // already uses Product_Card + Category_Card ✅
        break;
    case 'product' === $slug:
    case 'product-detail' === $slug:
    case strpos($slug, 'product/') === 0:
        $html = $this->inject_product_content($html);    // already uses Product_Adapter + ViewModel ✅
        break;
    case '' === $slug:
    case 'index' === $slug:
        $html = $this->inject_homepage_products($html);  // already uses Product_Card ✅
        $html = $this->inject_homepage_categories($html);// already uses Category_Card ✅
        break;
    case 'wishlist' === $slug:                           // already works ✅
        $html = $this->inject_wishlist_content($html);
        break;

    // FIX: Replace do_shortcode with Component Renderer pipeline:
    case 'cart' === $slug:
        $html = $this->inject_cart_content($html);       // was do_shortcode → now Cart_Item renderer
        break;
    case 'checkout' === $slug:
        $html = $this->inject_checkout_content($html);   // was do_shortcode → now Checkout_Form renderer
        break;
    case 'account' === $slug:
    case 'my-account' === $slug:
        $html = $this->inject_account_content($html);    // was do_shortcode → now Account_Detail renderer
        break;

    // NEW: Add missing route handlers:
    case strpos($slug, 'orders') === 0:
        $html = $this->inject_orders_content($html);     // Order_Card renderer
        break;
    case strpos($slug, 'order/') === 0:
        $html = $this->inject_order_detail_content($html);// Order_Table renderer
        break;
    case 'blog' === $slug:
        $html = $this->inject_blog_content($html);       // Blog_Card renderer (EXISTS, needs wiring)
        break;
    case strpos($slug, 'post/') === 0:
        $html = $this->inject_post_content($html);       // Post_Card + Post_Content renderers
        break;
    case 'search' === $slug:
        $html = $this->inject_search_content($html);     // Search_Card renderer
        break;
}
```

3. **Fix [placeholder] → {{PLACEHOLDER}} transition for product detail**
   - Product detail currently uses inconsistent `[product_id]` bracket tokens
   - Convert to `{{PRODUCT_ID}}` format for component renderer compatibility
   - Ensure backward compatibility check (log warning if bracket tokens detected)

4. **Write comprehensive unit tests:**
```php
class WooCommerceInjectorTest extends WP_UnitTestCase {
    public function test_dispatch_table_has_all_routes(): void { ... }
    public function test_each_dispatch_entry_has_valid_renderer(): void { ... }
    public function test_each_dispatch_entry_has_valid_template(): void { ... }
    public function test_render_page_shop_returns_200(): void { ... }
    public function test_render_page_cart_injects_cart_items(): void { ... }
    public function test_render_page_checkout_injects_form(): void { ... }
    public function test_render_page_blog_injects_blog_cards(): void { ... }
    public function test_render_page_search_injects_search_cards(): void { ... }
    public function test_render_page_account_injects_account_detail(): void { ... }
    public function test_invalid_route_returns_404(): void { ... }
    public function test_no_do_shortcode_for_mapped_routes(): void { ... }
}
```

5. **Debug log monitoring**: After deployment, verify `error_log` has ZERO entries related to `do_shortcode` fallbacks.

### Files to Modify (3)
```
includes/Engine/WooCommerce_Injector.php  ← Full refactor
includes/Engine/Render_Engine.php         ← Support new dispatch table
includes/contracts/interface-renderer.php ← Verify interface completeness
```

### Files to Create (1)
```
tests/engine/WooCommerceInjectorTest.php
```

### Verification
```
✓ php -l on all modified files
✓ phpunit --filter WooCommerceInjectorTest passes (14+ tests)
✓ No do_shortcode() calls remain in WooCommerce_Injector for mapped routes
✓ Every route returns HTTP 200 with proper content type
✓ Blog listing renders Blog_Card components for each post
✓ Cart page renders Cart_Item components for each item
✓ Empty cart shows proper empty state
✓ Checkout form renders with all fields
✓ Account page shows orders list with Order_Card components
✓ Order detail page shows Order_Table component
✓ Orders page renders Order_Card components
✓ Product detail uses {{PRODUCT_ID}} pattern (no [product_id] bracket tokens)
✓ Post content renders Post_Card + Post_Content components
✓ Search results render Search_Card components
✓ Debug log: 0 entries after full page crawl
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | php -l, phpunit, no dead code |
| Security | ≥95 | All user input escaped, no direct SQL |
| Performance | ≥95 | Page render <200ms, inject <50ms |
| UI/UX | ≥95 | All states: loading, empty, error, edge |
| **Aggregate** | **≥100** | Loop-engineering Level 4 validation |

---

## PHASE 4 — Compatibility Layer

### Goal
Build enterprise-grade compatibility bridges for Gutenberg, Elementor, WPML, RankMath, Yoast, and Contact Form 7 — making Phantom Core truly decoupled and framework-agnostic.

### Architecture Pattern
Each compatibility module follows the **Bridge** pattern already established:

```php
class Gutenberg_Bridge extends Plugin_Bridge {
    protected function register_hooks(): void {
        // Gutenberg-specific hooks and filters
    }
    public function is_active(): bool {
        return function_exists('register_block_type');
    }
}
```

### 1. Gutenberg Compatibility — `includes/Compatibility/class-gutenberg-bridge.php`
```
- Parse Gutenberg blocks and render them inside SPA templates
- Expose block content via REST API `phantom/v1/post/{id}/blocks`
- Gutenberg block data → Post_Adapter → Post_ViewModel → Post_Content renderer
- Handle: core/paragraph, core/heading, core/image, core/gallery, core/columns
- Fallback: render_blocks() → HTML for blocks not supported
```

### 2. Elementor Compatibility — `includes/Compatibility/class-elementor-bridge.php`
```
- Detect Elementor-built pages
- Render via Elementor's render engine when Elementor is active
- Fallback to Component Renderer when Elementor is not active
- Elementor CSS/JS enqueue management
- SSR support for Elementor content in SPA shell
```

### 3. WPML Compatibility — `includes/Compatibility/class-wpml-bridge.php`
```
- Language switcher integration
- Translated URLs for all 22 SPA routes
- WPML → REST API language parameter passthrough
- Translated menu support
- Home URL / Page ID translation mapping
```

### 4. RankMath SEO — `includes/Compatibility/class-rankmath-bridge.php`
```
- Inject RankMath meta tags into SPA shell <head>
- Open Graph / Twitter Card tag passthrough
- Schema.org JSON-LD passthrough
- Breadcrumb data via REST API
- Focus keyword → REST API filter
```

### 5. Yoast SEO — `includes/Compatibility/class-yoast-bridge.php`
```
- Inject Yoast meta tags into SPA shell <head>
- Open Graph / Twitter Card tag passthrough
- Schema.org JSON-LD passthrough
- Breadcrumb data via REST API
- Sitemap compatibility
``` 

**Decision: RankMath takes priority for active development; Yoast bridge provides baseline compatibility.**

### 6. Contact Form 7 — `includes/Compatibility/class-cf7-bridge.php`
```
- Detect CF7 forms in post content
- Render CF7 shortcodes inside SPA templates via REST API
- Form submission endpoint passthrough
- CF7 styling enqueue management
```

### Files to Create (6)
```
includes/Compatibility/class-gutenberg-bridge.php
includes/Compatibility/class-elementor-bridge.php
includes/Compatibility/class-wpml-bridge.php
includes/Compatibility/class-rankmath-bridge.php
includes/Compatibility/class-yoast-bridge.php
includes/Compatibility/class-cf7-bridge.php
```

### Files to Modify (4)
```
includes/Engine/Container_Config.php       ← Register all 6 bridges
includes/Engine/Bridge_Manager.php         ← Auto-detect + init active bridges
phantom-core.php                           ← Autoloader namespace for Compatibility\
tests/compatibility/                       ← Test stubs for each bridge
```

### Implementation Steps
1. Create `includes/Compatibility/` directory
2. Implement each bridge following the existing `Plugin_Bridge` pattern
3. Register all 6 in `Container_Config`
4. Update `Bridge_Manager` to auto-detect and initialize active bridges
5. Write unit tests for each bridge
6. Integration test: activate each plugin alongside Phantom Core, verify no breakage

### Verification
```
✓ php -l on all 6 new files
✓ phpunit --filter test_bridge passes for each bridge
✓ Gutenberg: Block content renders through Post_Content renderer
✓ Elementor: Elementor-built pages render correctly
✓ WPML: Language switcher and translated routes work
✓ RankMath: OG/Twitter/Schema meta tags in SPA shell <head>
✓ Yoast: OG/Twitter/Schema meta tags in SPA shell <head>
✓ CF7: Contact forms render and submit correctly
✓ All bridges + Phantom Core: zero PHP notices/warnings
✓ Bridge_Manager auto-detects active plugins correctly
✓ Inactive plugins produce no errors
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | php -l, phpunit, each bridge <200 lines |
| Security | ≥95 | XSS audit on meta tag output, form handling |
| Performance | ≥95 | Inactive bridges add <1ms overhead |
| Compatibility | ≥95 | All 6 plugins + Phantom Core = 0 errors |
| **Aggregate** | **≥100** | Loop-engineering Level 3 + Level 4 validation |

---

## PHASE 5 — Template Packs & E2E Validation

### Goal
Create 3 additional template packs (Dark, Minimal, Bold) with demo content generators, a theme activation wizard, and comprehensive E2E validation to certify 100/100 delivery readiness.

### 1. Template Pack Architecture
The existing `Template_Loader::set_pack()` method provides the hook point. Each pack is a directory:

```
frontend/packs/dark/
frontend/packs/dark/html/       ← Override any or all 22 templates
frontend/packs/dark/scss/       ← Pack-specific SCSS (colors, fonts)
frontend/packs/dark/assets/     ← Pack-specific JS, images
frontend/packs/dark/manifest.json ← Pack metadata

frontend/packs/minimal/
frontend/packs/minimal/html/
frontend/packs/minimal/scss/
frontend/packs/minimal/assets/
frontend/packs/minimal/manifest.json

frontend/packs/bold/
...
```

### 2. Pack Manifest Schema
```json
{
  "name": "Dark",
  "version": "1.0.0",
  "description": "Dark-themed premium template pack",
  "author": "Phantom Core",
  "settings": {
    "primary_color": "#6C63FF",
    "dark_mode": true,
    "font_heading": "Inter",
    "font_body": "Inter"
  },
  "templates": {
    "override_count": 22,
    "base": "frontend/packs/dark/html/"
  },
  "assets": {
    "css": ["frontend/packs/dark/assets/css/pack.css"],
    "js": ["frontend/packs/dark/assets/js/pack.js"]
  }
}
```

### 3. Demo Content Generator — `includes/Setup/class-demo-content-generator.php`
```
- WC_Product generator (20 products across 4 categories)
- WP_Post generator (10 blog posts with featured images)
- WP_Page generator (About, Contact, Privacy, Terms)
- Navigation menu generator (primary + footer)
- Widget configuration for all 7 widget areas
- Homepage configuration (featured products, hero)
- Category/term assignment
- Tags generation
- One-click activation with progress bar
```

### 4. Theme Activation Wizard
```php
class Activation_Wizard {
    public function render(): void {
        // Step 1: Welcome
        // Step 2: Choose template pack (Dark/Minimal/Bold/Classic)
        // Step 3: Choose demo content (Full/Fast/Minimal/None)
        // Step 4: Color customization
        // Step 5: Summary + Activate
    }
}
```

### 5. E2E Validation Suite — `tests/e2e/`

Create comprehensive Docker-based E2E tests:

```bash
tests/e2e/01-health-check.sh          # PHP syntax, WP constants, plugin activated
tests/e2e/02-frontend-pages.sh        # All 22 SPA pages return HTTP 200
tests/e2e/03-rest-api.sh              # All 49 endpoints return valid JSON
tests/e2e/04-woocommerce-flow.sh      # Shop → Product → Cart → Checkout → Order
tests/e2e/05-blog-flow.sh             # Blog → Single Post → Comments
tests/e2e/06-search-flow.sh           # Search → Results → Detail
tests/e2e/07-template-packs.sh        # Switch pack → verify templates load
tests/e2e/08-compatibility.sh         # Activate Gutenberg/Elementor + verify
tests/e2e/09-php-errors.sh            # debug.log must be 0 bytes
tests/e2e/10-performance.sh           # Page load <500ms, queries <50
```

### 6. Full Delivery Checklist

```
□ PHASE 0: Build pipeline, scaffolds, test base
  □ npm run build (CSS + JS) succeeds
  □ Scaffold generator creates working component
  □ Test base class established

□ PHASE 1: 14 component templates + 13 new renderers + Blog_Card registered
  □ All templates follow {{PLACEHOLDER}} pattern (lowercase data keys)
  □ All renderers extend Component_Renderer under PhantomCore\Renderer namespace
  □ All components registered in Component_Registry::register_defaults()
  □ Blog_Card registered: exists as class but was never in register_defaults()
  □ Nav menu wiring: Menu_Adapter feeds Nav_Menu renderer
  □ Cart, checkout, account: replace do_shortcode with component renderers
  □ Blog, post, search, orders, order-detail: add new routes to switch(true)

□ PHASE 2: 5 new adapters + 5 new viewmodels
  □ All implement respective interfaces (normalize() / from_adapter_output())
  □ Data normalization complete for all entities
  □ Follows existing pattern: adapters are new'd inline (not in container)
  □ Autoloader covers new namespaces if needed

□ PHASE 3: Full injector refactor + tests
  □ Dispatch table replaces switch statement
  □ All 14 page types use component pipeline
  □ [placeholder] → {{PLACEHOLDER}} migration complete
  □ 12+ unit tests covering all routes
  □ debug.log: 0 bytes after full crawl

□ PHASE 4: 6 compatibility bridges
  □ Gutenberg block content renders correctly
  □ Elementor page detection + render
  □ WPML language switcher + translated routes
  □ RankMath/Yoast meta tags in SPA shell
  □ CF7 form rendering + submission
  □ Bridge_Manager auto-detects active plugins

□ PHASE 5: 3 template packs + E2E
  □ Dark pack: 22 templates, SCSS, manifest
  □ Minimal pack: 22 templates, SCSS, manifest
  □ Bold pack: 22 templates, SCSS, manifest
  □ Demo content generator: products, posts, pages
  □ Activation wizard: pack selection, content, color
  □ 10 E2E smoke tests all passing
  □ debug.log: 0 bytes

□ FINAL VERIFICATION
  □ php -l on ALL PHP files (0 syntax errors)
  □ phpunit — all tests pass
  □ REST API — all 49+ routes return HTTP 200
  □ Frontend — all 22 SPA pages render without errors
  □ WooCommerce — full shop→checkout flow works
  □ Template packs — all 3 switchable without errors
  □ Compatibility — 0 errors with/without plugins
  □ Performance — all pages <500ms
  □ Debug log — 0 bytes
  □ Serena memory updated
```

### Files to Create (many)
```
frontend/packs/dark/html/*           ← 22 template overrides
frontend/packs/dark/scss/pack.scss   ← Dark theme SCSS
frontend/packs/dark/manifest.json
frontend/packs/minimal/html/*        ← 22 template overrides
frontend/packs/minimal/scss/pack.scss
frontend/packs/minimal/manifest.json
frontend/packs/bold/html/*           ← 22 template overrides
frontend/packs/bold/scss/pack.scss
frontend/packs/bold/manifest.json
includes/Setup/class-demo-content-generator.php
includes/Setup/class-activation-wizard.php
tests/e2e/*                          ← 10 E2E scripts
```

### Files to Modify (3)
```
includes/Engine/Template_Loader.php  ← Pack switching + manifest loading
includes/Engine/Container_Config.php ← Setup services
phantom-core.php                     ← Activation hook + wizard trigger
```

### Quality Gate Criteria
| Domain | Threshold | Verifier |
|--------|-----------|----------|
| Code Quality | ≥95 | php -l all files, phpunit all tests |
| Security | ≥95 | Demo content: no hardcoded secrets, no XSS |
| Performance | ≥95 | Pack switch <1s, all pages <500ms |
| UI/UX | ≥95 | All 3 packs visually distinct and premium |
| SEO | ≥95 | All E2E SEO checks pass |
| **Aggregate** | **≥100** | Full E2E + Loop-engineering Level 4 validation |

---

## Loop Engineering Quality Protocol

Every phase follows this exact cycle:

### Level 3 — Specialist Agent Review (after implementation)
```
┌─────────────────────────────────────────────────────┐
│ GENERATOR: Implement phase (write all files)         │
├─────────────────────────────────────────────────────┤
│ SPECIALIST REVIEWERS (parallel):                     │
│   • Code Quality Agent: php -l, phpunit, DRY check   │
│   • Security Agent: XSS, SQLi, auth, nonce audit     │
│   • Performance Agent: render time, query count      │
│   • UI/UX Agent (Phase 1, 5): template quality       │
│     ↑ Each outputs structured JSON issues report     │
├─────────────────────────────────────────────────────┤
│ AGGREGATOR: Deduplicate, sort by severity            │
│ Output: Unified fix manifest with file:line refs     │
├─────────────────────────────────────────────────────┤
│ IMPROVER: Apply ALL fixes. No skipping. No arguing.  │
├─────────────────────────────────────────────────────┤
│ EXIT CHECK: All domain scores ≥ phase threshold?     │
│   YES → Proceed to Level 4 verification              │
│   NO → Loop back to IMPROVER                         │
└─────────────────────────────────────────────────────┘
```

### Level 4 — Tool Feedback Verification (before sign-off)
```
┌─────────────────────────────────────────────────────┐
│ TOOL EXECUTOR (run all):                             │
│   • php -l on every new/modified file                │
│   • phpunit --filter (phase-specific tests)          │
│   • phpunit --group=renderer (Phase 1)               │
│   • npm run build (Phase 0, 5)                       │
│   • debug.log check (must be 0 bytes)                │
├─────────────────────────────────────────────────────┤
│ ERROR PARSER: Structured failures → fix instructions │
├─────────────────────────────────────────────────────┤
│ IMPROVER: Fix all test failures + lint errors        │
├─────────────────────────────────────────────────────┤
│ RE-RUN: All tools again                              │
├─────────────────────────────────────────────────────┤
│ EXIT: All tools pass + all scores ≥ thresholds       │
│   → Phase complete at 100/100                        │
└─────────────────────────────────────────────────────┘
```

### Scoring Rubric (Applies to All Phases)

| Domain | Weight | Threshold | How Measured |
|--------|--------|-----------|--------------|
| Code Quality | 25% | ≥95 | php -l, phpunit, manual DRY/pattern check |
| Security | 20% | ≥95 | XSS/SQLi/CSRF audit, nonce verification |
| Performance | 20% | ≥95 | Render time, query count, bundle size |
| UI/UX | 20% | ≥95 | States coverage, responsive, accessibility |
| SEO | 15% | ≥85 | Meta tags, structure, semanics (Phase 1,5 only) |
| **Aggregate** | **100%** | **≥100** | **All must pass + debug.log = 0 bytes** |

---

## Serena Memory Update Protocol

After **each phase** completes at 100/100:

1. Record phase completion:
```
mem:phantom-core-v2-phase-N-complete
Content: Phase N delivered at 100/100 on 2026-07-28
Files created: [list]
Files modified: [list]
Tests passing: [N]
Key decisions: [...]
```

2. At **final completion** (all 6 phases, all 100/100):
```
mem:phantom-core-v2-complete
Content: Full v2.0 gap closure certified at 100/100 on 2026-07-28
All 6 phases complete. 
Final stats: [aggregate]
Delivery checklist: all 100/100
```

3. Store the complete plan in Serena:
```
mem:phantom-core-v2-gap-closure-plan
Content: This entire document as a reference
```

---

## Timeline Estimate

| Phase | Est. Implementation | Est. Loop-Engineering | Total |
|-------|-------------------|----------------------|-------|
| Phase 0 | 30 min | 15 min | 45 min |
| Phase 1 | 3 hours | 1 hour | 4 hours |
| Phase 2 | 45 min | 30 min | 1.25 hours |
| Phase 3 | 1 hour | 45 min | 1.75 hours |
| Phase 4 | 2 hours | 1 hour | 3 hours |
| Phase 5 | 4 hours | 2 hours | 6 hours |
| **Total** | **~11.25 hours** | **~5.5 hours** | **~17 hours** |

---

## Risk Register

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| WooCommerce API changes break adapters | Low | High | Adapter pattern isolates WC dependency; unit tests catch breakage |
| Template pack design doesn't match brand | Medium | Medium | Activation wizard preview before commit |
| Compatibility bridge misses plugin version | Medium | Medium | Version detection + graceful degradation |
| Build pipeline breaks on Windows | Low | Medium | Test build on both nix + Windows CI |
| SCSS compilation conflicts with existing CSS | Low | Medium | Namespace all SCSS under `.phantom-` prefix |
| Loop-engineering infinite loop | Medium | High | Max 5 iterations per phase with stagnation detection |

---

## Approval Sign-off

- **Plan Author**: Claude (opencode agent)
- **Plan Approved**: __________________
- **Date**: _______________

---

*End of Plan — Phantom Core v2 Enterprise Gap Closure*
