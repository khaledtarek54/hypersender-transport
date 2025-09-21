<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Company;
use Carbon\Carbon;

class KpiService
{
    /**
     * Get overall trip statistics
     */
    public function getTripStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $totalTrips = Trip::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedTrips = Trip::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        $cancelledTrips = Trip::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'cancelled')
            ->count();

        return [
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'cancelled_trips' => $cancelledTrips,
            'completion_rate' => $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 2) : 0,
            'cancellation_rate' => $totalTrips > 0 ? round(($cancelledTrips / $totalTrips) * 100, 2) : 0,
        ];
    }

    /**
     * Get driver performance statistics
     */
    public function getDriverPerformance(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $driverStats = Driver::withCount([
            'trips as completed_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'trips as total_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->where('is_active', true)
        ->get()
        ->map(function ($driver) {
            $driver->completion_rate = $driver->total_trips_count > 0 
                ? round(($driver->completed_trips_count / $driver->total_trips_count) * 100, 2) 
                : 0;
            return $driver;
        });

        return $driverStats->toArray();
    }

    /**
     * Get vehicle utilization statistics
     */
    public function getVehicleUtilization(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $vehicleStats = Vehicle::withCount([
            'trips as active_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->whereIn('status', ['scheduled', 'in_progress', 'completed'])
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->where('is_active', true)
        ->get();

        $totalDays = $startDate->diffInDays($endDate) + 1;

        return $vehicleStats->map(function ($vehicle) use ($totalDays) {
            $vehicle->utilization_rate = $totalDays > 0 
                ? round(($vehicle->active_trips_count / $totalDays) * 100, 2) 
                : 0;
            return $vehicle;
        })->toArray();
    }

    /**
     * Get company performance comparison
     */
    public function getCompanyPerformance(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $companyStats = Company::withCount([
            'trips as total_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            },
            'trips as completed_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'drivers as active_drivers_count' => function ($query) {
                $query->where('is_active', true);
            },
            'vehicles as active_vehicles_count' => function ($query) {
                $query->where('is_active', true);
            }
        ])
        ->get()
        ->map(function ($company) {
            $company->completion_rate = $company->total_trips_count > 0 
                ? round(($company->completed_trips_count / $company->total_trips_count) * 100, 2) 
                : 0;
            return $company;
        });

        return $companyStats->toArray();
    }

    /**
     * Get monthly trip trends
     */
    public function getMonthlyTripTrends(int $months = 12): array
    {
        $trends = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();
            
            $totalTrips = Trip::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $completedTrips = Trip::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'completed')
                ->count();
            
            $trends[] = [
                'month' => $date->format('M Y'),
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'completion_rate' => $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 2) : 0,
            ];
        }

        return $trends;
    }

    /**
     * Get top performing drivers
     */
    public function getTopPerformingDrivers(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        return Driver::withCount([
            'trips as completed_trips_count' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])
        ->where('is_active', true)
        ->orderBy('completed_trips_count', 'desc')
        ->with('company')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    /**
     * Get distance statistics
     */
    public function getDistanceStatistics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $trips = Trip::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('distance')
            ->get();

        return [
            'total_distance' => $trips->sum('distance'),
            'average_distance' => $trips->avg('distance') ? round($trips->avg('distance'), 2) : 0,
            'longest_trip' => $trips->max('distance') ?? 0,
            'shortest_trip' => $trips->min('distance') ?? 0,
            'total_trips_with_distance' => $trips->count(),
        ];
    }
}
