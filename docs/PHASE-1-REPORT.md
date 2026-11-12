# OSAM Ukay-Ukay - Phase 1 Report (Project Inspection and Database Design)

## 1. What already exists
- Fresh Laravel skeleton, framework v13.40.0 (composer.json requires ^13.17), Tinker, PHPUnit 12, Pint.
- Installed vendor packages need PHP >= 8.4.1 (see vendor/composer/platform_check.php). Your XAMPP PHP must be 8.4.1 or newer.
- routes/web.php: only `/` returning the default welcome view.
- app/Models/User.php: default (name, email, password). No controllers, middleware, policies or views yet.
- Migrations: users, password_reset_tokens, sessions (one file), cache, jobs.
- Session, cache and queue drivers are all `database`, so MySQL must be running before anything works.
- Frontend: Vite 8 + Tailwind 4 are in package.json. No Bootstrap, Chart.js, PDF or Excel packages yet.
- Auth config: default `web` guard, `users` provider, App\Models\User.
- .env was set to SQLite with the DB lines commented out.

## 2. Changes made in this phase
Modified:
- .env and .env.example: DB_CONNECTION=mysql, host 127.0.0.1, port 3306, database `osam_ukay_ukay`, user `root`, empty password (XAMPP defaults). .env also got APP_NAME="OSAM Ukay-Ukay".

Created (database/migrations):
- 2026_11_09_000001_add_role_and_status_to_users_table.php  (role, is_active)
- 2026_11_09_000002_create_categories_table.php
- 2026_11_09_000003_create_products_table.php
- 2026_11_09_000004_create_stock_movements_table.php
- 2026_11_09_000005_create_sales_table.php  (also creates sale_items)
- 2026_11_09_000006_create_activity_logs_table.php

Existing files were not deleted or replaced.

## 3. Database design
users (+role, is_active) 1-N sales, stock_movements, activity_logs
categories 1-N products (restrictOnDelete: cannot delete a used category)
products 1-N stock_movements, products 1-N sale_items (soft deletes keep history)
sales 1-N sale_items

Key decisions
- products.available_stock excludes damaged items; damaged units are recorded as stock_movements of type `damaged`.
- sale_items stores product_name, sku and unit_price snapshots; sales stores staff_name snapshot.
- activity_logs stores user_name snapshot; user FKs use nullOnDelete so records survive.
- stock_movements stores previous_stock and updated_stock for every change.
- Stock and sale writes will use DB transactions with row locks (Phase 6 and 7).

## 4. Files planned for later phases
- Phase 2: AuthController, RoleMiddleware (alias `role`), User model update, DatabaseSeeder (admin + staff), layouts/auth views.
- Phase 3: layouts/app, sidebar/navbar components, DashboardController.
- Phases 4-9: models (Category, Product, StockMovement, Sale, SaleItem, ActivityLog), controllers, Form Requests, views per module.
- Phase 8 packages: barryvdh/laravel-dompdf (PDF), maatwebsite/excel (Excel).

## 5. Things to run on your machine
1. Start Apache and MySQL in XAMPP; check `php -v` is 8.4.1+.
2. Create an empty database `osam_ukay_ukay` in phpMyAdmin (collation utf8mb4_unicode_ci).
3. In the project folder: `php artisan config:clear` then `php artisan migrate`.
4. Confirm the tables in phpMyAdmin.

## 6. Decisions needed before Phase 2
- CSS framework: Bootstrap 5 (via CDN, no Node needed) or Tailwind 4 (already installed, needs `npm run dev`).
- Icons: Font Awesome or Lucide.
- Staff permissions for stock functions (suggested: staff can Stock In and record Damaged, only admins can Stock Out).
