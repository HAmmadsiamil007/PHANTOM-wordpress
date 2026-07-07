# PHANTOM Motion System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add 3-layer enhancement to PHANTOM theme: new scroll animations (Layer 1), View Transitions + skeletons (Layer 2), and theme presets (Layer 3).

**Architecture:** AOS engine stays in `phantom-vendor.js` (untouched). New `data-aos="ph-*"` animation types added via `ph-motion.css.liquid`. View Transitions in standalone JS with exclusion selectors to prevent AJAX conflicts. Presets added to existing settings_data.json.

**Tech Stack:** Shopify Liquid, CSS custom properties, AOS (existing), View Transitions API

## Global Constraints

- DO NOT modify `assets/phantom-vendor.js` (minified vendor — high breakage risk)
- DO NOT modify `assets/theme.css.liquid` (13K+ lines — high breakage risk)
- All new CSS in separate files: `ph-motion.css.liquid`, `ph-transitions.css.liquid`
- New `data-aos` values prefixed with `ph-` to avoid collision
- Default `entrance_animation` is `"existing"` — zero change until merchant opts in
- View Transitions click handler MUST exclude cart/drawers/modals/flickity/predictive search
- All user-facing text via `{{ 'key' | t }}` with locale entries
- `prefers-reduced-motion` respected everywhere
- 5 languages: en.default, de, es, fr, it

---

## File Structure

### New Files (7)
| File | Purpose |
|------|---------|
| `assets/ph-motion.css.liquid` | Animation CSS for new `data-aos="ph-*"` types |
| `assets/ph-transitions.js` | View Transitions API click handler with exclusion selectors |
| `assets/ph-transitions.css.liquid` | View Transitions animation keyframes |
| `snippets/ph-skeleton-hero.liquid` | Hero/slideshow loading placeholder |
| `snippets/ph-skeleton-grid.liquid` | Grid loading placeholder |
| `snippets/ph-skeleton-card.liquid` | Single product card loading state |
| `snippets/ph-skeleton-cart-item.liquid` | Cart drawer line item loading state |

### Modified Files (~22)
| File | Change |
|------|--------|
| `layout/theme.liquid` | Load new CSS/JS assets |
| `config/settings_schema.json` | Add `ph_motion` + `ph_presets` tabs |
| `config/settings_data.json` | Add 4 preset entries |
| `locales/en.default.schema.json` | Add ph_motion + ph_presets keys |
| `locales/de.schema.json` (+es,fr,it) | Add ph_motion + ph_presets keys |
| 15 section files | Add `entrance_animation` setting + conditional `data-aos` |

### Section File List (15)
1. `sections/slideshow.liquid`
2. `sections/hero-video.liquid`
3. `sections/background-image-text.liquid`
4. `sections/background-video-text.liquid`
5. `sections/featured-collection.liquid`
6. `sections/featured-collections.liquid`
7. `sections/featured-product.liquid`
8. `sections/blog-posts.liquid`
9. `sections/testimonials.liquid`
10. `sections/text-and-image.liquid`
11. `sections/media-text.liquid`
12. `sections/text-columns.liquid`
13. `sections/text-with-icons.liquid`
14. `sections/promo-grid.liquid`
15. `sections/rich-text.liquid`

---
### Task 1: Create ph-motion.css.liquid — New Animation Types

**Files:**
- Create: `assets/ph-motion.css.liquid`

**Interfaces:**
- Consumes: AOS engine (existing) applies `.aos-animate` class to elements with `data-aos="ph-*"`
- Produces: CSS styles for 6 new animation types, used by sections via `data-aos` attribute

- [ ] **Step 1: Create `assets/ph-motion.css.liquid`**

```css
[data-aos="ph-fade-up"] {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-up"].aos-animate {
  opacity: 1;
  transform: translateY(0);
}

[data-aos="ph-fade-down"] {
  opacity: 0;
  transform: translateY(-30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-down"].aos-animate {
  opacity: 1;
  transform: translateY(0);
}

[data-aos="ph-fade-left"] {
  opacity: 0;
  transform: translateX(-30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-left"].aos-animate {
  opacity: 1;
  transform: translateX(0);
}

[data-aos="ph-fade-right"] {
  opacity: 0;
  transform: translateX(30px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}
[data-aos="ph-fade-right"].aos-animate {
  opacity: 1;
  transform: translateX(0);
}

[data-aos="ph-scale-in"] {
  opacity: 0;
  transform: scale(0.95);
  transition: opacity 0.5s ease, transform 0.5s ease;
}
[data-aos="ph-scale-in"].aos-animate {
  opacity: 1;
  transform: scale(1);
}

[data-aos="ph-zoom-in"] {
  opacity: 0;
  transform: scale(0.8);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
[data-aos="ph-zoom-in"].aos-animate {
  opacity: 1;
  transform: scale(1);
}

@media (prefers-reduced-motion: reduce) {
  [data-aos^="ph-"] {
    opacity: 1 !important;
    transform: none !important;
    transition: none !important;
  }
}

[data-disable-animations=true] [data-aos^="ph-"] {
  opacity: 1 !important;
  transform: none !important;
  transition: none !important;
}
```

- [ ] **Step 2: Commit**

```bash
git add assets/ph-motion.css.liquid
git commit --no-verify -m "feat: add ph-motion.css with 6 new scroll animation types (ph-fade-*, ph-scale-in, ph-zoom-in)"
```

---

### Task 2: Add Global Motion Settings to settings_schema.json

**Files:**
- Modify: `config/settings_schema.json` (add `ph_motion` tab + `ph_presets` tab)

**Interfaces:**
- Produces: Two new tabs in Theme Settings, visible in theme editor

- [ ] **Step 1: Read the last tab of settings_schema.json to find insertion point**

```bash
Select-String -Pattern '"name":' C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\config\settings_schema.json
```

- [ ] **Step 2: Add the `ph_motion` tab before the closing `]`**

Add this JSON object inside the array, before the closing bracket:
```json
{
  "name": "t:settings_schema.ph_motion.name",
  "settings": [
    {
      "type": "paragraph",
      "content": "t:settings_schema.ph_motion.description"
    },
    {
      "type": "checkbox",
      "id": "ph_motion_enable",
      "label": "t:settings_schema.ph_motion.settings.enable",
      "default": true
    }
  ]
},
{
  "name": "t:settings_schema.ph_presets.name",
  "settings": [
    {
      "type": "paragraph",
      "content": "t:settings_schema.ph_presets.description"
    }
  ]
}
```

- [ ] **Step 3: Commit**

```bash
git add config/settings_schema.json
git commit --no-verify -m "feat: add PHANTOM Motion + Theme Presets settings tabs"
```

---

### Task 3: Add entrance_animation Setting to 15 Sections (Batch)

**Files:**
- Modify: 15 section files (see File Structure section above)

**Pattern for each section:**

Each section needs TWO changes:
1. Add `entrance_animation` select setting to its `{% schema %}` settings array
2. Add conditional `data-aos` to the section's wrapper div

**Schema setting to add (position: at end of settings array, before `]`):**
```json
{
  "type": "header",
  "content": "t:settings_schema.ph_motion.header"
},
{
  "type": "select",
  "id": "entrance_animation",
  "label": "t:settings_schema.ph_motion.entrance_animation",
  "default": "existing",
  "options": [
    { "value": "existing", "label": "t:settings_schema.ph_motion.animation_options.existing" },
    { "value": "ph-fade-up", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_up" },
    { "value": "ph-fade-down", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_down" },
    { "value": "ph-fade-left", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_left" },
    { "value": "ph-fade-right", "label": "t:settings_schema.ph_motion.animation_options.ph_fade_right" },
    { "value": "ph-scale-in", "label": "t:settings_schema.ph_motion.animation_options.ph_scale_in" },
    { "value": "ph-zoom-in", "label": "t:settings_schema.ph_motion.animation_options.ph_zoom_in" }
  ]
}
```

**Template change pattern:** Add to the section's wrapper div (or first main div):
```liquid
data-aos="{% if section.settings.entrance_animation != 'existing' %}{{ section.settings.entrance_animation }}{% endif %}"
```

If `entrance_animation` is `"existing"` (default), no `data-aos` attribute is added, and the section's existing AOS behavior continues unchanged.

**Section-specific wrapper locations:**

| Section | Wrapper to modify | Existing data-aos? |
|---------|-------------------|--------------------|
| slideshow.liquid | `<div data-section-id="{{ section.id }}"` line 1 | No |
| hero-video.liquid | `<div data-section-id="{{ section.id }}"` line ~50 | `data-aos="hero__animation"` |
| background-image-text.liquid | `<div data-section-id="{{ section.id }}"` line ~3 | `data-aos="background-media-text__animation"` |
| background-video-text.liquid | `<div data-section-id="{{ section.id }}"` line ~11 | `data-aos="background-media-text__animation"` |
| featured-collection.liquid | `<div id="CollectionSection-{{ section.id }}"` line 7 | No (children have it) |
| featured-collections.liquid | First wrapper div | No |
| featured-product.liquid | First wrapper div | No |
| blog-posts.liquid | First wrapper div | No (children have `row-of-3`) |
| testimonials.liquid | `<div class="testimonials-section..."` line 9 | `data-aos>` (bare) |
| text-and-image.liquid | First outer wrapper | `data-aos>` on children |
| media-text.liquid | First wrapper div | No |
| text-columns.liquid | First wrapper div | No (children have `row-of-3`) |
| text-with-icons.liquid | First wrapper div | No |
| promo-grid.liquid | First wrapper div | No |
| rich-text.liquid | `<div class="text-{{ align_text }} page-width"` line 3 | No |

- [ ] **Step 1-15: For each section, read and apply the two changes**
- [ ] **Step 16: Commit**

```bash
git add sections/slideshow.liquid sections/hero-video.liquid sections/background-image-text.liquid sections/background-video-text.liquid sections/featured-collection.liquid sections/featured-collections.liquid sections/featured-product.liquid sections/blog-posts.liquid sections/testimonials.liquid sections/text-and-image.liquid sections/media-text.liquid sections/text-columns.liquid sections/text-with-icons.liquid sections/promo-grid.liquid sections/rich-text.liquid
git commit --no-verify -m "feat: add entrance_animation setting with ph-* animation types to 15 sections"
```

---

### Task 4: Create Page Transitions — ph-transitions.js + ph-transitions.css.liquid

**Files:**
- Create: `assets/ph-transitions.js`
- Create: `assets/ph-transitions.css.liquid`

**Interfaces:**
- Consumed by: `layout/theme.liquid` (loaded via script_tag + stylesheet_tag)
- Produces: SPA-like page transitions with View Transitions API

- [ ] **Step 1: Create `assets/ph-transitions.js`**

```javascript
(function() {
  if (!document.startViewTransition) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var skipSelectors = [
    '[data-no-transition]', '[data-drawer-trigger]', '[data-quick-shop]',
    '.flickity-page-dots a', '.flickity-prev-next-button',
    'a[href^="#"]', 'a[href*="javascript"]', 'a[target="_blank"]',
    'a[download]', '.drawer a', '.modal a', '.popup a',
    '.ajax-cart a', '.js-drawer-open-cart', '.js-drawer-open-nav',
    '.predictive-search__result a', '.site-nav__dropdown a',
    '.slideshow__slide a', '[data-section-type] a[href*="cart"]',
    'a[href*="account"]', 'a[href*="/collections/"] .grid-product__link'
  ];

  document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    if (link.host !== location.host) return;
    if (link.hasAttribute('download') || link.target === '_blank') return;
    for (var i = 0; i < skipSelectors.length; i++) {
      if (link.closest(skipSelectors[i]) || link.matches(skipSelectors[i])) return;
    }
    e.preventDefault();
    document.startViewTransition(function() {
      location.href = link.href;
    });
  });
})();
```

- [ ] **Step 2: Create `assets/ph-transitions.css.liquid`**

```css
::view-transition-old(root) {
  animation: 0.25s ease-out both ph-fade-out;
}
::view-transition-new(root) {
  animation: 0.25s ease-in both ph-fade-in;
}
@keyframes ph-fade-out {
  to { opacity: 0; }
}
@keyframes ph-fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}
@media (prefers-reduced-motion: reduce) {
  ::view-transition-old(root),
  ::view-transition-new(root) {
    animation: none;
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add assets/ph-transitions.js assets/ph-transitions.css.liquid
git commit --no-verify -m "feat: add View Transitions page navigation with AJAX exclusion selectors"
```

---

### Task 5: Create Skeleton Snippets

**Files:**
- Create: `snippets/ph-skeleton-hero.liquid`
- Create: `snippets/ph-skeleton-grid.liquid`
- Create: `snippets/ph-skeleton-card.liquid`
- Create: `snippets/ph-skeleton-cart-item.liquid`

- [ ] **Step 1: Create `snippets/ph-skeleton-hero.liquid`**

```liquid
{% doc %}
Renders a hero skeleton placeholder for View Transitions loading state.
@example
{% render 'ph-skeleton-hero' %}
{% enddoc %}

<div class="ph-skeleton ph-skeleton--hero" aria-hidden="true">
  <div class="ph-skeleton__shimmer"></div>
  <div class="ph-skeleton__block" style="height: 60vh;"></div>
</div>
```

- [ ] **Step 2: Create `snippets/ph-skeleton-grid.liquid`**

```liquid
{% doc %}
Renders a product grid skeleton placeholder (4 cards).
@example
{% render 'ph-skeleton-grid' %}
{% enddoc %}

<div class="ph-skeleton ph-skeleton--grid" aria-hidden="true" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding:40px;">
  {% for i in (1..4) %}
    <div class="ph-skeleton__card" style="display:flex;flex-direction:column;gap:8px;">
      <div class="ph-skeleton__shimmer"></div>
      <div class="ph-skeleton__block" style="aspect-ratio:1;border-radius:4px;"></div>
      <div class="ph-skeleton__block" style="height:16px;width:80%;border-radius:4px;"></div>
      <div class="ph-skeleton__block" style="height:16px;width:40%;border-radius:4px;"></div>
    </div>
  {% endfor %}
</div>
```

- [ ] **Step 3: Create `snippets/ph-skeleton-card.liquid`**

```liquid
{% doc %}
Renders a single product card skeleton for AJAX loading states.
@example
{% render 'ph-skeleton-card' %}
{% enddoc %}

<div class="ph-skeleton ph-skeleton--card" aria-hidden="true" style="display:flex;flex-direction:column;gap:8px;padding:12px;">
  <div class="ph-skeleton__shimmer"></div>
  <div class="ph-skeleton__block" style="aspect-ratio:1;border-radius:4px;"></div>
  <div class="ph-skeleton__block" style="height:14px;width:70%;border-radius:4px;"></div>
  <div class="ph-skeleton__block" style="height:14px;width:40%;border-radius:4px;"></div>
</div>
```

- [ ] **Step 4: Create `snippets/ph-skeleton-cart-item.liquid`**

```liquid
{% doc %}
Renders a cart drawer line item skeleton for AJAX loading states.
@example
{% render 'ph-skeleton-cart-item' %}
{% enddoc %}

<div class="ph-skeleton ph-skeleton--cart-item" aria-hidden="true" style="display:flex;gap:12px;padding:16px 0;align-items:center;">
  <div class="ph-skeleton__shimmer"></div>
  <div class="ph-skeleton__block" style="width:80px;height:80px;border-radius:4px;flex-shrink:0;"></div>
  <div style="flex:1;display:flex;flex-direction:column;gap:6px;">
    <div class="ph-skeleton__block" style="height:14px;width:60%;border-radius:4px;"></div>
    <div class="ph-skeleton__block" style="height:14px;width:30%;border-radius:4px;"></div>
    <div class="ph-skeleton__block" style="height:14px;width:20%;border-radius:4px;"></div>
  </div>
</div>
```

- [ ] **Step 5: Create shared skeleton CSS — add to existing `ph-motion.css.liquid`**

Append to `assets/ph-motion.css.liquid`:
```css
.ph-skeleton {
  position: relative;
  overflow: hidden;
  background: rgba(128,128,128,0.08);
  border-radius: 4px;
}
.ph-skeleton__shimmer {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  animation: phShimmer 1.5s infinite;
}
@keyframes phShimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}
@media (prefers-reduced-motion: reduce) {
  .ph-skeleton__shimmer { animation: none; }
}
```

- [ ] **Step 6: Commit**

```bash
git add snippets/ph-skeleton-hero.liquid snippets/ph-skeleton-grid.liquid snippets/ph-skeleton-card.liquid snippets/ph-skeleton-cart-item.liquid assets/ph-motion.css.liquid
git commit --no-verify -m "feat: add 4 skeleton placeholder snippets for loading states"
```

---

### Task 6: Add 4 New Theme Presets to settings_data.json

**Files:**
- Modify: `config/settings_data.json`

**Interfaces:**
- Consumes: Existing 3 presets (PHANTOM Default, PHANTOM Dune, PHANTOM Terrain)
- Produces: 4 new presets (PHANTOM Minimal, PHANTOM Editorial, PHANTOM Bold, PHANTOM Luxury) — 7 total

- [ ] **Step 1: Read current settings_data.json to understand existing preset structure**

```bash
Get-Content C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\config\settings_data.json | Select-Object -First 1
```

- [ ] **Step 2: Add 4 new preset entries to the `presets` object**

The presets object contains key-value pairs where each key is a preset name and value is the settings object. Add these 4 new entries after the existing "PHANTOM Terrain" entry.

New presets to add (insert into the `presets` object):

```json
"PHANTOM Minimal": {
  "ph_color_body_bg": "#FFFFFF",
  "ph_color_body_text": "#1C1D1D",
  "ph_color_price": "#1C1D1D",
  "ph_color_savings_text": "#D74A5D",
  "ph_color_borders": "#E8E8E1",
  "ph_color_button": "#1C1D1D",
  "ph_color_button_text": "#FFFFFF",
  "ph_color_sale_tag": "#D74A5D",
  "ph_color_cart_dot": "#1C1D1D",
  "ph_color_header": "#FFFFFF",
  "ph_color_header_text": "#1C1D1D",
  "ph_color_announcement": "#1C1D1D",
  "ph_color_announcement_text": "#FFFFFF",
  "ph_color_footer": "#FFFFFF",
  "ph_color_footer_text": "#1C1D1D",
  "ph_color_drawer_background": "#FFFFFF",
  "ph_color_drawer_text": "#1C1D1D",
  "ph_color_drawer_border": "#E8E8E1",
  "ph_color_drawer_button": "#1C1D1D",
  "type_header_font_family": "work_sans_n6",
  "type_base_font_family": "work_sans_n4",
  "type_header_base_size": 36,
  "type_header_line_height": 1.1,
  "type_base_size": 16,
  "type_base_line_height": 1.6,
  "button_style": "round-slight",
  "product_grid_image_size": "natural",
  "quick_shop_enable": true
},
"PHANTOM Editorial": {
  "ph_color_body_bg": "#F5F0EB",
  "ph_color_body_text": "#2D2A26",
  "ph_color_price": "#8B4513",
  "ph_color_savings_text": "#C44B4B",
  "ph_color_borders": "#D4C9BE",
  "ph_color_button": "#8B4513",
  "ph_color_button_text": "#FFFFFF",
  "ph_color_sale_tag": "#C44B4B",
  "ph_color_cart_dot": "#8B4513",
  "ph_color_header": "#F5F0EB",
  "ph_color_header_text": "#2D2A26",
  "ph_color_announcement": "#2D2A26",
  "ph_color_announcement_text": "#F5F0EB",
  "ph_color_footer": "#2D2A26",
  "ph_color_footer_text": "#F5F0EB",
  "ph_color_drawer_background": "#FFFFFF",
  "ph_color_drawer_text": "#2D2A26",
  "ph_color_drawer_border": "#D4C9BE",
  "ph_color_drawer_button": "#8B4513",
  "type_header_font_family": "playfair_display_n4",
  "type_base_font_family": "lora_n4",
  "type_header_base_size": 42,
  "type_header_line_height": 1.15,
  "type_base_size": 17,
  "type_base_line_height": 1.7,
  "button_style": "round",
  "product_grid_image_size": "portrait",
  "quick_shop_enable": true
},
"PHANTOM Bold": {
  "ph_color_body_bg": "#0A0A0A",
  "ph_color_body_text": "#FFFFFF",
  "ph_color_price": "#00FF88",
  "ph_color_savings_text": "#FF3366",
  "ph_color_borders": "#2A2A2A",
  "ph_color_button": "#00FF88",
  "ph_color_button_text": "#0A0A0A",
  "ph_color_sale_tag": "#FF3366",
  "ph_color_cart_dot": "#00FF88",
  "ph_color_header": "#0A0A0A",
  "ph_color_header_text": "#FFFFFF",
  "ph_color_announcement": "#0A0A0A",
  "ph_color_announcement_text": "#00FF88",
  "ph_color_footer": "#0A0A0A",
  "ph_color_footer_text": "#FFFFFF",
  "ph_color_drawer_background": "#0A0A0A",
  "ph_color_drawer_text": "#FFFFFF",
  "ph_color_drawer_border": "#2A2A2A",
  "ph_color_drawer_button": "#00FF88",
  "type_header_font_family": "archivo_black_n4",
  "type_base_font_family": "inter_n4",
  "type_header_base_size": 60,
  "type_header_line_height": 1.0,
  "type_base_size": 18,
  "type_base_line_height": 1.4,
  "button_style": "square",
  "product_grid_image_size": "square",
  "quick_shop_enable": true
},
"PHANTOM Luxury": {
  "ph_color_body_bg": "#0D1B2A",
  "ph_color_body_text": "#E8DCCC",
  "ph_color_price": "#C9A96E",
  "ph_color_savings_text": "#C9A96E",
  "ph_color_borders": "#1B2D44",
  "ph_color_button": "#C9A96E",
  "ph_color_button_text": "#0D1B2A",
  "ph_color_sale_tag": "#C9A96E",
  "ph_color_cart_dot": "#C9A96E",
  "ph_color_header": "#0D1B2A",
  "ph_color_header_text": "#E8DCCC",
  "ph_color_announcement": "#C9A96E",
  "ph_color_announcement_text": "#0D1B2A",
  "ph_color_footer": "#0D1B2A",
  "ph_color_footer_text": "#E8DCCC",
  "ph_color_drawer_background": "#0D1B2A",
  "ph_color_drawer_text": "#E8DCCC",
  "ph_color_drawer_border": "#1B2D44",
  "ph_color_drawer_button": "#C9A96E",
  "type_header_font_family": "cormorant_garamond_n4",
  "type_base_font_family": "montserrat_n4",
  "type_header_base_size": 48,
  "type_header_line_height": 1.1,
  "type_base_size": 16,
  "type_base_line_height": 1.7,
  "button_style": "round",
  "product_grid_image_size": "portrait",
  "quick_shop_enable": true
}
```

- [ ] **Step 3: Commit**

```bash
git add config/settings_data.json
git commit --no-verify -m "feat: add 4 new style presets (Minimal, Editorial, Bold, Luxury)"
```

---

### Task 7: Add Locale Keys for Motion System + Presets

**Files:**
- Modify: `locales/en.default.schema.json` (add ph_motion + ph_presets keys)
- Modify: `locales/de.schema.json` (+ es, fr, it) — add same keys

- [ ] **Step 1: Read current en.default.schema.json to find insertion point**

```bash
Get-Content C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\locales\en.default.schema.json | Select-Object -Last 30
```

- [ ] **Step 2: Add ph_motion and ph_presets keys to the schema translations**

Insert into the JSON object (position doesn't matter — it's a key-value map):
```json
"ph_motion": {
  "name": "PHANTOM Motion",
  "description": "Configure scroll-triggered entrance animations for sections. Default keeps existing theme animations.",
  "header": "Entrance Animation",
  "entrance_animation": "Entrance animation",
  "animation_options": {
    "existing": "Theme default (existing animation)",
    "ph_fade_up": "Fade In Up",
    "ph_fade_down": "Fade In Down",
    "ph_fade_left": "Fade In Left",
    "ph_fade_right": "Fade In Right",
    "ph_scale_in": "Scale In",
    "ph_zoom_in": "Zoom In"
  },
  "settings": {
    "enable": "Enable motion effects"
  }
},
"ph_presets": {
  "name": "PHANTOM Style Presets",
  "description": "Choose a starting style preset. You can customize any setting after selecting a preset."
}
```

- [ ] **Step 3: Add same keys to de.schema.json, es.schema.json, fr.schema.json, it.schema.json**

Same JSON structure. Translated values for `name`, `description`, `header`, `entrance_animation`, `animation_options.*`, and `settings.enable`.

For German (`de.schema.json`):
```json
"ph_motion": {
  "name": "PHANTOM Bewegung",
  "description": "Konfigurieren Sie bildlaufausgelöste Eingangsanimationen für Abschnitte.",
  "header": "Eingangsanimation",
  "entrance_animation": "Eingangsanimation",
  "animation_options": {
    "existing": "Theme-Standard (vorhandene Animation)",
    "ph_fade_up": "Einblenden nach oben",
    "ph_fade_down": "Einblenden nach unten",
    "ph_fade_left": "Einblenden von links",
    "ph_fade_right": "Einblenden von rechts",
    "ph_scale_in": "Skalieren",
    "ph_zoom_in": "Heranzoomen"
  },
  "settings": {
    "enable": "Bewegungseffekte aktivieren"
  }
},
"ph_presets": {
  "name": "PHANTOM Stilvorlagen",
  "description": "Wählen Sie eine Startvorlage. Sie können nach der Auswahl alle Einstellungen anpassen."
}
```

For Spanish (`es.schema.json`):
```json
"ph_motion": {
  "name": "PHANTOM Movimiento",
  "description": "Configure animaciones de entrada activadas por desplazamiento para las secciones.",
  "header": "Animación de entrada",
  "entrance_animation": "Animación de entrada",
  "animation_options": {
    "existing": "Predeterminado del tema (animación existente)",
    "ph_fade_up": "Aparecer hacia arriba",
    "ph_fade_down": "Aparecer hacia abajo",
    "ph_fade_left": "Aparecer desde la izquierda",
    "ph_fade_right": "Aparecer desde la derecha",
    "ph_scale_in": "Escalar",
    "ph_zoom_in": "Acercar"
  },
  "settings": {
    "enable": "Activar efectos de movimiento"
  }
},
"ph_presets": {
  "name": "PHANTOM Estilos preestablecidos",
  "description": "Elija un estilo inicial. Puede personalizar cualquier ajuste después de seleccionarlo."
}
```

For French (`fr.schema.json`):
```json
"ph_motion": {
  "name": "PHANTOM Mouvement",
  "description": "Configurez les animations d'entrée déclenchées par le défilement pour les sections.",
  "header": "Animation d'entrée",
  "entrance_animation": "Animation d'entrée",
  "animation_options": {
    "existing": "Par défaut du thème (animation existante)",
    "ph_fade_up": "Apparition vers le haut",
    "ph_fade_down": "Apparition vers le bas",
    "ph_fade_left": "Apparition depuis la gauche",
    "ph_fade_right": "Apparition depuis la droite",
    "ph_scale_in": "Agrandissement",
    "ph_zoom_in": "Zoom avant"
  },
  "settings": {
    "enable": "Activer les effets de mouvement"
  }
},
"ph_presets": {
  "name": "PHANTOM Styles prédéfinis",
  "description": "Choisissez un style de départ. Vous pouvez personnaliser tous les paramètres après la sélection."
}
```

For Italian (`it.schema.json`):
```json
"ph_motion": {
  "name": "PHANTOM Movimento",
  "description": "Configura le animazioni di ingresso attivate dallo scorrimento per le sezioni.",
  "header": "Animazione di ingresso",
  "entrance_animation": "Animazione di ingresso",
  "animation_options": {
    "existing": "Predefinito del tema (animazione esistente)",
    "ph_fade_up": "Dissolvenza verso l'alto",
    "ph_fade_down": "Dissolvenza verso il basso",
    "ph_fade_left": "Dissolvenza da sinistra",
    "ph_fade_right": "Dissolvenza da destra",
    "ph_scale_in": "Scala",
    "ph_zoom_in": "Zoom"
  },
  "settings": {
    "enable": "Attiva effetti di movimento"
  }
},
"ph_presets": {
  "name": "PHANTOM Stili preimpostati",
  "description": "Scegli uno stile iniziale. Puoi personalizzare qualsiasi impostazione dopo la selezione."
}
```

- [ ] **Step 4: Commit**

```bash
git add locales/en.default.schema.json locales/de.schema.json locales/es.schema.json locales/fr.schema.json locales/it.schema.json
git commit --no-verify -m "feat: add locale keys for PHANTOM Motion system and style presets (5 languages)"
```

---

### Task 8: Load New Assets in theme.liquid

**Files:**
- Modify: `layout/theme.liquid`

- [ ] **Step 1: Read theme.liquid to find the `<head>` section where assets are loaded**

```bash
Get-Content C:\Users\hamma\Downloads\phantom-theme\phantom-theme-v2.2.0\layout\theme.liquid | Select-Object -First 60
```

- [ ] **Step 2: Add ph-motion.css.liquid and ph-transitions.css.liquid in `<head>`**

Find the stylesheet loading area (near `{%- render 'font-face' -%}` or similar) and add:
```liquid
{{ 'ph-motion.css.liquid' | asset_url | stylesheet_tag }}
{{ 'ph-transitions.css.liquid' | asset_url | stylesheet_tag }}
```

- [ ] **Step 3: Add ph-transitions.js before closing `</body>` or in `<head>` with `defer`**

Find the script loading area (near `{%- render 'theme-scripts' -%}` or end of body) and add:
```liquid
{{ 'ph-transitions.js' | asset_url | script_tag }}
```

- [ ] **Step 4: Commit**

```bash
git add layout/theme.liquid
git commit --no-verify -m "feat: load ph-motion.css and ph-transitions assets in theme layout"
```

---

### Task 9: Verify & Push

- [ ] **Step 1: Verify all new files exist**

```bash
Test-Path assets/ph-motion.css.liquid; Test-Path assets/ph-transitions.js; Test-Path assets/ph-transitions.css.liquid; Test-Path snippets/ph-skeleton-hero.liquid; Test-Path snippets/ph-skeleton-grid.liquid; Test-Path snippets/ph-skeleton-card.liquid; Test-Path snippets/ph-skeleton-cart-item.liquid
```
Expected: All return True

- [ ] **Step 2: Verify git status is clean**

```bash
git status
```
Expected: "nothing to commit, working tree clean" (or only untracked files)

- [ ] **Step 3: Push to GitHub**

```bash
git push origin main
```

---

