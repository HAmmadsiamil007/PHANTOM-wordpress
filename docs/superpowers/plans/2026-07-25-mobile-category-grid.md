# Mobile Category Grid Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use subagent-driven-development or executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Fix mobile category grid to render as clean single-column stack

**Architecture:** Add `grid-template-areas` reset at ≤ 768px breakpoint in responsive.css to override the desktop 3-column named grid areas, and remove `min-height: 500px` on mobile so cards size naturally.

**Tech Stack:** CSS (existing responsive.css), Docker (optix_wordpress container)

## Global Constraints
- Must keep desktop named-grid-area layout intact
- Must use existing breakpoint (≤ 768px)
- Grid-area names must match desktop: `large`, `top-mid`, `bottom-left`, `accent`
- Toys must render last in stack (bottom position) with gold accent
- All cards: 16:9 aspect ratio on mobile (already specified in existing CSS)

---
### Task 1: Fix mobile category grid CSS

**Files:**
- Modify: `phantom-core/frontend/assets/css/responsive.css` — ≤ 768px breakpoint (line 649-651)
- Modify: Same file in `phantom-core-v3` inside Docker container

- [ ] **Step 1: Update the .category-grid rule at ≤ 768px breakpoint**

Change lines 649-651 from:
```css
.category-grid {
    grid-template-columns: 1fr;
}
```
To:
```css
.category-grid {
    grid-template-columns: 1fr;
    grid-template-areas:
        "large"
        "top-mid"
        "bottom-left"
        "accent";
    min-height: auto;
}
```

- [ ] **Step 2: Push to Docker and verify**

Run:
```bash
docker cp phantom-core/frontend/assets/css/responsive.css optix_wordpress:/var/www/html/wp-content/plugins/phantom-core-v3/frontend/assets/css/responsive.css
```

- [ ] **Step 3: Verify on mobile viewport**

Open browser at 390px width, check `.category-grid` computed style has:
- `grid-template-columns: 1fr`
- `grid-template-areas: "large" "top-mid" "bottom-left" "accent"`

- [ ] **Step 4: Commit**

```bash
git add phantom-core/frontend/assets/css/responsive.css
git commit -m "fix: mobile category grid single-column stack with proper grid-area reset"
```
