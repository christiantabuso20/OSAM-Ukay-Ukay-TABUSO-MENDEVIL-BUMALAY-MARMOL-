# Phase 8 - Reports Generation

Created: ReportController, app/Services/ReportBuilder.php, app/Support/SimpleXlsx.php,
views/reports/{index,print,pdf,_document}.blade.php, tests/Feature/ReportsTest.php.
Modified: routes/web.php (reports routes), sidebar (Reports Generation link now active).

Pages
- Reports (/reports): pick a report, set its coverage, see the summary cards + detailed table (preview shows the first 200 rows).
- Export PDF, Export Excel and Print buttons use the same filters as the preview.

Reports
- Inventory: Complete Inventory, Available Stock, Low-Stock (all can be filtered by category), Damaged Items, Stock Movement (date range; movement type for the movement report).
- Sales: Daily, Weekly, Monthly (pick any date - the day / Monday-Sunday week / month containing it), Custom Date Range, Best-Selling Products (top 20).
- Every report has: OSAM Ukay-Ukay name, report title, coverage, generation date and user, summary figures, detailed table and totals row.

Permissions
- Admin: every report.
- Staff: Complete Inventory, Available Stock, Low-Stock and all Sales reports. Staff sales reports contain only the sales they recorded.
- Damaged Items and Stock Movement reports are admin only (403 for staff, enforced in the controller, not just hidden in the menu).

Exports
- Excel: a real .xlsx file written by app/Support/SimpleXlsx.php (needs only the PHP zip extension, on by default in XAMPP).
- PDF: downloads a real PDF when the optional package is installed:  composer require dompdf/dompdf
  Without it, "Export PDF" opens the print view and the browser's "Save as PDF" is used.
- Print: opens a clean print layout (A4, landscape automatically for wide tables) and starts the print dialog.
- Exports and prints are recorded in Activity Logs (module Reports; actions export_pdf, export_excel, print).

Data rules
- Everything comes from MySQL; sales figures use sale_items/sales snapshots, so history stays correct after products change.
- Damaged items are excluded from available stock (already enforced by Stock Monitoring); the Damaged report lists them separately.

Tests: php artisan test (ReportsTest adds 15 tests: permissions, staff sales scoping, date ranges, totals, print, PDF and Excel export).
