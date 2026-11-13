OSAM Ukay-Ukay

OSAM Ukay-Ukay is a role-based inventory and point-of-sale portal for a secondhand clothing shop. It helps administrators and staff manage apparel, categories, stock movements, sales, reports, users, and activity history from one web application.

Author: Christian Jay Tabuso
Members: Mendevil, Bumalay, and Marmol

Features

•
Role-based access control with Admin and Staff roles

•
Authentication with username/password login, account activation status, logout, and password reset flow

•
Dashboard with operational summaries and sales chart data

•
Product inventory management with SKU generation, apparel details, pricing, stock status, and product images

•
Category management for organizing inventory

•
Stock monitoring with stock-in, stock-out, damaged-item tracking, and movement history

•
Sales recording with cart-style product selection, sale details, generated sale numbers, and printable receipts

•
Reports for inventory, low stock, damaged items, stock movement, daily/weekly/monthly sales, custom date ranges, and best-selling products

•
Export options for Excel, PDF, and print-friendly reports

•
User management for administrators, including search, role changes, activation/deactivation, and password resets

•
Activity logs for important authentication, inventory, sales, user, and reporting actions

•
Responsive web interface built with Blade templates, Tailwind CSS, and Vite

•
Automated feature tests covering inventory, sales, reports, and user management

Technology Stack

•
PHP 8.3+

•
Laravel 13

•
MySQL or another Laravel-supported database

•
Blade templates

•
Tailwind CSS 4

•
Vite 8

•
PHPUnit 12

•
Optional: dompdf/dompdf for direct PDF downloads

Requirements

Make sure the following are installed before setup:

•
PHP 8.3 or newer

•
Composer

•
Node.js and npm

•
MySQL/MariaDB, or another database supported by your Laravel configuration

•
PHP extensions required by Laravel, including PDO and the database driver for your selected database

•
PHP Zip extension if you want to generate real .xlsx exports

Installation

1.
Clone the repository and enter the project directory:

Bash


git clone <your-repository-url>
cd osam





2.
Install PHP dependencies:

Bash


composer install





3.
Create the environment file and generate an application key:

Bash


cp .env.example .env
php artisan key:generate





4.
Create a database named osam_ukay_ukay or update the database values in .env to match your local setup. The default template is configured for a local MySQL server:

Plain Text


DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osam_ukay_ukay
DB_USERNAME=root
DB_PASSWORD=





5.
Run the migrations and seed the demo data:

Bash


php artisan migrate --seed





6.
Install frontend dependencies and build the assets:

Bash


npm install
npm run build





7.
Start the Laravel development server:

Bash


php artisan serve





8.
Open 
http://localhost:8000 in your browser.

One-command setup

After cloning the project, the Composer setup script can perform most of the initial setup automatically:

Bash


composer run setup



Review your .env database settings before running migrations if your local database differs from the defaults.

Demo Accounts

The database seeder creates the following accounts for local development:

Role
Username
Password
Administrator
admin
admin123
Staff
staff
staff123




Change these passwords immediately in any shared, staging, or production environment. These credentials are intended for local development only.

Access Permissions

Administrator

Administrators have full access to the portal, including:

•
Dashboard

•
Products and categories

•
Stock monitoring and all stock reports

•
Sales and sales reports

•
Damaged-item and stock-movement reports

•
User management

•
Activity logs

•
PDF, Excel, and print report exports

Staff

Staff members can access:

•
Staff dashboard

•
Product browsing

•
Stock monitoring

•
Sales recording and receipts

•
Complete inventory, available stock, low-stock, and sales reports

Staff sales reports are scoped to sales recorded by the signed-in staff member. Administrative-only reports are protected server-side and are not merely hidden from the navigation.

Useful Commands

Bash


# Start the application
php artisan serve

# Run the Vite development server
npm run dev

# Build production frontend assets
npm run build

# Run the test suite
php artisan test

# Clear cached configuration and application data
php artisan optimize:clear

# Run migrations and reseed local demo data
php artisan migrate:fresh --seed



If product images are stored on the public filesystem, create the storage link once:

Bash


php artisan storage:link



Reports and Exports

The Reports page supports the following report groups:

•
Complete inventory

•
Available stock

•
Low stock, with category filtering

•
Damaged items

•
Stock movement, with date and movement-type filters

•
Daily, weekly, and monthly sales

•
Custom date-range sales

•
Best-selling products

Reports can be previewed in the browser and exported as Excel, PDF, or a print-friendly layout. Excel export uses the project’s built-in XLSX writer and requires the PHP Zip extension. Direct PDF downloads require the optional Dompdf package:

Bash


composer require dompdf/dompdf



Without Dompdf, the PDF action falls back to the print view so the browser’s Save as PDF feature can be used.

Testing

Run the complete test suite with:

Bash


php artisan test



Feature tests cover sales recording, inventory behavior, report permissions and calculations, and user management. For a clean local test database, configure a separate testing database in phpunit.xml or your test environment before running destructive test commands.

Project Structure

Plain Text


app/
├── Http/Controllers/       Request handling and application workflows
├── Http/Middleware/        Authentication, active-account, and role checks
├── Http/Requests/           Form validation classes
├── Models/                  Eloquent models
├── Services/                Report-building logic
└── Support/                 XLSX and PDF helpers

database/
├── migrations/              Database schema
├── seeders/                 Demo categories, products, and users
└── factories/               Test data factories

resources/views/              Blade pages and reusable UI components
public/                       Compiled assets and public entry point
routes/web.php                Web routes and access restrictions
tests/                        Unit and feature tests
docs/                         Phase reports and implementation notes



Security Notes

•
Do not commit .env files, production credentials, uploaded private files, or generated secrets.

•
Replace the seeded demo passwords before deploying the application.

•
Set APP_DEBUG=false in production.

•
Configure a real mail provider before using password-reset emails outside local development.

•
Use HTTPS and secure database credentials in production.

•
Review the project’s authorization rules before adding new routes or controllers.

Development Notes

The project includes phase documentation in the docs/ directory covering authentication, dashboards, inventory, categories, stock monitoring, sales, reports, and user management. These documents are useful references when extending the application.

License

This project is released under the MIT License. See LICENSE if a license file is included in the repository.

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
