# Multi-Agent Plan – FleetPress Rental (WP Theme + Plugin)

**Mission:** Generate a minimal but production-grade base for a car & scooter rental on WordPress. Theme = UI. Plugin = core logic (inventory, rates, availability, bookings, Stripe, emails, roles, cron, admin).

## Roles
1) **Architect** – confirms requirements, outputs file tree, scaffolds plugin/theme entries, sets WPCS/CI and env.
2) **Plugin Engineer** – DB schema + activation, CPT `vehicle`, REST API, availability calendar, pricing, cron, roles, emails, admin pages.
3) **Payments Engineer** – Stripe Checkout + secure webhooks; (optional) refunds.
4) **Theme/Frontend Engineer** – pages/templates (home, vehicle detail, dashboard), booking form UX, availability polling, price breakdown.
5) **QA Engineer** – lint (PHP + WPCS), basic PHPUnit, smoke E2E checklist; CI workflow.
6) **Docs Engineer** – keeps README/AGENTS updated; admin & user quick-guides.

## Non-negotiable constraints
- No external WP plugins; Composer libs allowed (Stripe, QR).
- Booking statuses: `pending | confirmed | in_progress | completed | cancelled`.
- Availability = capacity per day − confirmed reservations − blocks.
- Anti-overbooking via **DB transaction + SELECT … FOR UPDATE** over `fpr_calendar` rows on webhook success.
- Role `fpr_customer`; no /wp-admin; redirect to `/dashboard`; hide admin bar.
- i18n: default English; all strings wrapped with text domain `fpr`.

## Milestones & Deliverables
**M1 – Scaffolding**
- Create file tree; add `fleetpress-rental-core.php` (plugin header), theme `style.css`, `front-page.php`, `single-vehicle.php`, `page-dashboard.php`.
- Composer in plugin: `stripe/stripe-php` and `endroid/qr-code`. PSR-4 autoload (`Fpr\Rental\*`).
- Activator creates tables: `fpr_seasons`, `fpr_rates`, `fpr_blocks`, `fpr_calendar`, `fpr_bookings`, `fpr_payments`. Adds role `fpr_customer`.

**M2 – CPT + REST + Pricing/Availability**
- Register CPT `vehicle` (+ taxonomy `vehicle_type` with terms `car`, `scooter`); meta: inventory_count, specs.
- REST:
  - `GET /vehicles`, `GET /availability`, `POST /booking`.
- Pricing engine: seasonal daily rate; total = `days × daily_rate × qty`.
- Customer creation on booking (if not existing); admin bar hidden; redirect to `/dashboard`.

**M3 – Stripe**
- Create Checkout Session server-side (amount from pricing engine).
- Webhook: `payment_intent.succeeded` → payment `paid`, booking `confirmed`, and increment `fpr_calendar.reserved_count` per day (transaction).
- Handle `checkout.session.expired`/fail → cancel pending.

**M4 – UI + Emails**
- Theme: homepage search/list, vehicle detail with live availability & price, booking form → Stripe.
- Dashboard (frontend): bookings list, status, QR, cancellation (>72h).
- Email templates (EN) for customer/admin events.

**M5 – Admin + QA**
- Admin: Calendar (per date), Bookings list with filters/actions, Seasons & Rates manager, Blocks manager.
- CI workflow: PHP lint + WPCS; basic PHPUnit for pricing/availability.
- Update docs (README & AGENTS) with any deviations.

## Acceptance Test (QA)
- Create a booking for a vehicle (future dates, qty ≥ 1), pay via Stripe test mode; webhook confirms; calendar reserved_count rises; customer email sent; admin sees booking.
