# FleetPress Rental – WordPress (Custom Theme + Custom Plugin)

**Goal:** Minimal, production-ready base for a car & scooter rental site on WordPress:
- A **lightweight theme** (UI only).
- A **custom plugin** (“FleetPress Rental Core”) handling inventory, seasonal rates, availability, bookings, Stripe Checkout, emails, roles, customer dashboard (frontend-only), and admin backoffice.

**Language:** English by default, fully internationalizable (gettext).

---

## 1) Non-functional constraints
- **No external WP plugins** (except our custom plugin). Composer libraries allowed: `stripe/stripe-php`, `endroid/qr-code`.
- **PHP ≥ 8.1**, **MySQL/MariaDB with transactions**, **Node ≥ 18**, **Composer ≥ 2**.
- WordPress Coding Standards (WPCS), security-first (sanitize/escape/nonces/capabilities), performance-conscious.

## 2) Business rules (MVP)
- Two asset types: **Car** and **Scooter** (extensible).
- **Inventory model:** each “Vehicle” entry has an **inventory_count** (units available). Assignment is capacity-based (no per-unit tracking in MVP).
- **Booking unit:** **per day** (date range). Pick-up/return times can be added later; MVP assumes calendar days.
- **Pricing:** **seasonal daily rate** per vehicle; optional deposit (future).
- **Availability:** capacity − (confirmed reservations across the date range, plus admin blocks).
- **Statuses:** `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`.
- **Cancellation policy (MVP):** free cancellation if **> 72h** before start, otherwise disabled.
- **Payments:** Stripe **Checkout**; webhooks are the source of truth.

## 3) Architecture
**Theme:** UI, templates/blocks; strictly no business logic.  
**Plugin (`fleetpress-rental-core`):** DB tables, REST API, Stripe, emails, roles, cron, admin UI, customer frontend dashboard.

### 3.1 Data model (custom tables)
- `fpr_vehicles` (optional for performance) or use **CPT `vehicle`** with postmeta. MVP uses CPT; availability/rates use custom tables.
- `fpr_rates (id, vehicle_id, season_id, daily_rate_cents, currency)`
- `fpr_seasons (id, name, date_start, date_end)`
- `fpr_blocks (id, vehicle_id, date_start, date_end, reason)` — admin blackout/maintenance periods
- `fpr_calendar (vehicle_id, date, reserved_count, capacity)` — derived daily capacity (inventory − blocks), unique `(vehicle_id,date)`
- `fpr_bookings (id, public_id, user_id, vehicle_id, date_start, date_end, qty, amount_cents, currency, status, created_at, updated_at)`
- `fpr_payments (id, booking_id, stripe_session_id, stripe_payment_intent, amount_cents, status, raw_webhook_json, created_at, updated_at)`

**Indexes & constraints**
- Unique `(vehicle_id, date)` on `fpr_calendar`.
- Index `(vehicle_id, date_start, date_end)` on `fpr_bookings`.
- Transactions with `SELECT ... FOR UPDATE` on relevant `fpr_calendar` rows.

### 3.2 REST API (prefix `fpr/v1`)
- `GET /vehicles?type=car|scooter&from=YYYY-MM-DD&to=YYYY-MM-DD` → list with min availability & estimated total price.
- `GET /availability?vehicle_id=ID&from=YYYY-MM-DD&to=YYYY-MM-DD` → per-day remaining capacity + price breakdown.
- `POST /booking` → `{ vehicle_id, from, to, qty, customer:{email, first_name, last_name, phone} }`  
  Creates `pending` booking + Stripe Checkout Session → returns `{ checkout_url, booking_public_id }`.
- `GET /booking/:public_id` → booking details (auth: owner).
- `POST /cancel` → `{ booking_public_id }` (enforce 72h rule) → `status: cancelled`.
- `POST /stripe/webhook` → verify signature; on `payment_intent.succeeded`: mark payment `paid`, booking `confirmed`, and **increment** `fpr_calendar.reserved_count` for each day in range (transactional lock). On `checkout.session.expired`/failed: expire/cancel pending.

### 3.3 Availability & anti-overbooking
- **MVP rule:** Seats are allocated **only** on **webhook success**.  
- Webhook confirmation runs a **transaction**:
  1) Lock all `fpr_calendar` rows for `[from, to)` via `SELECT ... FOR UPDATE`.
  2) Validate `reserved_count + qty ≤ capacity` for **every** day.
  3) If OK, increment `reserved_count` on each day; set booking `confirmed`.
  4) Else, set booking `cancelled` and (optional) auto-refund (future).

### 3.4 Stripe Checkout
- Server creates Checkout Session with single line item: `daily_rate × number_of_days × qty`.
- Success/Cancel URLs handled by theme routes/templates.
- Store `stripe_session_id` and `payment_intent`; trust **webhook** only.

### 3.5 Emails (transactional)
**Customer:** account created (set password link), booking status updates (`pending` → `confirmed` → reminders → `in_progress` → `completed` → `cancelled`), receipt.  
**Admin:** new user, new booking, payment received, cancellations.  
Templates as HTML partials with gettext placeholders.

### 3.6 Roles & access
- Create role `fpr_customer` (`read` only).
- On first booking: auto-create user, assign role, send credentials email.
- Post-login redirect to `/dashboard`; **hide admin bar**; block `/wp-admin` for customers.

### 3.7 Frontend (MVP screens)
- **Homepage:** search (from/to, type), featured vehicles, CTA.
- **Vehicle detail:** gallery/specs, live availability & total pricing, booking form → Stripe.
- **Customer dashboard `/dashboard`:** upcoming bookings, status badges, QR code for pick-up, profile, cancellation (>72h).
- **Admin (backend):** Vehicles (CPT), Rates & Seasons, Blocks, Calendar view (per day), Bookings list, Reports, Settings (capacity, price defaults, Stripe keys, policies).

---

## 4) Project layout (to be generated by agents)
wp-content/
plugins/
fleetpress-rental-core/
fleetpress-rental-core.php
composer.json
vendor/ (generated)
includes/
class-plugin.php
class-activator.php
class-cpt-vehicle.php
class-rest.php
class-availability.php
class-pricing.php
class-booking.php
class-stripe.php
class-emails.php
class-roles.php
class-cron.php
helpers.php
admin/
class-admin-menu.php
views/.php
frontend/
class-frontend.php
templates/.php
emails/
templates/.php
assets/
js/.js
css/.css
themes/
fleetpress-light/
style.css
functions.php
front-page.php
single-vehicle.php
page-dashboard.php
archive-vehicle.php
assets/
css/
js/
img/
templates/
parts/.php
shortcodes/*.php
.github/
workflows/ci.yml
.editorconfig
.gitignore


---

## 5) Local development
- WP core not committed; keep only `wp-content`.
- `composer install` inside `plugins/fleetpress-rental-core/` to fetch Stripe + QR libs.
- `npm` in theme for Tailwind/build (optional in MVP).
- Configure Stripe keys via environment/DB options; never commit secrets.

---

## 6) Quality gates
- PHP lint + WPCS (coding standards).
- Sanitization/escaping/nonces for all mutating requests.
- Basic PHPUnit for service classes where feasible.
- E2E smoke test: book in Stripe test mode; webhook confirms; availability decrements.

---

## 7) Definition of Done (MVP)
- CPT `vehicle` with type taxonomy (`car`, `scooter`), inventory_count meta.
- Seasonal rates + live price calculation.
- Anti-overbooking via transactional lock at webhook.
- Stripe Checkout wired end-to-end.
- Customer dashboard (frontend) + admin backoffice basics.
- Emails shipped (EN) & strings wrapped for i18n (`fpr` text domain).
- README/AGENTS kept in sync at each milestone.
