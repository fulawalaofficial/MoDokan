<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'shop_id',
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'status',
        'permissions',
        'profile_photo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'permissions' => 'array',
        'password' => 'hashed',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended API Attributes
    |--------------------------------------------------------------------------
    |
    | Every JSON version of the user will automatically contain:
    |
    | "profile_photo_url": "https://domain.com/api/profile-images/..."
    |
    */

    protected $appends = [
        'profile_photo_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        $filename = basename($this->profile_photo);

        return route('profile.photo.public', [
            'filename' => $filename,
        ]);
    }
}
