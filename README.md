

# Hypersender Transport (Filament forever 🥳)

A transport management demo built with Laravel, Eloquent, and Filament. It manages companies, drivers, vehicles, and trips; provides availability calculations; and ships with a polished Filament admin dashboard and a comprehensive test suite.

## ✨ Highlights

- Filament admin with custom dashboard widgets
  - Active KPIs and resource availability
  - Monthly trips line chart
  - Trips by status doughnut chart (last 30 days)
- Availability engine (drivers/vehicles) with overlap logic
- Eloquent relationships for Companies, Drivers, Vehicles, Trips
- Trip scopes and computed properties (duration)
- Pest tests (Feature + Unit) for confidence and safety

## 🚀 Quick Start

Prerequisites:
- PHP 8.2+
- Composer
- Mysql (default) or your preferred database

Clone & install:
```bash
git clone https://github.com/khaledtarek54/hypersender-transport
cd hypersender-transport
composer install
cp .env.example .env
```

Generate app key and migrate:
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
```

Serve the app:
```bash
php artisan serve
# Filament panel will be under /app (e.g., http://127.0.0.1:8000/app)
```

Run tests:
```bash
vendor\bin\pest.bat  # Windows
# or
./vendor/bin/pest     # macOS/Linux
```

## 🧭 Domain Model

- `Company` has many `Driver`, `Vehicle`, `Trip`
- `Driver` belongs to `Company`, has many `Trip`
- `Vehicle` belongs to `Company`, has many `Trip`
- `Trip` belongs to `Company`, `Driver`, `Vehicle`

Trip enum: `App\Models\Enums\TripStatus` (scheduled, in_progress, completed, cancelled)

Computed property:
- `Trip::duration_minutes` – difference between `start_time` and `end_time` in minutes

Scopes:
- `Trip::upcoming()` – start time in the future
- `Trip::completed()` – status completed
- `Trip::active()` – scheduled or in_progress
- `Trip::status($status)` – filter by status

Driver & Vehicle:
- `active()` scope for filtering active resources

Validation rule:
- `App\Rules\NoOverlappingTrips` – prevents overlapping trips for the same driver/vehicle (inclusive bounds)

Availability service:
- `App\Services\AvailabilityService` – returns available drivers/vehicles and upcoming trips per resource

## 📊 Admin Dashboard (Filament)

Location: `app/Filament/Pages/Dashboard.php`

Widgets:
- `ActiveTripsWidget` – key metrics
- `AvailableResourcesWidget` – drivers/vehicles available now
- `MonthlyTripsWidget` – trips per month (last 12 months)
- `TripsByStatusWidget` – trips by status (last 30 days, doughnut)


