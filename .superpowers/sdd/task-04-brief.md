# T0.4 — Create Component Templates

**Goal:** Create 3 HTML component template files in the components directory.

**Directory:** `phantom-core/frontend/html/components/`

## Files to Create

### `product-card.html`
```html
<div class="product-card" data-product-id="{{PRODUCT_ID}}">
    <div class="product-card__badge">{{BADGE}}</div>
    <a href="{{PERMALINK}}" class="product-card__image-link">
        <img src="{{IMAGE}}" alt="{{TITLE}}" loading="lazy" class="product-card__image">
    </a>
    <div class="product-card__body">
        <h3 class="product-card__title">
            <a href="{{PERMALINK}}">{{TITLE}}</a>
        </h3>
        <div class="product-card__rating">
            <span class="product-card__stars">{{RATING_HTML}}</span>
            <span class="product-card__reviews">({{REVIEW_COUNT}})</span>
        </div>
        <div class="product-card__categories">{{CATEGORIES}}</div>
        <div class="product-card__price">{{PRICE}}</div>
        <div class="product-card__actions">
            <a href="{{ADD_TO_CART_URL}}" class="product-card__atc-btn">{{ADD_TO_CART_TEXT}}</a>
        </div>
    </div>
</div>
```

### `category-card.html`
```html
<div class="category-card" data-category-id="{{CATEGORY_ID}}">
    <a href="{{PERMALINK}}" class="category-card__link">
        <div class="category-card__image-wrap">
            <img src="{{IMAGE}}" alt="{{NAME}}" loading="lazy" class="category-card__image">
        </div>
        <h3 class="category-card__title">{{NAME}}</h3>
        <span class="category-card__count">{{COUNT}} {{PRODUCTS_TEXT}}</span>
    </a>
</div>
```

### `blog-card.html`
```html
<div class="blog-card" data-post-id="{{POST_ID}}">
    <a href="{{PERMALINK}}" class="blog-card__image-link">
        <img src="{{IMAGE}}" alt="{{TITLE}}" loading="lazy" class="blog-card__image">
    </a>
    <div class="blog-card__body">
        <span class="blog-card__date">{{DATE}}</span>
        <h3 class="blog-card__title">
            <a href="{{PERMALINK}}">{{TITLE}}</a>
        </h3>
        <p class="blog-card__excerpt">{{EXCERPT}}</p>
        <span class="blog-card__read-more">{{READ_MORE_TEXT}}</span>
    </div>
</div>
```

## Rules
- All placeholders use `{{UPPER_SNAKE_CASE}}` format
- No PHP code in templates
- All images include `loading="lazy"`
- Pure HTML, no logic

## Verification
```bash
ls phantom-core/frontend/html/components/
```
Expected: 3 files listed.

## Commit
```bash
cd C:\Users\hamma\Downloads\wordpress && git add phantom-core/frontend/html/components/ && git commit -m "feat(phase0): create component template directory with 3 HTML templates"
```
