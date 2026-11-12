# Phase 7 - Sales Recording

Created: SalesController, SaleRequest, views/sales/{index,create,show,receipt}.blade.php, tests/Feature/SalesTest.php.
Modified: Sale and SaleItem models (decimal casts, sale number generator), routes/web.php, sidebar (Sales Recording link), public/css/app.css (POS styles), tests/Feature/ExampleTest.php (home page now correctly expects a redirect to login).

Pages
- Sales History (/sales): summary cards (transactions, total sales, items sold, average sale), search (Sales ID, product name, SKU), date range, staff filter (admin), pagination, View and Receipt actions.
- New Sale (/sales/create): POS screen with live product search and category filter, available stock shown on each product, shopping cart with quantity stepper, total, payment input with quick buttons, automatic change, and a confirmation popup before saving.
- Sale details (/sales/{id}) and printable Receipt (/sales/{id}/receipt). The receipt opens automatically after a sale; "Print" uses the browser print dialog.

Rules
- Everything is saved in one DB transaction. Products are row-locked, so two sales cannot take the same stock.
- Prices always come from the database, never from the browser.
- Stock is checked on the server: insufficient stock, deleted products, zero/negative/decimal quantities and short payments are rejected and nothing is saved.
- The same product added twice is merged before the stock check.
- sale_items keeps the product name, SKU and unit price at the time of sale, so history stays correct after a product is edited or deleted.
- Sale numbers look like OSAM-20261109-0001 and count up each day.
- Admin sees all sales; staff see and print only the sales they recorded (403 otherwise).
- Activity log entry: module Sales, action sale.
- Sales do not create Stock Movement rows (Stock Out means removal for reasons other than a sale). Dashboard totals, Sold Items and Recent Sales update automatically.

Tests: php artisan test (15 tests, covering the rules above).
