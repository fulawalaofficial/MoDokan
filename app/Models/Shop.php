<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = [
        'shop_category_id',
        'owner_id',
        'name',
        'address',
        'contact_number',
        'gst_number',
        'logo',
        'opening_balance',
        'currency',
        'invoice_prefix',
        'default_tax',
        'low_stock_alert',
        'status',
        'settings',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'default_tax' => 'decimal:2',
        'low_stock_alert' => 'integer',
        'settings' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
