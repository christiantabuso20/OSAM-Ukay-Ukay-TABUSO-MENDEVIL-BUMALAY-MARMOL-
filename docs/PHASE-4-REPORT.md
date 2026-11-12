# Phase 4 - Clothing Inventory

Created: ProductController, ProductRequest, CategorySeeder, views/inventory/* (index, create, edit, show + partials).
Modified: Product model, AppServiceProvider (Bootstrap pagination), DatabaseSeeder, routes/web.php, sidebar, app.css, app.js.

Rules
- Admin: add, edit, delete. Staff: view list and details only (enforced by routes, not just hidden buttons).
- SKU auto-generated (OSAM-00001...). Image: JPG/PNG/WEBP, max 2 MB, stored in storage/app/public/products.
- Initial stock is only set on creation and is logged as a Stock In ("Initial stock"). Later stock changes go through Stock Monitoring (Phase 6).
- Delete is a soft delete, so sales and stock history stay intact.
- Activity logs: product create / update / delete.

Commands
php artisan db:seed            (adds the 10 default categories; safe to re-run)
php artisan storage:link       (required once so product images display)
