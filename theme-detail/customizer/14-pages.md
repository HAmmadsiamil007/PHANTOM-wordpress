# Pages Panel — `phantom_pages`

## Panel Overview

| Property | Value |
|----------|-------|
| Panel ID | `phantom_pages` |
| Sections | 14 |
| Total Settings | 91 |
| Control Types | `ast-toggle` (22), `text` (18), `string` (12), `number` (6), `image` (2), `code` (2), `repeater` (3), `array` (3) |

---

## Section: `about_page` (`phantom_section_about_page`)

**Settings (16)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `about_enable` | `ast-toggle` | `false` | Enable custom about page |
| `about_hero_title` | `text` | — | Hero heading |
| `about_hero_description` | `text` | — | Hero description |
| `about_hero_image` | `image` | — | Hero background image |
| `about_team_enable` | `ast-toggle` | `true` | Show team section |
| `about_team_title` | `text` | — | Team section heading |
| `about_values_enable` | `ast-toggle` | `true` | Show values section |
| `about_values_title` | `text` | — | Values heading |
| `about_story_enable` | `ast-toggle` | `true` | Show story section |
| `about_story_title` | `text` | — | Story heading |
| `about_story_content` | `text` | — | Story content |
| `about_mission_enable` | `ast-toggle` | `true` | Show mission section |
| `about_mission_title` | `text` | — | Mission heading |
| `about_mission_content` | `text` | — | Mission content |
| `about_team_repeater` | `repeater` | — | Team members |
| `about_values_repeater` | `repeater` | — | Company values |

---

## Section: `contact_page` (`phantom_section_contact_page`)

**Settings (14)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `contact_enable` | `ast-toggle` | `false` | Enable custom contact page |
| `contact_form_heading` | `text` | — | Form heading |
| `contact_form_subheading` | `text` | — | Form subheading |
| `contact_email` | `string` | — | Contact email |
| `contact_phone` | `string` | — | Phone number |
| `contact_address` | `string` | — | Physical address |
| `contact_map_enable` | `ast-toggle` | `false` | Show map |
| `contact_mapApiKey` | `string` | — | Google Maps API key |
| `contact_map_lat` | `string` | — | Map latitude |
| `contact_map_lng` | `string` | — | Map longitude |
| `contact_social_repeater` | `repeater` | — | Social links |
| `contact_form_shortcode` | `string` | — | CF7/Form shortcode |
| `contact_business_hours` | `text` | — | Business hours |
| `contact_success_message` | `text` | — | Form success message |

---

## Section: `faq_page` (`phantom_section_faq_page`)

**Settings (6)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `faq_enable` | `ast-toggle` | `false` | Enable custom FAQ |
| `faq_heading` | `text` | — | Page heading |
| `faq_subheading` | `text` | — | Page subheading |
| `faq_items` | `array` | — | FAQ items (question/answer pairs) |
| `faq_schema_enable` | `ast-toggle` | `true` | Enable FAQ structured data |
| `faq_columns` | `int` | `2` | Layout columns |

---

## Section: `coming_soon` (`phantom_section_coming_soon`)

**Settings (5)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `coming_soon_enable` | `ast-toggle` | `false` | Enable coming soon page |
| `coming_soon_title` | `string` | — | Headline |
| `coming_soon_description` | `text` | — | Description |
| `coming_soon_date` | `string` | — | Launch date |
| `maintenance_mode` | `ast-toggle` | `false` | Enable maintenance mode |

---

## Section: `error_404` (`phantom_section_error_404`)

**Settings (3)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `404_title` | `text` | — | 404 heading |
| `404_description` | `text` | — | 404 description |
| `404_button_text` | `string` | — | Back to home button text |

---

## Section: `login_page` (`phantom_section_login_page`)

**Settings (9)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `login_enable` | `ast-toggle` | `false` | Enable custom login |
| `login_heading` | `text` | — | Login form heading |
| `login_subheading` | `text` | — | Form subheading |
| `login_logo` | `image` | — | Login page logo |
| `login_bg_color` | `string` | — | Background color |
| `login_redirect_url` | `string` | — | Redirect after login |
| `login_show_remember` | `ast-toggle` | `true` | Show remember me |
| `login_show_forgot` | `ast-toggle` | `true` | Show forgot password |
| `login_social_enable` | `ast-toggle` | `false` | Show social login |

---

## Section: `register_page` (`phantom_section_register_page`)

**Settings (9)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `register_enable` | `ast-toggle` | `false` | Enable custom register |
| `join_heading` | `text` | — | Registration heading |
| `join_subheading` | `text` | — | Form subheading |
| `join_logo` | `image` | — | Register page logo |
| `join_bg_color` | `string` | — | Background color |
| `join_redirect_url` | `string` | — | Redirect after register |
| `join_show_terms` | `ast-toggle` | `true` | Show terms checkbox |
| `join_social_enable` | `ast-toggle` | `false` | Show social register |
| `join_fields` | `array` | — | Custom fields |

---

## Section: `portfolio` (`phantom_section_portfolio`)

**Settings (3)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `portfolio_posts_per_page` | `int` | `12` | Posts per page |
| `portfolio_columns` | `int` | `3` | Grid columns |
| `portfolio_enable_lightbox` | `ast-toggle` | `true` | Enable lightbox |

---

## Section: `thank_you` (`phantom_section_thank_you`)

**Settings (5)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `thank_you_enable` | `ast-toggle` | `false` | Enable custom thank you |
| `thank_you_heading` | `text` | — | Thank you heading |
| `thank_you_description` | `text` | — | Thank you message |
| `thank_you_continue_text` | `string` | — | Continue shopping text |
| `thank_you_track_enable` | `ast-toggle` | `true` | Show order tracking |

---

## Section: `load_more` (`phantom_section_load_more`)

**Settings (8)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `load_more_enable` | `ast-toggle` | `true` | Enable load more button |
| `load_more_text` | `string` | — | Button text |
| `load_more_loading_text` | `string` | — | Loading state text |
| `load_more_style` | `string` | — | Button style |
| `load_more_animation` | `string` | — | Loading animation |
| `load_more_per_page` | `int` | `12` | Items per load |
| `load_more_infinite_scroll` | `ast-toggle` | `false` | Enable infinite scroll |
| `load_more_scroll_offset` | `int` | `200` | Trigger offset in px |

---

## Section: `privacy` (`phantom_section_privacy`)

**Settings (2)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `privacy_heading` | `text` | — | Page heading |
| `privacy_content` | `code` | — | Privacy policy content (HTML) |

---

## Section: `terms` (`phantom_section_terms`)

**Settings (2)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `terms_heading` | `text` | — | Page heading |
| `terms_content` | `code` | — | Terms content (HTML) |

---

## Section: `team` (`phantom_section_team`)

**Settings (6)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `team_heading` | `text` | — | Section heading |
| `team_subheading` | `text` | — | Section subheading |
| `team_columns` | `int` | `3` | Grid columns |
| `team_show_social` | `ast-toggle` | `true` | Show social links |
| `team_members` | `array` | — | Team member data |
| `team_repeater` | `repeater` | — | Team member repeater |

---

## Section: `testimonials` (`phantom_section_testimonials`)

**Settings (3)**

| Setting Key | Control Type | Default | Description |
|-------------|-------------|---------|-------------|
| `testimonials_heading` | `text` | — | Section heading |
| `testimonials_subheading` | `text` | — | Section subheading |
| `testimonials_items` | `repeater` | — | Testimonial items |

---

## Code Flow

Each page section is **independent**. Settings control content displayed on specific WordPress pages. The AETHER templates (`about.html`, `contact.html`, etc.) contain static markup that can be customized via these settings.

## Frontend Behavior

Each page template reads its corresponding settings to display dynamic content:

| Page | Template | Notes |
|------|----------|-------|
| About | `about.html` | Sections toggled via `about_*_enable` |
| Contact | `contact.html` | Form via shortcode, map via JS API |
| FAQ | `faq.html` | Accordion from `faq_items` array |
| Coming Soon | `coming_soon.html` | Countdown from `coming_soon_date` |
| 404 | `404.html` | Static fallback |
| Login | `login.html` | Custom WP login |
| Register | `register.html` | Custom WP registration |
| Portfolio | `portfolio.html` | Grid + lightbox |
| Thank You | `thank-you.html` | Order confirmation |

Some pages use **server-side injection** (contact form, FAQ accordion), while others use static HTML customized by settings.

## Key Files

| File | Purpose |
|------|---------|
| `includes/Engine/View_Engine.php` | Renders page-specific sections |
| `includes/class-settings-registry.php` | Registers all page settings |
| `includes/Settings/class-settings-loader.php` | Creates Customizer controls |
| `frontend/html/about.html` | About page template |
| `frontend/html/contact.html` | Contact page template |
| `frontend/html/faq.html` | FAQ page template |
| `frontend/html/404.html` | 404 page template |
