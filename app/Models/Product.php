<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;

    public const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Free Size'];
    public const CONDITIONS = ['Excellent', 'Good', 'Fair'];
    public const CLOTHING_TYPES = ['Casual Wear', 'Formal Wear', 'Sportswear', 'Streetwear', 'Sleepwear', 'Others'];

    protected $fillable = [
        'sku', 'name', 'category_id', 'clothing_type', 'size', 'condition', 'description',
        'unit_price', 'initial_stock', 'available_stock', 'minimum_stock', 'image_path',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Products at or below their minimum stock level (includes out of stock). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('available_stock', '<=', 'minimum_stock');
    }

    /** in_stock | low_stock | out_of_stock */
    public function getStockStatusAttribute(): string
    {
        if ($this->available_stock <= 0) {
            return 'out_of_stock';
        }

        return $this->available_stock <= $this->minimum_stock ? 'low_stock' : 'in_stock';
    }

    public function getStockStatusLabelAttribute(): string
    {
        return ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'][$this->stock_status];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** Next unique SKU, e.g. OSAM-00042 (soft-deleted products are counted too). */
    public static function generateSku(): string
    {
        $next = ((int) static::withTrashed()->max('id')) + 1;

        do {
            $sku = 'OSAM-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            $next++;
        } while (static::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}
