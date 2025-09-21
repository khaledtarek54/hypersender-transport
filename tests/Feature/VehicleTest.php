<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_vehicle(): void
    {
        $company = Company::factory()->create();
        
        $vehicleData = [
            'company_id' => $company->id,
            'license_plate' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'capacity' => 5,
            'is_active' => true,
        ];

        $vehicle = Vehicle::create($vehicleData);

        $this->assertDatabaseHas('vehicles', $vehicleData);
        $this->assertEquals($vehicleData['make'], $vehicle->make);
    }

    public function test_vehicle_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        $this->assertEquals($company->id, $vehicle->company->id);
        $this->assertEquals($company->name, $vehicle->company->name);
    }

    public function test_vehicle_has_trips_relationship(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertTrue($vehicle->trips->contains($trip));
        $this->assertEquals(1, $vehicle->trips->count());
    }

    public function test_vehicle_active_scope(): void
    {
        $company = Company::factory()->create();
        $activeVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $inactiveVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $activeVehicles = Vehicle::active()->get();

        $this->assertTrue($activeVehicles->contains($activeVehicle));
        $this->assertFalse($activeVehicles->contains($inactiveVehicle));
        $this->assertEquals(1, $activeVehicles->count());
    }

    public function test_vehicle_license_plate_must_be_unique(): void
    {
        $company = Company::factory()->create();
        
        Vehicle::factory()->create([
            'company_id' => $company->id,
            'license_plate' => 'ABC123',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Vehicle::factory()->create([
            'company_id' => $company->id,
            'license_plate' => 'ABC123',
        ]);
    }

    public function test_vehicle_year_validation(): void
    {
        $company = Company::factory()->create();
        
        $vehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'year' => 2020,
        ]);

        $this->assertEquals(2020, $vehicle->year);
        $this->assertIsInt($vehicle->year);
    }
}
