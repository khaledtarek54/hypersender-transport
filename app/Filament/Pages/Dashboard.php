<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Services\KpiService;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = -1;
    
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\ActiveTripsWidget::class,
            \App\Filament\Widgets\AvailableResourcesWidget::class,
            \App\Filament\Widgets\MonthlyTripsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
}
