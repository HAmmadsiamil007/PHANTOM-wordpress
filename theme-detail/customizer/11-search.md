# Customizer Panel: Search (`phantom_search`)

## Overview

The Search panel controls site-wide search behavior, result display, and live/autocomplete search. Search results are rendered server-side via the `Search_Card` renderer and can optionally use live AJAX autocomplete via `phantom-injector.js`.

## Panel ID

`phantom_search`

## Sections

### 1. Search Settings (`phantom_section_search`)

**10 settings** covering search behavior, result layout, and live search.

#### Core Settings

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `enable_search` | `bool` | `true` | Master toggle — when false, search form and endpoints are disabled. |
| `search_placeholder` | `text` | `Search products...` | Placeholder text for the search input field. |
| `search_enable_live` | `ast-toggle` | `false` | Enable live/autocomplete search with debounced AJAX. |

#### Results Display

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `search_results_layout` | `ast-select` | `grid` | Results display style: `grid`, `list`, `compact`. Selective refresh partial. |
| `search_show_images` | `ast-toggle` | `true` | Show thumbnails in search results. |
| `search_show_price` | `ast-toggle` | `true` | Show product prices in results. |
| `search_show_excerpt` | `ast-toggle` | `true` | Show excerpts in results. |
| `search_excerpt_length` | `int` | `20` | Excerpt word count in results. |

#### Query Settings

| Setting Key | Type | Default | Description |
|---|---|---|---|
| `search_post_types` | `multiselect` | `[post, page, product]` | Post types included in search results. |
| `search_products_per_page` | `int` | `12` | Results per page. |

---

## Code Flow

### Standard (Non-Live) Search

```
User submits search form
  → GET /?s={query}
  → WordPress standard search query runs
  → search.html template loaded
  → Content_Injector::inject_search_content() invoked
    → Queries post types from phantom_search_post_types option
    → For each result: Search_Card::render($post_data) outputs card HTML
      → Respects phantom_search_show_images, phantom_search_show_price, phantom_search_show_excerpt
    → Results rendered in layout mode (grid/list/compact) via class on .search-results container
  → Pagination: phantom_search_products_per_page controls page size
```

### Live Search (AJAC)

```
search_enable_live = true
  → phantom-injector.js initializes search autocomplete
  → User types in search input
  → After 300ms debounce: GET /products?search={query}&per_page=12
  → REST API returns matching products
  → Results rendered via Search_Card component in DOM
  → Dropdown overlay shows live results without page reload
```

### Selective Refresh

The `search_results_layout` setting uses `Selective Refresh` transport. When changed:

1. Customizer sends partial refresh request for `phantom_render_search_partial`
2. `partial-renderers.php` invokes `phantom_render_search_partial()` which re-renders the results grid
3. The partial replaces the `.search-results` container DOM

### Frontend Templates

| Template | Usage |
|---|---|
| `frontend/html/search.html` | Search page — form input, results grid, pagination |
| `includes/renderer/class-search-card.php` | `Search_Card` renderer — outputs individual search result card |

### REST API Dependencies

Live search uses:
- `GET /products?search={query}` — WooCommerce product search
- `GET /posts?search={query}` — WP REST posts search (if `post` in `search_post_types`)

Standard search uses the native WordPress `?s=` query parameter.

---

## Developer Notes

- **No CSS variables**: Search settings are consumed directly by PHP renderers and JS handlers — no CSS custom properties are generated.
- **Live search debounce**: The 300ms debounce is hardcoded in `phantom-injector.js`. Adjust by modifying `SEARCH_DEBOUNCE_MS` constant in the JS file.
- **Search_Card renderer**: `Search_Card::render()` in `includes/renderer/class-search-card.php` follows the same adapter pattern as `Product_Card` and `Blog_Card` — accepts data array, outputs HTML string.
- **Multiselect**: `search_post_types` uses `multiselect` control type. The Customizer renders checkboxes for each public post type. Values are stored as a serialized array.
- **Performance**: When `search_enable_live` is false, no search-related JS is enqueued beyond the basic form submission handler.
