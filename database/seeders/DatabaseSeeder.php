<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test user for admin access
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // Create companies
        $companies = Company::factory(5)->create();

        // Create drivers for each company
        foreach ($companies as $company) {
            $drivers = Driver::factory(3)->create([
                'company_id' => $company->id,
            ]);

            // Create vehicles for each company
            $vehicles = Vehicle::factory(4)->create([
                'company_id' => $company->id,
            ]);

            // Create trips for each company
            foreach ($drivers as $driver) {
                Trip::factory(2)->create([
                    'company_id' => $company->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicles->random()->id,
                ]);
            }
        }

        // Create some additional trips with different statuses
        $allDrivers = Driver::all();
        $allVehicles = Vehicle::all();

        foreach ($allDrivers->take(3) as $driver) {
            Trip::factory()->create([
                'company_id' => $driver->company_id,
                'driver_id' => $driver->id,
                'vehicle_id' => $allVehicles->where('company_id', $driver->company_id)->random()->id,
                'status' => 'completed',
                'start_time' => now()->subDays(2),
                'end_time' => now()->subDays(2)->addHours(4),
            ]);
        }

        foreach ($allDrivers->skip(3)->take(2) as $driver) {
            Trip::factory()->create([
                'company_id' => $driver->company_id,
                'driver_id' => $driver->id,
                'vehicle_id' => $allVehicles->where('company_id', $driver->company_id)->random()->id,
                'status' => 'cancelled',
                'start_time' => now()->subDay(),
                'end_time' => now()->subDay()->addHours(3),
            ]);
        }
    }
}
