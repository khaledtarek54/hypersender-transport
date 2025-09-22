<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Enums\TripStatus;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    /**
     * Get available drivers for a specific time period
     */
    public function getAvailableDrivers(?Carbon $startTime = null, ?Carbon $endTime = null): Collection
    {
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? $startTime->copy()->addDay();

        // Get drivers who are active
        $activeDrivers = Driver::where('is_active', true)->get();

        // Get drivers who have trips during the specified time period
        $busyDriverIds = Trip::whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->pluck('driver_id')
            ->unique();

        // Return available drivers
        return $activeDrivers->whereNotIn('id', $busyDriverIds);
    }

    /**
     * Get available vehicles for a specific time period
     */
    public function getAvailableVehicles(?Carbon $startTime = null, ?Carbon $endTime = null): Collection
    {
        $startTime = $startTime ?? now();
        $endTime = $endTime ?? $startTime->copy()->addDay();

        // Get vehicles that are active
        $activeVehicles = Vehicle::where('is_active', true)->get();

        // Get vehicles that have trips during the specified time period
        $busyVehicleIds = Trip::whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->pluck('vehicle_id')
            ->unique();

        // Return available vehicles
        return $activeVehicles->whereNotIn('id', $busyVehicleIds);
    }

    /**
     * Check if a driver is available for a specific time period
     */
    public function isDriverAvailable(int $driverId, Carbon $startTime, Carbon $endTime): bool
    {
        $conflictingTrips = Trip::where('driver_id', $driverId)
            ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return !$conflictingTrips;
    }

    /**
     * Check if a vehicle is available for a specific time period
     */
    public function isVehicleAvailable(int $vehicleId, Carbon $startTime, Carbon $endTime): bool
    {
        $conflictingTrips = Trip::where('vehicle_id', $vehicleId)
            ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return !$conflictingTrips;
    }

    /**
     * Get upcoming trips for a driver
     */
    public function getUpcomingTripsForDriver(int $driverId, int $limit = 5): Collection
    {
        return Trip::where('driver_id', $driverId)
            ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where('start_time', '>=', now())
            ->with(['vehicle', 'company'])
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }

    /**
     * Get upcoming trips for a vehicle
     */
    public function getUpcomingTripsForVehicle(int $vehicleId, int $limit = 5): Collection
    {
        return Trip::where('vehicle_id', $vehicleId)
            ->whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where('start_time', '>=', now())
            ->with(['driver', 'company'])
            ->orderBy('start_time')
            ->limit($limit)
            ->get();
    }
}
