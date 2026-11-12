# Phase 2 - Authentication and Login Page

## Files created
- app/Http/Controllers/AuthController.php
- app/Http/Middleware/RoleMiddleware.php (`role:admin` / `role:staff`)
- app/Http/Middleware/EnsureUserIsActive.php (`active`)
- app/Models/ActivityLog.php (login/logout are logged now)
- resources/views/layouts/auth.blade.php
- resources/views/auth/login.blade.php, forgot-password.blade.php, reset-password.blade.php
- resources/views/dashboard/placeholder.blade.php (temporary, replaced in Phase 3)
- public/css/auth.css, public/js/auth.js

## Files modified
- app/Models/User.php (role, is_active, helpers)
- bootstrap/app.php (middleware aliases, redirect for logged-in users)
- routes/web.php
- database/seeders/DatabaseSeeder.php (replaces the "Test User" with admin + staff)

## Test accounts (change after first login)
- username: admin / password: admin123
- username: staff / password: staff123
(Login now uses username + password. Email is still stored for password reset.)

## Commands
php artisan migrate        (if not done yet)
php artisan db:seed
php artisan serve          then open http://localhost:8000

Password reset emails are written to storage/logs/laravel.log because MAIL_MAILER=log.
