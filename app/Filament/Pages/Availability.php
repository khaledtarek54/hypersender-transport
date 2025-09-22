<?php

namespace App\Filament\Pages;

use App\Services\AvailabilityService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;

class Availability extends Page
{
    use InteractsWithForms;
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static string $view = 'filament.pages.availability';

    protected static ?string $navigationGroup = null;

    public ?array $data = [];

    public array $drivers = [];
    public array $vehicles = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_time' => now()->format('Y-m-d H:i'),
            'end_time' => now()->copy()->addHours(2)->format('Y-m-d H:i'),
        ]);
        $this->search();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\DateTimePicker::make('start_time')->label('Start time')->native(false)->seconds(false)->required(),
                    Forms\Components\DateTimePicker::make('end_time')->label('End time')->native(false)->seconds(false)->required()->after('start_time'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('search')->label('Search')->submit('search'),
                    ])->columnSpan(1),
                ]),
            ])->statePath('data');
    }

    public function search(): void
    {
        $service = app(AvailabilityService::class);

        $start = Carbon::parse($this->data['start_time'] ?? now());
        $end = Carbon::parse($this->data['end_time'] ?? now()->copy()->addHours(2));

        $this->drivers = $service->getAvailableDrivers($start, $end)->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'company' => $d->company?->name,
        ])->values()->all();

        $this->vehicles = $service->getAvailableVehicles($start, $end)->map(fn ($v) => [
            'id' => $v->id,
            'license_plate' => $v->license_plate,
            'company' => $v->company?->name,
        ])->values()->all();
    }
}


