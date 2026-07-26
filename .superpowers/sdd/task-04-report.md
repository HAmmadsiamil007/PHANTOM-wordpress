# T0.4 — Create Component Templates

**Status:** ✅ Complete

**Commit:** `2b5be9a` — feat(phase0): create component template directory with 3 HTML templates

## Files Created

| File | Size | Description |
|------|------|-------------|
| `phantom-core/frontend/html/components/product-card.html` | 914 B | Product card with badge, image, title, rating, categories, price, add-to-cart |
| `phantom-core/frontend/html/components/category-card.html` | 425 B | Category card with image, title, product count |
| `phantom-core/frontend/html/components/blog-card.html` | 536 B | Blog post card with image, date, title, excerpt, read more |

## Verification

- All 3 files verified present in `phantom-core/frontend/html/components/`
- All placeholders use `{{UPPER_SNAKE_CASE}}` format
- No PHP code in templates
- All images include `loading="lazy"`
- Pure HTML, no logic
