<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\Vehicle;
use App\Models\Enums\TripStatus;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class AvailableVehiclesTable extends BaseWidget
{
    protected static ?string $heading = 'Available Vehicles';

    protected function getTableQuery(): Builder
    {
        return Vehicle::query()
            ->where('is_active', true)
            ->with('company');
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('license_plate')->label('Plate')->searchable(),
                Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable(),
                Tables\Columns\TextColumn::make('make')->label('Make')->searchable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('period')
                    ->form([
                        DatePicker::make('start')->label('Start')->required(),
                        DatePicker::make('end')->label('End')->required()->after('start'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $start = $data['start'] ?? null;
                        $end = $data['end'] ?? null;
                        if (! $start || ! $end) {
                            return $query;
                        }
                        return $query->whereNotExists(function ($sub) use ($start, $end) {
                            $sub->select(DB::raw(1))
                                ->from('trips')
                                ->whereColumn('trips.vehicle_id', 'vehicles.id')
                                ->whereIn('trips.status', [TripStatus::Scheduled->value, TripStatus::InProgress->value])
                                ->where(function ($q) use ($start, $end) {
                                    $q->whereBetween('trips.start_time', [$start, $end])
                                        ->orWhereBetween('trips.end_time', [$start, $end])
                                        ->orWhere(function ($qq) use ($start, $end) {
                                            $qq->where('trips.start_time', '<=', $start)
                                               ->where('trips.end_time', '>=', $end);
                                        });
                                });
                        });
                    })
                    ->default(
                        fn () => [
                            'start' => now()->format('Y-m-d H:i:s'),
                            'end' => now()->copy()->addHours(2)->format('Y-m-d H:i:s'),
                        ]
                    ),
            ])
            ->paginated(false);
    }
}


