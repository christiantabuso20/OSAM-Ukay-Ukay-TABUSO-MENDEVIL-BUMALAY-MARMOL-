<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Low-stock notification data for the top navigation bell.
        View::composer('layouts.app', function ($view) {
            try {
                $count = Product::lowStock()->count();
                $items = Product::lowStock()->orderBy('available_stock')->limit(5)->get(['id', 'name', 'available_stock', 'minimum_stock']);
            } catch (\Throwable $e) {
                $count = 0;
                $items = collect();
            }

            $view->with('navLowStockCount', $count)->with('navLowStockItems', $items);
        });
    }
}
