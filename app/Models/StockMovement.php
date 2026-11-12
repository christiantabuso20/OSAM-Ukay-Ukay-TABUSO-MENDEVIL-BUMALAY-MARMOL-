<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_IN = 'stock_in';
    public const TYPE_OUT = 'stock_out';
    public const TYPE_DAMAGED = 'damaged';

    public const TYPES = [
        self::TYPE_IN => 'Stock In',
        self::TYPE_OUT => 'Stock Out',
        self::TYPE_DAMAGED => 'Damaged',
    ];

    protected $fillable = ['product_id', 'user_id', 'type', 'quantity', 'previous_stock', 'updated_stock', 'remarks'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    /** Display ID, e.g. SM-000123 */
    public function getCodeAttribute(): string
    {
        return 'SM-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Movement types the given user may record. Staff cannot do Stock Out. */
    public static function allowedTypesFor(User $user): array
    {
        return $user->isAdmin()
            ? [self::TYPE_IN, self::TYPE_OUT, self::TYPE_DAMAGED]
            : [self::TYPE_IN, self::TYPE_DAMAGED];
    }
}
