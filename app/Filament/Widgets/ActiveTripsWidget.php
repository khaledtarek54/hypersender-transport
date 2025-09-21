<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiveTripsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $scheduledTrips = Trip::where('status', 'scheduled')->count();
        $inProgressTrips = Trip::where('status', 'in_progress')->count();
        $completedToday = Trip::where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();
        $totalTrips = Trip::count();

        return [
            Stat::make('Scheduled Trips', $scheduledTrips)
                ->description('Trips waiting to start')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
            
            Stat::make('In Progress', $inProgressTrips)
                ->description('Currently active trips')
                ->descriptionIcon('heroicon-m-play')
                ->color('primary'),
            
            Stat::make('Completed Today', $completedToday)
                ->description('Trips finished today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Total Trips', $totalTrips)
                ->description('All time trips')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }
}
