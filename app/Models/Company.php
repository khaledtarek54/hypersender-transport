<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the drivers for the company.
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get the vehicles for the company.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get the trips for the company.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    protected static function booted(): void
    {
        static::created(function () {
            Cache::forget('kpi:total_companies');
        });

        static::deleted(function () {
            Cache::forget('kpi:total_companies');
        });
    }
}
