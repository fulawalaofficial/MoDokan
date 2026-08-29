<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'supports_repair',
        'status',
    ];

    protected $casts = [
        'supports_repair' => 'boolean',
    ];

    public function shops(): HasMany
    {
        return $this->hasMany(Shop::class);
    }
}
