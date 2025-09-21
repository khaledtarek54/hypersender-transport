<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_trip(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        
        $tripData = [
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'origin' => 'New York',
            'destination' => 'Boston',
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(5),
            'status' => 'scheduled',
            'distance' => 220.5,
            'notes' => 'Test trip',
        ];

        $trip = Trip::create($tripData);

        $this->assertDatabaseHas('trips', $tripData);
        $this->assertEquals($tripData['origin'], $trip->origin);
    }

    public function test_trip_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertEquals($company->id, $trip->company->id);
        $this->assertEquals($company->name, $trip->company->name);
    }

    public function test_trip_belongs_to_driver(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertEquals($driver->id, $trip->driver->id);
        $this->assertEquals($driver->name, $trip->driver->name);
    }

    public function test_trip_belongs_to_vehicle(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertEquals($vehicle->id, $trip->vehicle->id);
        $this->assertEquals($vehicle->license_plate, $trip->vehicle->license_plate);
    }

    public function test_trip_status_constants(): void
    {
        $this->assertEquals('scheduled', Trip::STATUS_SCHEDULED);
        $this->assertEquals('in_progress', Trip::STATUS_IN_PROGRESS);
        $this->assertEquals('completed', Trip::STATUS_COMPLETED);
        $this->assertEquals('cancelled', Trip::STATUS_CANCELLED);
    }

    public function test_trip_status_scope(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'scheduled',
        ]);
        
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
        ]);

        $scheduledTrips = Trip::status('scheduled')->get();
        $activeTrips = Trip::active()->get();

        $this->assertEquals(1, $scheduledTrips->count());
        $this->assertEquals(1, $activeTrips->count());
    }

    public function test_trip_distance_is_decimal(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);
        
        $trip = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'distance' => 123.45,
        ]);

        $this->assertEquals(123.45, $trip->distance);
        $this->assertIsNumeric($trip->distance);
    }
}
