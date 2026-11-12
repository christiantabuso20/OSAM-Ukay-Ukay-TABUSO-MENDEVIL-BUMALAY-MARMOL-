# Phase 5 - Category Management

Created: CategoryController, CategoryRequest, views/categories (index with add/edit modal, show, delete modal).
Modified: routes/web.php (categories resource, admin only), sidebar (link enabled).

Rules
- Admin only (routes protected; staff get 403 and do not see the menu item).
- Add, edit, delete, view, search. Unique category names.
- A category cannot be deleted while any product (including archived/deleted ones) still uses it; the admin must reassign those products first (Clothing Inventory > Edit).
- The category page lists its products and links to the inventory filtered by that category.
- Size, Condition and Clothing Type remain fixed lists (see Product model); categories are the dynamic classification.
- Activity logs: category create / update / delete.
