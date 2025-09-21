<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_driver(): void
    {
        $company = Company::factory()->create();
        
        $driverData = [
            'company_id' => $company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'license_number' => 'DL123456789',
            'hire_date' => now()->toDateString(),
            'is_active' => true,
        ];

        $driver = Driver::create($driverData);

        $this->assertDatabaseHas('drivers', [
            'company_id' => $driverData['company_id'],
            'name' => $driverData['name'],
            'email' => $driverData['email'],
            'phone' => $driverData['phone'],
            'license_number' => $driverData['license_number'],
            'hire_date' => $driverData['hire_date'],
            'is_active' => $driverData['is_active'],
        ]);
        $this->assertEquals($driverData['name'], $driver->name);
    }

    public function test_driver_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);

        $this->assertEquals($company->id, $driver->company->id);
        $this->assertEquals($company->name, $driver->company->name);
    }

    public function test_driver_has_trips_relationship(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertTrue($driver->trips->contains($trip));
        $this->assertEquals(1, $driver->trips->count());
    }

    public function test_driver_active_scope(): void
    {
        $company = Company::factory()->create();
        $activeDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $inactiveDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $activeDrivers = Driver::active()->get();

        $this->assertTrue($activeDrivers->contains($activeDriver));
        $this->assertFalse($activeDrivers->contains($inactiveDriver));
        $this->assertEquals(1, $activeDrivers->count());
    }

    public function test_driver_email_must_be_unique(): void
    {
        $company = Company::factory()->create();
        
        Driver::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@example.com',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Driver::factory()->create([
            'company_id' => $company->id,
            'email' => 'john@example.com',
        ]);
    }

    public function test_driver_license_number_must_be_unique(): void
    {
        $company = Company::factory()->create();
        
        Driver::factory()->create([
            'company_id' => $company->id,
            'license_number' => 'DL123456789',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Driver::factory()->create([
            'company_id' => $company->id,
            'license_number' => 'DL123456789',
        ]);
    }
}
