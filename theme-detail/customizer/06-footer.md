# Customizer Panel: Footer (`phantom_footer`)

**Panel ID:** `phantom_footer`
**Section:** `footer` (`phantom_section_footer`)

---

## Footer Section (`phantom_section_footer`)

**Settings (27):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `display_footer` | `ast-toggle` | — | Enable/disable footer |
| `footer_layout` | `ast-select` | — | Footer column layout. Selective refresh partial. |
| `footer_columns` | `int` | — | Live preview: footer `.row` class toggle (adds `row-cols-{n}`) |
| `footer_bg_color` | `ast-color` | `--footer--bg` | |
| `footer_text` | `ast-color` | `--footer--text` | |
| `footer_heading_text` | `ast-color` | `--footer-heading` | |
| `footer_link` | `ast-color` | `--footer-link` | |
| `footer_border_color` | `ast-color` | `--footer-border-color` | |
| `footer_logo` | `image` | — | Footer logo. Live preview: `.footer-logo img` `src`/`backgroundImage` |
| `footer_about_text` | `text` | — | About text. Live preview: `p.footer-tagline` `textContent` + line breaks |
| `footer_address` | `text` | — | Address. Live preview: `.footer-newsletter p` `textContent` + line breaks |
| `footer_copyright` | `text` | — | Copyright text. Live preview: `.footer-legal span:first-child` `textContent` with `%d` replacement |
| `newsletter_enable` | `ast-toggle` | — | Enable newsletter form |
| `newsletter_title` | `text` | — | Newsletter heading |
| `newsletter_description` | `text` | — | Newsletter description |
| `footer_social_repeater` | `repeater` | — | Social media links |
| `footer_nav_repeater` | `repeater` | — | Footer navigation links |
| Various footer element visibility toggles | `ast-toggle` | — | |

### Code Flow

```
User changes footer color
  → ast-color saves hex
  → footer.php module (priority 40) reads
  → outputs :root { --footer--bg: #141416; }
  → footer.php template applies via CSS vars
```

### Frontend

`footer.php` renders newsletter form, 4-column widget area, navigation, contact info, copyright, payment cards. AETHER templates have static footer.

---

## Live Preview Bindings

| Setting | Target Selector | Property |
|---------|----------------|----------|
| `footer_columns` | `.row` | Class toggle: `row-cols-{n}` |
| `footer_logo` | `.footer-logo img` | `src` / `backgroundImage` |
| `footer_about_text` | `p.footer-tagline` | `textContent` (+ line breaks) |
| `footer_address` | `.footer-newsletter p` | `textContent` (+ line breaks) |
| `footer_copyright` | `.footer-legal span:first-child` | `textContent` (replaces `%d`) |
