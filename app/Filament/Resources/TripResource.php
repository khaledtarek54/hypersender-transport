<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Enums\TripStatus;
use App\Models\Trip;
use App\Rules\NoOverlappingTrips;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive(),
                Forms\Components\Select::make('driver_id')
                    ->relationship('driver', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function (callable $get) {
                        $companyId = $get('company_id');
                        if (!$companyId) {
                            return [];
                        }
                        return \App\Models\Driver::where('company_id', $companyId)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    }),
                Forms\Components\Select::make('vehicle_id')
                    ->relationship('vehicle', 'license_plate')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function (callable $get) {
                        $companyId = $get('company_id');
                        if (!$companyId) {
                            return [];
                        }
                        return \App\Models\Vehicle::where('company_id', $companyId)
                            ->where('is_active', true)
                            ->pluck('license_plate', 'id');
                    }),
                Forms\Components\TextInput::make('origin')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('destination')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->after('start_time')
                    ->rules([
                        fn (Get $get) => new NoOverlappingTrips(
                            $get('driver_id'),
                            $get('vehicle_id'),
                            $get('start_time'),
                            $get('end_time'),
                            request()->route('record')
                        ),
                    ]),
                Forms\Components\Select::make('status')
                    ->options(collect(TripStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray())
                    ->default(TripStatus::Scheduled->value)
                    ->required(),
                Forms\Components\TextInput::make('distance')
                    ->numeric()
                    ->step(0.01)
                    ->suffix('km'),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.license_plate')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('origin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof TripStatus ? $state->label() : (TripStatus::tryFrom($state)?->label() ?? ucfirst(str_replace('_', ' ', (string) $state))))
                    ->color(function ($state): string {
                        $enum = $state instanceof TripStatus ? $state : TripStatus::tryFrom((string) $state);
                        return $enum?->color() ?? 'gray';
                    }),
                Tables\Columns\TextColumn::make('distance')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' km'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(TripStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray()),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_time', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_time', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
