# Phantom Core — WooCommerce Integration Master Plan

## Executive Summary
**Date:** 2026-07-25
**Plugin:** phantom-core v1.5.3 (SPA architecture, static HTML + REST API)
**WooCommerce:** v10.9.4 (separate plugin)
**Current Health Score:** ~40% complete for full WooCommerce feature coverage

The SPA architecture (shell.php + phantom-data.js + REST API) replaces WooCommerce's PHP templates. This means EVERY WooCommerce feature must be implemented via REST API endpoints + JS rendering + HTML templates. Currently, the foundation is solid (cart, products, auth) but critical e-commerce flows are broken or missing.

---

## CRITICAL GAPS (Blocking Full E-Commerce)

### 1. CHECKOUT / ORDER CREATION — Score: 0%
**The SPA CANNOT place orders.** No `/checkout` or `/orders/create` endpoint exists.
- `initCheckout()` POSTs to `/?wc-ajax=checkout` which relies on WC native flow
- No server-side order creation via REST API
- No checkout validation
- **FIX:** Create `/checkout` POST endpoint that: validates cart, creates WC_Order, handles payment, returns order confirmation

### 2. PAYMENT PROCESSING — Score: 0%
No payment gateway integration whatsoever.
- No Stripe, PayPal, bank transfer, COD endpoints
- No payment method listing/selection
- No payment form UI
- **FIX:** Create `/payment-methods` endpoint + Stripe/PayPal integration + payment form in checkout.html

### 3. SHIPPING METHOD SELECTION — Score: 20%
Can READ shipping methods but cannot SET them.
- `get_shipping_methods()` exists but no `set_shipping_method()` endpoint
- No address-based rate calculation
- No shipping calculator on cart page
- **FIX:** Create `/cart/set-shipping` POST endpoint + shipping calculator UI

### 4. ORDER MANAGEMENT (Admin) — Score: 5%
Only `/user/orders` exists for customer order listing.
- No admin order CRUD
- No order status updates
- No refund endpoints
- No order notes
- **FIX:** Create `/admin/orders` endpoints for full order management

### 5. TAX BREAKDOWN — Score: 5%
Tax is embedded in totals but not visible as separate line item.
- `get_cart_data()` has no `tax_total` field
- No tax display in cart/checkout summary
- **FIX:** Add `tax_total` to cart response + tax row in cart/checkout HTML

---

## HIGH-PRIORITY GAPS (Needed for Professional Store)

### 6. CUSTOMER PROFILE & ADDRESSES — Score: 20%
Auth works (login/register/logout) but no profile management.
- No `/user/profile` endpoint
- No billing/shipping address CRUD
- No guest checkout
- **FIX:** Create `/user/profile`, `/user/addresses` endpoints + profile/address UI

### 7. PRODUCT SEARCH & FILTERING — Score: 25%
Backend supports search/filter/sort but JS has NO UI for it.
- No search input handler
- No price range slider
- No sorting dropdown
- No tag/stock/featured filter UI
- **FIX:** Add search handler, price filter, sorting dropdown to shop page JS

### 8. COUPONS — Score: 40%
Can apply/remove coupons in cart but no admin CRUD.
- No coupon creation/listing/management endpoints
- No coupon input HTML in cart template (JS handles it but no form)
- **FIX:** Create `/admin/coupons` endpoints + coupon input in cart.html

### 9. RELATED PRODUCTS / UPSELLS / CROSS-SELLS — Score: 10%
Backend returns related_products in product detail but JS NEVER renders them.
- `related_products` sent but no `injectRelatedProducts()` function
- Cross-sell/upsell IDs returned but not hydrated to objects
- **FIX:** Add related products rendering in product-detail page + hydrate cross-sells/upsells

### 10. REVIEWS — Score: 55%
Backend has get/submit reviews but hardcoded 5-star rating.
- Rating always submits as 5 (line 428 in phantom-data.js)
- No star selector UI
- No review pagination
- No review form in HTML template
- **FIX:** Add star selector, review form HTML, pagination

---

## MEDIUM-PRIORITY GAPS (Needed for Complete Experience)

### 11. ACCOUNT MANAGEMENT — Score: 30%
Only shows order history. No profile editing, address management, password change.
- No tabbed account navigation
- No address forms
- No password change
- **FIX:** Create tabbed account UI with Orders, Addresses, Account Details sections

### 12. ORDER TRACKING — Score: 10%
Order table shows status but no detail view or tracking.
- No order detail page
- No tracking timeline
- No carrier tracking integration
- **FIX:** Create order-detail.html template + `/user/orders/{id}` endpoint

### 13. WISHLIST — Score: 35%
Add/remove from cards works but no wishlist PAGE rendering.
- localStorage-based, no server persistence
- No wishlist page function in JS
- No header counter
- **FIX:** Add `renderWishlistPage()` function + wishlist.html template

### 14. CART COMPLETENESS — Score: 85%
Cart is mostly functional but missing:
- Coupon input HTML in cart.html
- Remove coupon handler in JS
- Tax display
- Free shipping progress bar
- Cross-sells section
- **FIX:** Add coupon form, tax row, free shipping bar, cross-sells

### 15. PRODUCT DETAIL COMPLETENESS — Score: 75%
Strong but missing:
- No gallery carousel initialization
- No product video support
- No qty selector on detail page
- No social sharing
- **FIX:** Init Swiper, add video player, add qty input, add share buttons

---

## LOW-PRIORITY GAPS (Nice to Have)

### 16. MULTI-CURRENCY — Score: 0%
All prices hardcoded to `$`. No currency switching.
- **FIX:** Use `price_html` from API instead of hardcoded `$`

### 17. MULTI-LANGUAGE — Score: 10%
Strings use `__()` for translation but no language switching.
- **FIX:** Add WPML/Polylang integration endpoints

### 18. PRODUCT COMPARISON — Score: 0%
No compare functionality at all.
- **FIX:** Create compare feature with localStorage + compare.html template

### 19. REPORTS & ANALYTICS — Score: 0%
No reporting endpoints.
- **FIX:** Create `/admin/reports` endpoints for sales, revenue, product performance

### 20. TAX MANAGEMENT — Score: 5%
Tax calculates but no admin management.
- **FIX:** Create `/admin/taxes` endpoints for rate/class management

---

## COMPLETE ENDPOINT INVENTORY

### Existing (31 endpoints)
| Endpoint | Method | Status |
|----------|--------|--------|
| `/products` | GET/POST | Working |
| `/products/featured` | GET | Working |
| `/products/{id}` | GET/PUT/DELETE | Working |
| `/categories` | GET | Working |
| `/cart` | GET | Working |
| `/cart/add` | POST | Working |
| `/cart/update` | POST | Working |
| `/cart/remove` | POST | Working |
| `/cart/coupon` | POST | Working |
| `/cart/remove-coupon` | POST | Working |
| `/cart/shipping-methods` | POST | Working |
| `/woo/attributes` | GET | Working |
| `/woo/variations` | GET | Working |
| `/woo/reviews` | GET/POST | Working |
| `/auth/login` | POST | Working |
| `/auth/register` | POST | Working |
| `/auth/password-reset` | POST | Working |
| `/auth/logout` | POST | Working |
| `/user/orders` | GET | Working |
| `/page-data` | GET | Working |
| `/contact` | POST | Working |
| `/posts` | GET | Working |
| `/posts/{slug}` | GET | Working |
| `/menus` | GET | Working |
| `/settings` | GET | Working |

### Missing (Must Create)
| Endpoint | Method | Priority |
|----------|--------|----------|
| `/checkout` | POST | CRITICAL |
| `/payment-methods` | GET | CRITICAL |
| `/cart/set-shipping` | POST | CRITICAL |
| `/cart/clear` | POST | HIGH |
| `/orders/{id}` | GET | HIGH |
| `/orders/{id}/refunds` | POST | HIGH |
| `/user/profile` | GET/PUT | HIGH |
| `/user/addresses` | GET/POST/PUT/DELETE | HIGH |
| `/admin/orders` | GET | HIGH |
| `/admin/orders/{id}` | GET/PUT | HIGH |
| `/admin/coupons` | GET/POST | HIGH |
| `/admin/reports/sales` | GET | MEDIUM |
| `/products/{id}/related` | GET | MEDIUM |
| `/products/tags` | GET | MEDIUM |
| `/woo/tags` | GET | MEDIUM |
| `/tax/rates` | GET/POST | LOW |
| `/tax/classes` | GET/POST | LOW |

---

## HTML TEMPLATE GAPS

### Templates That Need Major Work
1. **checkout.html** — Needs billing/shipping split, payment gateway UI, order notes, guest checkout toggle
2. **cart.html** — Needs coupon input form, tax display, free shipping bar, shipping calculator
3. **account.html** — Needs tabbed navigation, address forms, password change, download section
4. **product-detail.html** — Needs review form with star selector, social sharing, gallery init
5. **shop.html** — Needs search input, sorting dropdown, price filter, product count display

### Templates That Need Creation
1. **order-detail.html** — Individual order view with items, status timeline, tracking
2. **compare.html** — Side-by-side product comparison table
3. **search-results.html** — Dedicated search results page
4. **category.html** — Category archive page
5. **address-form.html** — Billing/shipping address management

---

## JAVASCRIPT GAPS

### Functions That Need Creation
1. `renderWishlistPage()` — Render wishlist items from localStorage
2. `injectRelatedProducts()` — Render related products on detail page
3. `initSearch()` — Handle search input, debounce, API call
4. `initPriceFilter()` — Price range slider UI + API filtering
5. `initSorting()` — Sort dropdown change handler
6. `initAddressForms()` — Billing/shipping address CRUD
7. `initOrderDetail()` — Render single order with line items
8. `initCompare()` — Product comparison table
9. `initTaxDisplay()` — Show tax line in cart/checkout
10. `initShippingCalculator()` — Address input for shipping rates

### Functions That Need Fixing
1. `injectProductReviews()` — Fix hardcoded 5-star rating (line 428)
2. `buildProductCard()` — Use `price_html` instead of hardcoded `$`
3. `renderProduct()` — Add gallery carousel init, qty selector
4. `initCheckout()` — Add form validation, payment method selection
5. `injectCart()` — Add coupon input, tax display, free shipping bar

---

## IMPLEMENTATION PHASES

### Phase 1: Critical (Week 1-2)
1. Create `/checkout` endpoint with order creation
2. Create `/payment-methods` endpoint + Stripe integration
3. Create `/cart/set-shipping` endpoint
4. Fix checkout.html with payment gateway UI
5. Add tax_total to cart response + display
6. Add coupon input to cart.html
7. Add review star selector

### Phase 2: High Priority (Week 3-4)
8. Create `/user/profile` and `/user/addresses` endpoints
9. Create account.html tabbed navigation
10. Add product search UI to shop page
11. Add sorting dropdown to shop page
12. Add price filter to shop page
13. Fix related products rendering
14. Create `/admin/orders` endpoints
15. Add order detail view

### Phase 3: Medium Priority (Week 5-6)
16. Create wishlist page rendering
17. Add shipping calculator to cart
18. Add free shipping progress bar
19. Add social sharing to product detail
20. Add gallery carousel initialization
21. Add product video support
22. Create `/admin/coupons` endpoints

### Phase 4: Low Priority (Week 7-8)
23. Add multi-currency support
24. Add product comparison
25. Add reports endpoints
26. Add tax management endpoints
27. Add search results template
28. Add category archive template
