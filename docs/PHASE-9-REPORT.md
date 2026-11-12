# Phase 9 - User Management and Activity Logs

Created: UserController, ActivityLogController, UserRequest, views/users/{index,show}.blade.php,
views/activity-logs/index.blade.php, tests/Feature/UserManagementTest.php.
Modified: routes/web.php, sidebar (User Management and Activity Logs links now active), ActivityLog model (logs cannot be updated or deleted).

User Management (/users, admin only)
- Summary cards (total, active, deactivated, administrators), search (name, username, email), role and status filters, pagination.
- Add User popup: full name, username, email, role (Admin or Staff), password + confirmation. There is no public registration.
- Edit User popup: name, username, email, role. Passwords are never changed here.
- Reset Password popup: admin sets a new password; the person is signed out of open sessions.
- Activate / Deactivate with a confirmation dialog. A deactivated person is signed out immediately and cannot log in.
- User details page: account info, sales recorded, stock movements recorded, last 10 activities.

Rules
- Every route and action is admin only (staff get 403; also enforced in UserRequest::authorize).
- Passwords are hashed; username and email must be unique; password at least 8 characters and confirmed.
- An admin cannot deactivate their own account or change their own role (prevents locking everyone out).
- Users are never deleted, so sales and stock history keep their user names.

Activity Logs (/activity-logs, admin only, read-only)
- Newest first; search (description or user name); filters for user, module, action and date range; 20 per page.
- The page has no edit or delete actions, no routes exist for them, and the model refuses update/delete.
- New log entries: module Users, actions create, update, activate, deactivate, reset_password. Login, logout, products, categories, stock, sales and reports were already logged in earlier phases.

Tests: php artisan test (UserManagementTest adds 17 tests).
