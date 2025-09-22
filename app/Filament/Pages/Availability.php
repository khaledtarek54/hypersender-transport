<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\AvailableDriversTable;
use App\Filament\Widgets\AvailableVehiclesTable;

class Availability extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.availability';
    protected static ?string $navigationGroup = null;
    protected static ?string $navigationLabel = 'Availability';
    protected static ?int $navigationSort = 1;


    protected function getHeaderWidgets(): array
    {
        return [
            AvailableDriversTable::class,
            AvailableVehiclesTable::class,
        ];
    }
}



