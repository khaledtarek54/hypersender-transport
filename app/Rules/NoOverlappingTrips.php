<?php

namespace App\Rules;

use App\Models\Trip;
use App\Models\Enums\TripStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlappingTrips implements ValidationRule
{
    protected $driverId;
    protected $vehicleId;
    protected $startTime;
    protected $endTime;
    protected $excludeTripId;

    public function __construct($driverId, $vehicleId, $startTime, $endTime, $excludeTripId = null)
    {
        $this->driverId = $driverId;
        $this->vehicleId = $vehicleId;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->excludeTripId = $excludeTripId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check for overlapping trips with the same driver
        $driverOverlap = Trip::where('driver_id', $this->driverId)
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::InProgress])
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->startTime, $this->endTime])
                    ->orWhereBetween('end_time', [$this->startTime, $this->endTime])
                    ->orWhere(function ($q) {
                        $q->where('start_time', '<=', $this->startTime)
                          ->where('end_time', '>=', $this->endTime);
                    });
            });

        // Exclude current trip if updating
        if ($this->excludeTripId) {
            $driverOverlap->where('id', '!=', $this->excludeTripId);
        }

        if ($driverOverlap->exists()) {
            $fail('The driver is already assigned to another trip during this time period.');
        }

        // Check for overlapping trips with the same vehicle
        $vehicleOverlap = Trip::where('vehicle_id', $this->vehicleId)
            ->whereIn('status', [TripStatus::Scheduled, TripStatus::InProgress])
            ->where(function ($query) {
                $query->whereBetween('start_time', [$this->startTime, $this->endTime])
                    ->orWhereBetween('end_time', [$this->startTime, $this->endTime])
                    ->orWhere(function ($q) {
                        $q->where('start_time', '<=', $this->startTime)
                          ->where('end_time', '>=', $this->endTime);
                    });
            });

        // Exclude current trip if updating
        if ($this->excludeTripId) {
            $vehicleOverlap->where('id', '!=', $this->excludeTripId);
        }

        if ($vehicleOverlap->exists()) {
            $fail('The vehicle is already assigned to another trip during this time period.');
        }
    }
}
