# Phantom Core — Features / Gaps Analysis

> **Legend:** ✅ Implemented | ⚠️ Partial | ❌ Missing | 🔧 Hardcoded (not setting-controlled)

---

## 1. WordPress Core Features (all use existing WP)

✅ Users, Posts, Pages, Media, Comments, Roles, Customizer, Options API, Menus, Widgets, Permalinks

**All work natively — no phantom-core modifications needed.**

---

## 2. WooCommerce Integration

| Feature | Status | Detail |
|---------|--------|--------|
| Products CRUD | ✅ | REST `/phantom/v1/products` |
| Featured Products | ✅ | REST `/phantom/v1/products/featured` |
| Categories | ✅ | REST `/phantom/v1/categories` |
| Cart display | ✅ | REST `/phantom/v1/cart` + JS inject |
| Add to cart | ✅ | `wc-ajax=add_to_cart` |
| Remove from cart | ✅ | `wc-ajax=remove_from_cart` |
| Quantity update | ✅ | Store API `/wc/store/v1/cart/update-item` |
| Checkout | ✅ | `wc-ajax=checkout` |
| Product Attributes | ❌ | No REST endpoint for attributes |
| Product Variations | ❌ | No REST endpoint for variations |
| Product Reviews | ❌ | No REST endpoint |
| Shipping | ✅ | WC native admin |
| Coupons | ✅ | WC native admin |
| Orders | ✅ | WC native admin |
| Product Gallery | ⚠️ | Via `data-phantom` on gallery element, but variations not supported |

---

## 3. Theme Settings — Actual vs Spec

### Branding

| Feature | Status | Setting Key |
|---------|--------|-------------|
| Site Logo | ✅ | `general_site_logo` |
| Retina Logo | ❌ | Missing |
| Dark Logo | ❌ | Missing |
| Mobile Logo | ❌ | Missing |
| Favicon | ✅ | `general_favicon` |
| Loader Logo | ✅ | `preloader_logo` |
| Site Title | ✅ | WP native (bloginfo) |
| Tagline | ✅ | WP native (bloginfo) |

### Header

| Feature | Status | Detail |
|---------|--------|--------|
| Sticky Header | ✅ | `header_sticky` |
| Transparent Header | ❌ | Missing |
| Header Height | ✅ | `header_height` |
| Header Width | ✅ | `header_layout` |
| Top Bar show/hide | ✅ | `topbar_show` |
| Top Bar content | ✅ | Strings + repeaters (languages, currencies) |
| Search icon | ✅ | `header_search_icon` |
| Cart icon | ✅ | `header_cart_icon` |
| Account icon | ✅ | `header_account_icon` |
| Wishlist icon | ❌ | No setting |
| Compare icon | ❌ | No setting |
| Notification icon | ❌ | No setting |
| Language switcher | ⚠️ | Topbar repeater exists, but no header integration |
| Currency switcher | ⚠️ | Topbar repeater exists, but no header integration |
| Header layout style | ✅ | `header_style` |
| Mega Menu | ❌ | No mega menu support |

### Announcement Bar

| Feature | Status | Detail |
|---------|--------|--------|
| Enable/disable | ✅ | `announcement_bar_enable` |
| Text | ✅ | `announcement_bar_text` |
| Link URL | ❌ | Missing |
| Countdown timer | ❌ | Missing |
| Background color | ✅ | CSS var `--announcement--bg` |
| Text color | ✅ | CSS var `--announcement--text--color` |
| Close button | ❌ | Missing |
| Dismiss cookie | ❌ | Missing |

### Navigation

| Feature | Status | Detail |
|---------|--------|--------|
| Menu style | ✅ | `menu_style` select |
| Dropdown animation | ❌ | Missing |
| Mobile menu style | ✅ | `mobile_menu_style` |
| Menu icons | ❌ | Missing |
| Menu labels/badges | ❌ | Missing |
| Active state styling | 🔧 | CSS-driven |
| Mega Menu | ❌ | Missing |
| Off-canvas menu | ❌ | Missing |

### Hero / Banner

| Feature | Status | Detail |
|--------|--------|--------|
| Title, subtitle, description | ✅ | `home_banner_*` settings |
| Button text & URL | ✅ | `home_banner_btn_text/url` |
| Background image | ✅ | `home_banner_img1/img2` |
| Background video | ❌ | Missing |
| Overlay color & opacity | ✅ | `hero_overlay_color/enable` |
| Full-screen height | ❌ | Missing |
| Animation effects | ❌ | Missing |
| Slider mode | ❌ | Only static images |
| Parallax | ❌ | Missing |
| Content alignment | ❌ | Missing |

### Collections (Home Categories)

| Feature | Status | Detail |
|--------|--------|--------|
| Category grid | ✅ | `home_categories_*` |
| Collection images | ✅ | Repeater with image |
| Collection links | ✅ | Repeater with URL |
| Hover effects | ❌ | Missing |
| Number of items | ✅ | `home_categories_count` |
| Layout style | ❌ | Missing |

### Product Cards

| Feature | Status | Detail |
|--------|--------|--------|
| Card style | ✅ | `product_card_style` |
| Hover effects | ✅ | `product_card_hover_effect` |
| Image ratio | ✅ | `product_card_image_ratio` |
| Quick view | ✅ | `product_card_quick_view` |
| Wishlist button toggle | ❌ | Missing |
| Compare toggle | ❌ | Missing |
| Sale badges | ✅ | `product_card_sale_badge` |
| Featured badges | ✅ | `product_card_featured_badge` |
| Countdown timer | ❌ | Missing |
| Color swatches on cards | ❌ | Missing |
| ATC button style | ✅ | `product_card_atc_style` |

### Shop Page

| Feature | Status | Detail |
|--------|--------|--------|
| Layout (grid/list) | ✅ | `shop_layout` |
| Sidebar position | ✅ | `shop_sidebar` |
| Number of columns | ✅ | `shop_columns` |
| Products per page | ✅ | `shop_per_page` |
| Pagination style | ✅ | `shop_pagination` (numbers/load-more) |
| Infinite scroll | ❌ | Missing |
| Sorting options | ✅ | `shop_sorting` |
| Filter position | ❌ | Missing |
| Category filter style | ❌ | Missing |

### Product Page

| Feature | Status | Detail |
|--------|--------|--------|
| Gallery layout | ✅ | `product_gallery_style` |
| Image zoom | ⚠️ | `product_image_zoom` exists, partial implementation |
| Video support | ❌ | Missing |
| 360-degree viewer | 🔧 | JS function exists in phantom-data.js, no setting |
| Sticky add-to-cart | ❌ | Missing |
| Tab style | ✅ | `product_tab_style` |
| Related products | ✅ | `product_related_count` |
| Upsells | ❌ | Missing |
| Cross-sells | ❌ | Missing |
| Review layout | ✅ | `product_review_layout` |

### Blog

| Feature | Status | Detail |
|--------|--------|--------|
| Layout | ✅ | `blog_layout` |
| Masonry layout | ❌ | Missing |
| Sidebar position | ✅ | `blog_sidebar` |
| Number of columns | ✅ | `blog_columns` |
| Posts per page | ✅ | `blog_per_page` |
| Featured image | ✅ | `blog_show_image` |
| Author display | ✅ | `blog_show_author` |
| Date display | ✅ | `blog_show_date` |
| Reading time | ❌ | Missing |
| Related posts | ⚠️ | `blog_related_posts` count only |
| Excerpt length | ✅ | `blog_excerpt_length` |
| Author bio | ❌ | Missing on single post |

### Footer

| Feature | Status | Detail |
|--------|--------|--------|
| Layout (1-4 columns) | ✅ | `footer_layout` |
| Widget areas | ✅ | `footer_widget_areas` |
| Newsletter signup | ❌ | Missing — no setting/JS |
| Copyright text | ✅ | `footer_copyright_text` |
| Social media icons | ✅ | `footer_social_links` (repeater) |
| Payment method icons | ✅ | `footer_payment_icons` (repeater) |
| Background/style | ✅ | CSS vars `--footer--*` |
| Back-to-top button | ❌ | Missing |
| Instagram feed | ❌ | Missing (JS has `injectInstagramFeed` but no setting) |

### Typography

| Feature | Status | Detail |
|--------|--------|--------|
| Heading font | ✅ | `heading_font_family` |
| Body font | ✅ | `body_font_family` |
| Font weights | ✅ | `heading_font_weight`, `body_font_weight` |
| Base font size | ✅ | `base_font_size` |
| Line height | ✅ | `body_line_height` |
| Letter spacing | ✅ | `letter_spacing` |
| Text case | ✅ | `text_case` |
| Google Fonts dynamic loading | ❌ | Fonts are static, no Google Fonts API integration |
| Font subsets | ❌ | Missing |
| System font fallback | ✅ | Implied via CSS |
| Fluid type scale | ❌ | Missing |

### Colors

| Feature | Status | Detail |
|--------|--------|--------|
| Primary | ✅ | `primary_color`, CSS var `--primary--color` |
| Secondary | ✅ | `secondary_color`, CSS var `--secondary--color` |
| Accent | ✅ | `accent_color`, CSS var `--accent--color` |
| Background | ✅ | `body_bg_color`, CSS var `--body--bg` |
| Header BG | ✅ | `header_bg_color`, CSS var `--header--bg` |
| Footer BG | ✅ | `footer_bg_color`, CSS var `--footer--bg` |
| Text | ✅ | `body_text_color` |
| Heading | ✅ | `heading_color` |
| Links | ✅ | `link_color`, `link_hover_color` |
| Border | ✅ | `border_color` |
| Sale | ✅ | `sale_color` |
| Dark mode | ❌ | No auto-switch or dark mode palette |
| Section-specific colors | ❌ | No per-section color overrides |

### Layout

| Feature | Status | Detail |
|--------|--------|--------|
| Site width (boxed/full) | ✅ | `layout_style`, `boxed_width` |
| Container width | ✅ | `container_width` |
| Content/sidebar ratio | ✅ | `content_width`, `sidebar_width` |
| Section padding | ✅ | `section_padding_y/x` |
| Column count | ✅ | `columns` |
| Container gutter | ✅ | `container_gutter` |

### Responsive

| Feature | Status | Detail |
|--------|--------|--------|
| Breakpoint overrides | ✅ | 4 breakpoint CSS vars |
| Device-specific visibility | ❌ | Missing |
| Mobile menu toggle | ✅ | `mobile_menu_style` |
| Tablet adjustments | ❌ | No tablet-specific settings |

### Animations

| Feature | Status | Detail |
|--------|--------|--------|
| Page loader toggle | ✅ | `preloader_enable` |
| Page loader type | ❌ | Only enable/disable, no type selection |
| Scroll reveal animations | ❌ | Missing |
| Hover effects | ⚠️ | Product card hover only |
| GSAP controls | ❌ | Missing |
| Three.js | ❌ | Missing |
| Lenis smooth scroll | ❌ | Missing |
| Swiper | ❌ | Missing |
| Animation speed | ❌ | Missing |
| Reduced motion | ❌ | Missing |

### 3D Effects

| Feature | Status | Detail |
|--------|--------|--------|
| Enable/disable | ✅ | `effects_3d_tilt_enable` |
| Tilt intensity | ❌ | Only enable/disable |
| Perspective | ❌ | Missing |
| Performance mode | ❌ | Missing |

### Search

| Feature | Status | Detail |
|--------|--------|--------|
| AJAX live search | ✅ | `search_ajax` |
| Search suggestions | ✅ | `search_suggestions` |
| Product search | ✅ | `search_post_types` includes products |
| Blog search | ✅ | `search_post_types` includes posts |
| Search placeholder | ✅ | `search_placeholder` |
| Results count | ✅ | `search_results_count` |

### Performance

| Feature | Status | Detail |
|--------|--------|--------|
| Lazy loading | ⚠️ | `performance_lazy_load_images` toggle (basic) |
| Minification | ❌ | Missing |
| Preload key assets | ⚠️ | `performance_preload` (basic) |
| Font loading strategy | ❌ | Missing |
| Image optimization | ❌ | Missing |
| Script defer/async | ❌ | Missing |
| Preconnect | ✅ | `performance_preconnect` |
| Prefetch | ✅ | `performance_prefetch` |
| DNS prefetch | ✅ | `performance_dns_prefetch` |
| Resource hints | ✅ | `performance_resource_hints` |

### SEO

| Feature | Status | Detail |
|--------|--------|--------|
| Meta title template | ✅ | `seo_meta_title` |
| Meta description | ✅ | `seo_meta_description` |
| Open Graph tags | ✅ | OG title, desc, image, URL |
| Twitter Cards | ✅ | Twitter title, desc, image |
| JSON-LD Schema | ✅ | `seo_json_ld` — basic Organization schema |
| Breadcrumbs schema | ❌ | Missing (HTML breadcrumbs exist, no schema) |
| Canonical URLs | ✅ | Base tag injection |
| Sitemap integration | ❌ | Missing |
| Meta defaults template | ❌ | Missing — title/desc are static |

### Accessibility

| Feature | Status | Detail |
|--------|--------|--------|
| Keyboard navigation | ❌ | Missing |
| Focus states | ❌ | Missing |
| Contrast options | ⚠️ | `accessibility_contrast_mode`, `accessibility_contrast_level` |
| Skip links | ❌ | Missing |
| ARIA labels | ❌ | Missing |
| Screen reader support | ❌ | Missing |
| Reduced motion | ❌ | Missing |
| Font size adjustment | ⚠️ | `accessibility_font_size_adjustment` |

### Integrations

| Feature | Status | Detail |
|--------|--------|--------|
| Google Analytics | ❌ | Setting exists (`integration_ga_id`) but no GA4 tracking code injection |
| Google Maps API key | ❌ | Setting exists but not loaded on contact page |
| Meta Pixel ID | ❌ | Missing |
| Newsletter service | ❌ | No Mailchimp/etc integration |
| Social URLs | ✅ | In footer |

### Custom Code

| Feature | Status | Detail |
|--------|--------|--------|
| Custom CSS | ✅ | `custom_css` |
| Custom JS | ✅ | `custom_js` |
| Header scripts | ✅ | `custom_header_scripts` |
| Footer scripts | ✅ | `custom_footer_scripts` |
| Body class | ✅ | `custom_body_class` |

### Import / Export

| Feature | Status | Detail |
|--------|--------|--------|
| Export settings (JSON) | ✅ | REST `/export`, admin export button |
| Import settings | ✅ | REST `/import`, admin import |
| Reset to defaults | ❌ | Missing |
| Backup/Restore | ❌ | Missing |
| Presets (color, typography) | ❌ | Missing |

### Static Pages (14 page types)

All 14 page types have content settings. All implemented.

| Page | Settings |
|------|----------|
| About | 20 settings — title, subtitle, mission, team (repeater with image/name/role/bio/social) |
| Contact | 15 — address, email, phone, map, form |
| FAQ | 6 — questions/answers (repeater) |
| Team | 6 — member cards (repeater) |
| Testimonials | 3 — review cards (repeater with image/name/text/rating) |
| Coming Soon | 5 — countdown, notification form |
| 404 | 3 — title, description, button |
| Login | 9 — layout, fields |
| Register | 10 — layout, fields |
| Thank You | 5 — message, redirect |
| Privacy | 2 — content blocks |
| Terms | 2 — content blocks |
| Cookie Policy | 2 — content blocks |
| Load More | 8 — demo content |

---

## 4. Customizer Panels (14 panels, 49 sections)

All mapped from Settings Registry. Provides visual editing with live preview for colors and CSS vars.

**Live preview coverage:** Only `color` type settings get automatic postMessage. Only 7 non-color settings (hero) have explicit postMessage. ~12 remaining panels require page refresh.

---

## 5. Known Issues

### High Priority

1. **CSS var data duplicated** — `get_css_var_map()` and px key list in both `class-customizer.php` and `templates/shell.php`. Must be DRY'd.
2. **No `get_px_keys()` method** — px key list is hardcoded inline 3 times.
3. **global .gitignore** — Removed `*.png` / `*.jpg` patterns; all images are tracked.

### Medium Priority

1. **Only 1 conditional dependency** — The `dependencies` system exists but barely used.
2. **Customizer transport limited** — Only colors + 7 hero settings use live preview. Rest requires refresh.
3. **JS uses `innerHTML`** — Even though `escapeHtml()` exists, it's not used on template strings.
4. **Missing WooCommerce endpoints** — No attributes, variations, reviews endpoints.

### Low Priority

1. **Typo keys**: `three_colum_sidbar_*`, `six_colum_full_wide_*` (should be `column`)
2. **Duplicate settings**: `footer_social_links` and `footer_social` are nearly identical
3. **JS uses `var`** instead of `let/const`
4. **No ACF support** — old optix-core had it
5. **No unit tests** for phantom-core code
