<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static ?int $navigationSort = -1;
    
    protected static ?string $title = 'Dashboard';
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
    
    public function getHeading(): string
    {
        return 'Welcome to Hypersender Transport';
    }
    
    public function getSubheading(): ?string
    {
        return 'Monitor your fleet, track trips, and manage your transport operations';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_company')
                ->label('New Company')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(fn (): string => \App\Filament\Resources\CompanyResource::getUrl('create')),
            Actions\Action::make('create_trip')
                ->label('New Trip')
                ->icon('heroicon-o-plus')
                ->color('info')
                ->url(fn (): string => \App\Filament\Resources\TripResource::getUrl('create')),
            Actions\Action::make('create_driver')
                ->label('Add Driver')
                ->icon('heroicon-o-user-plus')
                ->color('warning')
                ->url(fn (): string => \App\Filament\Resources\DriverResource::getUrl('create')),
            Actions\Action::make('create_vehicle')
                ->label('Add Vehicle')
                ->icon('heroicon-o-truck')
                ->color('danger')
                ->url(fn (): string => \App\Filament\Resources\VehicleResource::getUrl('create')),
        ];
    }
}
