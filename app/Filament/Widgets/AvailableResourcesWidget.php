<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Company;
use App\Models\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;

class AvailableResourcesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDrivers = Driver::where('is_active', true)->count();
        $totalVehicles = Vehicle::where('is_active', true)->count();
        
        // Get drivers currently on trips
        $driversOnTrips = Trip::whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->distinct('driver_id')
            ->count('driver_id');
            
        // Get vehicles currently on trips
        $vehiclesOnTrips = Trip::whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->distinct('vehicle_id')
            ->count('vehicle_id');

        $availableDrivers = $totalDrivers - $driversOnTrips;
        $availableVehicles = $totalVehicles - $vehiclesOnTrips;

        return [
            Stat::make('Available Drivers', $availableDrivers)
                ->description("{$totalDrivers} total drivers")
                ->descriptionIcon('heroicon-m-user')
                ->color($availableDrivers > 0 ? 'success' : 'danger'),
            
            Stat::make('Available Vehicles', $availableVehicles)
                ->description("{$totalVehicles} total vehicles")
                ->descriptionIcon('heroicon-m-truck')
                ->color($availableVehicles > 0 ? 'success' : 'danger'),
            
            Stat::make('Utilization Rate', $totalDrivers > 0 ? round(($driversOnTrips / $totalDrivers) * 100, 1) . '%' : '0%')
                ->description('Driver utilization')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('info'),

            Stat::make('Total Companies', Cache::remember('kpi:total_companies', 300, fn () => Company::count()))
                ->description('Organizations in system')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('gray'),
        ];
    }
}
