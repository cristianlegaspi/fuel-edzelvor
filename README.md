# Fuel Trading System — Filament → Custom Blade Conversion

This converts your **edzelborapp** project's admin panel (currently built on
Filament) into hand-rolled Tailwind Blade CRUD screens, in the same style as
the ordering system we built earlier — same layout pattern, same components,
same "business logic lives in the model, controllers stay thin" approach.

## Important: your model logic is untouched and still does all the work

Your `app/Models/*.php` files already do the heavy lifting — recalculating
totals, generating tracking numbers, validating stock, auto-creating/deleting
tanker records — inside `booted()` event hooks (`saving`, `saved`, `deleted`,
`restored`). **None of that changes.** Every controller below just calls
`create()`, `update()`, or `delete()` on your existing models; your model
events fire exactly as they did under Filament and do all the math. That's
why the controllers are short — they're just moving form data into your
models, not reimplementing your business rules.

---

## What's in this package

```
app/Http/Controllers/
  AuthController.php                  — simple login/logout (same users table Filament uses)
  FuelDashboardController.php         — stat overview
  FuelSalesOrderController.php        — supplier orders + fuel product line items
  FuelPaymentController.php           — payments TO supplier (nested under a sales order)
  FuelCustomerPurchaseController.php  — customer purchases + line items + expenses
  FuelCustomerPaymentController.php   — payments FROM customer (nested under a purchase)
  FuelTankerRecordController.php      — edit the auto-created tanker delivery record

resources/views/
  layouts/fuel.blade.php              — sidebar shell (Tailwind + Alpine)
  components/icon.blade.php, badge.blade.php, stat-card.blade.php
  auth/login.blade.php
  fuel/dashboard.blade.php
  fuel/sales-orders/{index,create,edit,_form}.blade.php
  fuel/customer-purchases/{index,create,edit,_form}.blade.php

routes/web-additions.php              — routes to merge into your existing routes/web.php
```

---

## Step 1 — Copy files in

Copy `app/Http/Controllers/*` and `resources/views/*` from this package into
the matching folders in your actual project (the one you uploaded), keeping
your existing `app/Filament`, `app/Models`, and `database/migrations`
untouched — nothing there needs to change.

## Step 2 — Merge the routes

Open your real `routes/web.php` (it already has your SOA/print/report
routes — keep those). Add the `use` statements and route blocks from
`routes/web-additions.php` into it. The one line to change is your existing:

```php
Route::get('/', function () {
    return redirect('/admin/login');
});
```

Replace it with:

```php
Route::get('/', fn () => redirect()->route('fuel-dashboard'));
```

(Or leave both routes — Filament's `/admin` panel and the new Blade UI can
run side by side on the same database, since they share the same `users`
table, the same `web` auth guard, and the same Eloquent models.)

## Step 3 — Tailwind + Vite

Same setup as the ordering system: install `@tailwindcss/vite`, wire it into
`vite.config.js` and `resources/css/app.css`, then:

```bash
npm install
npm run dev
```

## Step 4 — Run it

```bash
php artisan serve
```

Log in at `/login` with any existing user from your `users` table, then visit
`/fuel-dashboard`.

---

## How each screen maps to your Filament resources

| Filament Resource | Blade equivalent | Notes |
|---|---|---|
| `FuelSalesOrderResource` | `fuel/sales-orders/*` | Repeater → an Alpine.js item list (add/remove rows client-side, submitted as `items[i][field]`) |
| `FuelPaymentResource` | Payment table + form inside `sales-orders/edit.blade.php` | Filament had this as a separate resource; here it's inline under the order it belongs to, like a relation manager |
| `FuelCustomerPurchaseResource` | `fuel/customer-purchases/*` | Same Alpine repeater pattern for fuel product lines, plus the expenses section |
| `FuelCustomerPaymentResource` | Payment table + form inside `customer-purchases/edit.blade.php` | Same inline pattern as above |
| `FuelTankerRecordResource` | Tanker section inside `customer-purchases/edit.blade.php` | The record is auto-created/deleted by your `FuelCustomerPurchase` model when `tanker_details` is filled in or cleared — this form only edits it, never creates/deletes it directly, matching your model's ownership of that lifecycle |

### The one thing Blade doesn't replicate: live client-side recalculation

Filament's repeater had `->live()` fields that recalculated placeholders
(line totals, stock warnings) instantly as you typed, before saving. The
Blade version shows a **client-side estimate** for the sales-order line
total via Alpine (`x-text`), but the authoritative numbers — total_liters,
net_income, balance, status, stock remaining — only appear **after you save**,
same as any plain server-rendered form. If you want live recalculation without
a full page reload, that would mean layering in either Livewire or a fetch-based
JS calculator; happy to build that next if you want it.

### Stock validation

Your `FuelCustomerPurchaseItem::validateAvailableStock()` throws a
`ValidationException` if someone tries to sell more liters than remain on the
selected sales order. `FuelCustomerPurchaseController` catches that exception
and turns it into normal form errors (shown at the top of the page via the
layout's `$errors->any()` block) instead of crashing with a 500.
