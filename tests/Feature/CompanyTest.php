<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company(): void
    {
        $companyData = [
            'name' => 'Test Transport Company',
            'email' => 'test@company.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street, Test City',
        ];

        $company = Company::create($companyData);

        $this->assertDatabaseHas('companies', $companyData);
        $this->assertEquals($companyData['name'], $company->name);
    }

    public function test_company_has_drivers_relationship(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($company->drivers->contains($driver));
        $this->assertEquals(1, $company->drivers->count());
    }

    public function test_company_has_vehicles_relationship(): void
    {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        $this->assertTrue($company->vehicles->contains($vehicle));
        $this->assertEquals(1, $company->vehicles->count());
    }

    public function test_company_has_trips_relationship(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertTrue($company->trips->contains($trip));
        $this->assertEquals(1, $company->trips->count());
    }

    public function test_company_cascade_deletion(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $company->delete();

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
    }
}
