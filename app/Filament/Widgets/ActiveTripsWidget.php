<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Enums\TripStatus;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
 

class ActiveTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now();

        $activeNow = Trip::whereIn('status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->count();

        $scheduledToday = Trip::where('status', TripStatus::Scheduled->value)
            ->whereDate('start_time', $now->toDateString())
            ->count();


        $completedThisMonth = Trip::where('status', TripStatus::Completed->value)
            ->whereMonth('end_time', $now->month)
            ->whereYear('end_time', $now->year)
            ->count();

        return [
            Stat::make('Active Trips Now', $activeNow)
                ->description('Trips currently running')
                ->descriptionIcon('heroicon-m-play')
                ->color('primary'),

            Stat::make('Scheduled Today', $scheduledToday)
                ->description('New trips starting today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Completed This Month', $completedThisMonth)
                ->description('Trips finished this month')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
