# Phase 6 - Stock Monitoring

Created: StockMovementController, StockMovementRequest, views/stock/index.blade.php.
Modified: StockMovement model, routes/web.php, sidebar, app.css.

Page: summary cards (Total Stock In / Out / Damaged / Low-Stock), tabs for Movement History, Current Stock and Low-Stock Products,
search and filters (type, date range), and a Record Movement popup (also opened from the In / Out / Damaged buttons per product).

Rules
- Stock In adds; Stock Out and Damaged subtract. Damaged items leave available stock.
- Quantity must be at least 1; a movement that would make stock negative is rejected.
- Stock Out and Damaged require remarks.
- Admin: In, Out, Damaged. Staff: In and Damaged only (enforced in the request validation).
- Each movement stores previous and updated stock, user and time; the product row is locked and everything runs in one DB transaction.
- Total Stock In includes the opening stock entered when a product is created.
- Activity logs: stock_in, stock_out, damaged.
