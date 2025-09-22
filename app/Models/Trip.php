<?php

namespace App\Models;

use App\Models\Enums\TripStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'driver_id',
        'vehicle_id',
        'origin',
        'destination',
        'start_time',
        'end_time',
        'status',
        'distance',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'distance' => 'decimal:2',
        'status' => TripStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    /**
     * Get the company that owns the trip.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the driver for the trip.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle for the trip.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Scope a query to only include trips with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active trips.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value]);
    }
}
