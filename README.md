# Hypersender Transport

> A fleet-management demo built with **Laravel 11** and **Filament 5** — companies, drivers, vehicles, and trips, with an availability engine, KPI dashboards, and a comprehensive Pest test suite.

## Why this project

Built as a reference for production-grade Laravel admin panels: real availability/conflict logic, dashboard widgets that compute live, and tests that exercise both the domain model and the Filament UI layer.

## Features

- **Filament admin** with custom dashboard widgets:
  - Active KPIs (drivers, vehicles, trips)
  - Monthly trips line chart
  - Trips-by-status doughnut chart (last 30 days)
  - Driver / vehicle availability cards
- **Availability engine** — overlap detection for drivers and vehicles across trip windows
- **Domain model** — Eloquent relationships across Companies, Drivers, Vehicles, Trips
- **Trip scopes & computed properties** — duration, status, current-trip lookup
- **Pest test suite** — feature + unit coverage for the availability engine and Filament resources

## Tech stack

- PHP 8.2+
- Laravel 11
- Filament 5
- MySQL
- Pest 3
- Livewire 3 (Filament-bundled)

## Architecture

```
app/
├── Filament/Resources/    # Companies, Drivers, Vehicles, Trips
├── Filament/Widgets/      # Dashboard KPI cards + charts
├── Models/                # Eloquent + relationships + scopes
└── Services/Availability  # Overlap detection engine
tests/
├── Feature/               # End-to-end Filament resource tests
└── Unit/                  # Availability engine + scopes
```

## Quick start

```bash
git clone https://github.com/khaledtarek54/hypersender-transport
cd hypersender-transport
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Visit `/admin` and log in with the seeded credentials.

## Run the tests

```bash
php artisan test
# or
./vendor/bin/pest
```

## Screenshots

> Coming soon — dashboard, driver list, trip detail.

## License

MIT

---

Built by [Khaled Tarek](https://github.com/khaledtarek54) · [LinkedIn](https://www.linkedin.com/in/khaled-tarek-3596401b1)
