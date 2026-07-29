# Advanced Panel — `phantom_advanced`

## Panel Overview

| Property | Value |
|----------|-------|
| Panel ID | `phantom_advanced` |
| Sections | 3 |
| Total Settings | 22 |
| Control Types | `ast-toggle` (3), `string` (9), `text` (2), `code` (3), `code` (1 JSON) |

---

## Section: `integrations` (`phantom_section_integrations`)

**Settings (15)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `social_facebook` | `string` | — | Facebook URL |
| `social_twitter` | `string` | — | Twitter URL |
| `social_instagram` | `string` | — | Instagram URL |
| `social_youtube` | `string` | — | YouTube URL |
| `social_tiktok` | `string` | — | TikTok URL |
| `social_pinterest` | `string` | — | Pinterest URL |
| `social_linkedin` | `string` | — | LinkedIn URL |
| `social_github` | `string` | — | GitHub URL |
| `google_analytics_id` | `string` | — | GA tracking ID |
| `google_tag_manager_id` | `string` | — | GTM container ID |
| `facebook_pixel_id` | `string` | — | FB pixel ID |
| `cookie_consent_enable` | `ast-toggle` | `false` | Enable cookie consent banner |
| `cookie_consent_text` | `text` | — | Cookie consent message |
| `mailchimp_api_key` | `string` | — | Mailchimp API key |
| `mailchimp_list_id` | `string` | — | Mailchimp list ID |

### Code Flow

```
Integration settings saved
    ↓
Bridge system detects active plugins
    ↓
Bridges auto-initialize (WooCommerce, Mailchimp, etc.)
    ↓
Social URLs rendered in footer / social links component
    ↓
Analytics scripts injected by Asset_Engine
```

### Analytics Injection

| Setting | Injected Script |
|---------|-----------------|
| `google_analytics_id` | GA4 `gtag.js` |
| `google_tag_manager_id` | GTM `<script>` + `<noscript>` iframe |
| `facebook_pixel_id` | FB Pixel `fbevents.js` |

Analytics scripts are injected by `Asset_Engine::inject_essential_only()` in `<head>`.

### Social URLs

Social URLs are rendered in the footer social links component. If a URL is empty, its icon/link is omitted from the rendered output.

---

## Section: `custom_code` (`phantom_section_custom_code`)

**Settings (4)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `custom_css` | `code` | — | Custom CSS editor |
| `custom_js_head` | `code` | — | Custom JS in `<head>` |
| `custom_js_footer` | `code` | — | Custom JS before `</body>` |
| `custom_html_head` | `code` | — | Custom HTML in `<head>` |

### Code Flow

```
User writes CSS in custom code editor
    ↓
Saved as phantom_custom_css
    ↓
Asset_Engine::inject_essential_only() outputs:
    <style id="phantom-custom-css">{value}</style>
in <head>
```

```
User writes JS
    ↓
Saved as phantom_custom_js_head or phantom_custom_js_footer
    ↓
Asset_Engine injects at appropriate location:
    <head> → <script>{value}</script>
    Footer → <script>{value}</script> before wp_footer close
```

### Output Locations

| Setting | Output Position |
|---------|-----------------|
| `custom_css` | `<style id="phantom-custom-css">` in `wp_head` |
| `custom_js_head` | `<script>` in `wp_head` |
| `custom_js_footer` | `<script>` before `</body>` close |
| `custom_html_head` | Raw HTML in `wp_head` |

### Security Notes

- Custom code is stored as raw content in `wp_options`.
- Output is **not escaped** — it is trusted admin-entered code.
- Only users with `edit_theme_options` capability can access these settings.

---

## Section: `import_export` (`phantom_section_import_export`)

**Settings (3)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `import_export_settings` | `code` | — | Settings export/import (JSON) |
| `import_export_enable` | `ast-toggle` | `false` | Enable advanced import |
| `import_export_backup` | `code` | — | Full site backup |

### Code Flow — Export

```
User clicks Export
    ↓
Settings_Registry::get_schema() → JSON encode
    ↓
Browser downloads .json file
```

### Code Flow — Import

```
User uploads JSON file
    ↓
JSON decode → validate structure
    ↓
Settings_Registry::set() for each key
    ↓
Customizer::sync_options()
    ↓
CSS cache flushed
```

### Backup

`import_export_backup` stores a full site backup as serialized JSON. Includes:
- All Phantom settings (612+)
- Customizer mods
- Custom CSS/JS
- Widget configurations

### Security Notes

- Import validates JSON structure before applying.
- Only users with `edit_theme_options` capability can import/export.
- Import overwrites existing settings — no merge by default.

---

## Key Files

| File | Purpose |
|------|---------|
| `includes/class-rest-controller.php` | REST endpoints for settings import/export |
| `includes/class-settings-registry.php` | `get_schema()`, `set()` — settings I/O |
| `includes/Engine/Asset_Engine.php` | Injects custom CSS/JS into document |
| `includes/Settings/class-settings-loader.php` | Creates Customizer controls |
| `includes/class-customizer.php` | `sync_options()` — syncs Customizer to options |
| `admin/class-settings-page.php` | Tabbed admin UI for import/export |
