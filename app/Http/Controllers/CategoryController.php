<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $categories = Category::withCount('products')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function show(Category $category): View
    {
        $products = $category->products()->orderBy('name')->paginate(10);

        return view('categories.show', compact('category', 'products'));
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = DB::transaction(function () use ($request) {
            $category = Category::create($request->validated());
            ActivityLog::record('Categories', 'create', "Added category {$category->name}.");

            return $category;
        });

        return redirect()->route('categories.index')->with('success', "Category {$category->name} was added.");
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        DB::transaction(function () use ($request, $category) {
            $category->update($request->validated());
            ActivityLog::record('Categories', 'update', "Updated category {$category->name}.");
        });

        return redirect()->route('categories.index')->with('success', "Category {$category->name} was updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        // Count archived (soft-deleted) products too, because they still reference the category.
        $assigned = $category->products()->withTrashed()->count();

        if ($assigned > 0) {
            return back()->with('error', "\"{$category->name}\" is still assigned to {$assigned} "
                .\Illuminate\Support\Str::plural('product', $assigned).'. Reassign those products to another category first.');
        }

        DB::transaction(function () use ($category) {
            $category->delete();
            ActivityLog::record('Categories', 'delete', "Deleted category {$category->name}.");
        });

        return redirect()->route('categories.index')->with('success', "Category {$category->name} was deleted.");
    }
}
