# Customizer Panel: Header (`phantom_header`)

**Panel ID:** `phantom_header`
**Sections:** `header`, `topbar`, `navigation`, `announcement_bar`

---

## Header Section (`phantom_section_header`)

**Settings (27):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `display_header` | `ast-toggle` | — | Enable/disable header |
| `header_style` | `ast-select` | — | Header style. Live preview: `document.body` class toggle (adds `header-{style}`). Selective refresh partial. |
| `header_bg` | `ast-color` | `--header-bg` | |
| `header_text_color` | `ast-color` | `--header-color` | |
| `header_padding_x` | `int` | `--header-padding-x` (px) | Responsive: desktop/tablet/mobile |
| `header_padding_y` | `int` | `--header-padding-y` (px) | Responsive |
| `header_border_color` | `ast-color` | `--header-border-color` | |
| `header_border_width` | `int` | `--header-border-width` (px) | |
| `header_height` | `int` | `--header--height` (px) | |
| `header_mobile_height` | `int` | `--header-mobile-height` (px) | |
| `header_banner_height` | `int` | `--banner-height` (px) | |
| `menu_font_size` | `int` | `--menu--font--size` (px) | |
| `enable_live_search` | `ast-toggle` | — | Enable search in header |
| `search_placeholder` | `string` | — | Search placeholder text |
| Various header element visibility toggles | `ast-toggle` | — | |

### Code Flow

```
User changes header style
  → phantom_header_style saved
  → customizer-preview.js adds header-{style} class to document.body
  → CSS rules for .header-centered, .header-bordered, etc. apply
  → Selective refresh partial re-renders header via phantom_render_header_partial_v2()
```

### Frontend

`header.php` renders Bootstrap 5 navbar with `custom_logo`, primary menu, WooCommerce cart icon. AETHER templates have static header in `index.html`.

---

## Topbar Section (`phantom_section_topbar`)

**Settings (6):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `topbar_enable` | `ast-toggle` | — | Enable topbar |
| `topbar_bg` | `ast-color` | `--topbar--bg` | |
| `topbar_text` | `ast-color` | `--topbar--text` | |
| `topbar_content` | `string` | — | Topbar text/HTML |
| `topbar_left_content` | `repeater` | — | Left side items |
| `topbar_right_content` | `repeater` | — | Right side items |

---

## Navigation Section (`phantom_section_navigation`)

**Settings (16):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `menu_location` | `ast-select` | — | Which menu to display. Selective refresh partial. |
| `footer_nav` | `ast-toggle` | — | Show footer navigation |
| `footer_support` | `ast-toggle` | — | Show support link |
| `mobile_menu_breakpoint` | `int` | — | Breakpoint for mobile menu |
| `nav_menu_height` | `int` | `--nav-menu-height` (px) | |
| `nav_submenu_width` | `int` | `--nav-submenu-width` (px) | |
| Various nav element toggles and style settings | — | — | |

---

## Announcement Bar Section (`phantom_section_announcement_bar`)

**Settings (4):**

| Setting | Control | CSS Var | Notes |
|---------|---------|---------|-------|
| `announcement_bar_enable` | `ast-toggle` | — | Enable announcement bar |
| `announcement_bar_bg` | `ast-color` | `--announcement-bar-bg` | |
| `announcement_bar_text_color` | `ast-color` | `--announcement-bar-color` | |
| `announcement_bar_text` | `text` | — | Announcement text |

### Code Flow

```
All header settings
  → header.php CSS module (priority 30) reads settings
  → outputs CSS vars
  → header.php template uses vars for styling
```
