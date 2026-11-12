# Phase 3 - Layout and Dashboards

Created: layouts/app.blade.php, partials/sidebar.blade.php, partials/navbar.blade.php, components/stat-card.blade.php,
dashboard/index.blade.php, DashboardController.php, models (Category, Product, StockMovement, Sale, SaleItem),
public/css/app.css, public/js/app.js.
Modified: routes/web.php, app/Providers/AppServiceProvider.php.
Removed: resources/views/dashboard/placeholder.blade.php (temporary page from Phase 2).

Notes
- Sidebar links for modules that are not built yet are shown greyed out with a "Soon" tag. Each phase turns its link on by
  setting the route name in partials/sidebar.blade.php.
- Dashboard numbers read live from MySQL; with no products or sales yet they correctly show 0 and empty-state messages.
- Staff dashboard hides the Recent Activities panel; Admin sees everything.
