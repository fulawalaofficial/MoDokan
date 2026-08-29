<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'low_stock_alert' => 'decimal:2',
        'settings' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(
            ShopCategory::class,
            'shop_category_id'
        );
    }

    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    public function users()
    {
        return $this->hasMany(
            User::class,
            'shop_id'
        );
    }

    public function subscriptions()
    {
        return $this->hasMany(
            ShopSubscription::class,
            'shop_id'
        );
    }
}
