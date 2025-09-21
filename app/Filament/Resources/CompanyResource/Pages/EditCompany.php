<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to Companies')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => CompanyResource::getUrl('index')),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Company')
                ->modalDescription('Are you sure you want to delete this company? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete it'),
        ];
    }
}
