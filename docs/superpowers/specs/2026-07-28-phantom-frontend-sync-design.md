# Phantom Frontend — AETHER Reference Sync Design

## Purpose
Sync Phantom Core's 22 frontend HTML templates + CSS + images with the AETHER reference template at `C:\Users\hamma\Downloads\templete\frontend\`, bringing the Phantom theme to pixel-perfect parity with the premium reference design.

## Reference Template Analysis

The AETHER reference is a premium cinematic dark-theme sneaker brand template featuring:

### Design System
- **Background**: `#09090B` (void black)
- **Typography**: Cabinet Grotesk (headings) + Satoshi (body)
- **Accent**: `#C8956C` (gold)
- **Z-scale**: 10-tier system (fog=1, header=1000, preloader=9999)

### Global Components (shared across all 22 pages)
1. **Preloader** — Logo + progress bar animation
2. **Cinematic Fog System** — 3-layer CSS-animated fog overlay
3. **Announcement Bar** — Rotating messages (shipping, returns, drops)
4. **Smart Sticky Header** — Hide on scroll down, show on scroll up
5. **Mobile Menu Overlay** — Full-screen slide-out with search + nav + socials
6. **Newsletter Section** — Glow background, email input, success state
7. **Footer** — 5-column layout (brand, shop, support, company, newsletter) + bottom bar with legal + payment icons

### Page-Specific Sections
- **Home**: Hero slider (Swiper 11), category grid, bestsellers grid, reviews carousel, FAQ accordion, newsletter
- **Shop**: Page hero, filter bar, product grid, pagination
- **Product Detail**: Breadcrumb, gallery (main+thumbs swiper), color/size/quantity selectors, sticky add-to-cart bar, tech specs accordion, customer reviews with score bars, related products swiper, size guide modal
- **Blog**: Page hero, blog card grid with categories

### Animation System
- **GSAP 3.12.5** + ScrollTrigger for scroll-triggered reveals
- **Lenis 1.1.18** for smooth scrolling
- **Data attributes**: `data-motion-text`, `data-reveal-item`, `data-reveal-group`, `data-tilt`, `data-magnetic`, `data-image-zoom`
- **Motion CSS**: visibility hidden until JS reveals, `will-change` hints, prefers-reduced-motion fallback

### CDN Dependencies
- Bootstrap 5.3.3 (CSS + JS)
- Font Awesome 6.5.1
- Swiper 11
- GSAP 3.12.5 + ScrollTrigger
- Lenis 1.1.18

## Divergences Found

| Item | Reference | Phantom Theme |
|------|-----------|---------------|
| Font Awesome CDN | Present in all `<head>` | Missing/commented in several |
| Sticky add-to-cart bar | Full section (~50 lines) | Entirely absent |
| `style.css` | 99 KB | 126 KB (diverged) |
| `motion.css` | 4.8 KB | 5.1 KB (diverged) |
| `responsive.css` | 32.8 KB | 35 KB (diverged) |
| `a11y.css` | 3 KB | 3.2 KB (diverged) |
| Component partials | N/A | 19 files in `html/components/` |
| Template packs | N/A | `frontend/packs/`, `frontend/scss/` |

## Sync Plan

### Scope
1. **22 HTML files** — overwrite `phantom-core/frontend/html/*.html` with reference versions
2. **4 CSS files** — overwrite `phantom-core/frontend/assets/css/*.css` with reference versions
3. **Images** — copy `assets/images/` from reference to Phantom
4. **JS files** — no change (already identical)
5. **Component partials** — no change (Phantom-specific)
6. **Template packs + SCSS** — no change (Phantom-specific)

### Files to copy
Source: `C:\Users\hamma\Downloads\templete\frontend\`
Target: `C:\Users\hamma\Downloads\wordpress\phantom-core\frontend\`

### What is preserved
- `frontend/html/components/*.html` (19 component partials)
- `frontend/assets/js/*` (JS files identical; phantom-bridge.js, phantom-data.js stay)
- `frontend/packs/*` (3 template packs)
- `frontend/scss/*` (SCSS sources)
- All PHP files, renderers, backend logic

## Verification
- `php -l` on all PHP files (ensure no breakage)
- Compare file count and structure between reference and target
- Confirm Font Awesome CDN present in all 22 HTML files
- Confirm sticky add-to-cart bar present in product-detail.html
