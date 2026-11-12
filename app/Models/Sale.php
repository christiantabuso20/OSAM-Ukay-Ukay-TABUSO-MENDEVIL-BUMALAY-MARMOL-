<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Sale extends Model
{
    protected $fillable = ['sale_number', 'user_id', 'staff_name', 'total_amount', 'payment_amount', 'change_amount'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Next sale number for today, e.g. OSAM-20261109-0001. */
    public static function generateNumber(): string
    {
        $prefix = 'OSAM-'.now()->format('Ymd').'-';

        $last = static::where('sale_number', 'like', $prefix.'%')->orderByDesc('sale_number')->value('sale_number');
        $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
