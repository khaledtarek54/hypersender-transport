<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = new AvailabilityService();
    }

    public function test_can_get_available_drivers(): void
    {
        $company = Company::factory()->create();
        $driver1 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $driver2 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create a trip for driver1
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(3),
            'status' => 'scheduled',
        ]);

        $availableDrivers = $this->availabilityService->getAvailableDrivers();

        $this->assertFalse($availableDrivers->contains($driver1));
        $this->assertTrue($availableDrivers->contains($driver2));
        $this->assertEquals(1, $availableDrivers->count());
    }

    public function test_can_get_available_vehicles(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle1 = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle2 = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create a trip for vehicle1
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(3),
            'status' => 'scheduled',
        ]);

        $availableVehicles = $this->availabilityService->getAvailableVehicles();

        $this->assertFalse($availableVehicles->contains($vehicle1));
        $this->assertTrue($availableVehicles->contains($vehicle2));
        $this->assertEquals(1, $availableVehicles->count());
    }

    public function test_driver_availability_check(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = now()->addHour();
        $endTime = now()->addHours(3);

        // Driver should be available initially
        $this->assertTrue($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));

        // Create a conflicting trip
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
        ]);

        // Driver should not be available now
        $this->assertFalse($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));
    }

    public function test_vehicle_availability_check(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = now()->addHour();
        $endTime = now()->addHours(3);

        // Vehicle should be available initially
        $this->assertTrue($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));

        // Create a conflicting trip
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
        ]);

        // Vehicle should not be available now
        $this->assertFalse($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));
    }

    public function test_overlapping_trip_detection(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create an existing trip from 10:00 to 14:00
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => now()->setTime(10, 0),
            'end_time' => now()->setTime(14, 0),
            'status' => 'scheduled',
        ]);

        // Test overlapping scenarios
        $this->assertFalse($this->availabilityService->isDriverAvailable(
            $driver->id,
            now()->setTime(9, 0),  // 09:00
            now()->setTime(11, 0)  // 11:00 (overlaps)
        ));

        $this->assertFalse($this->availabilityService->isDriverAvailable(
            $driver->id,
            now()->setTime(13, 0), // 13:00
            now()->setTime(15, 0)  // 15:00 (overlaps)
        ));

        $this->assertTrue($this->availabilityService->isDriverAvailable(
            $driver->id,
            now()->setTime(15, 0), // 15:00
            now()->setTime(17, 0)  // 17:00 (no overlap)
        ));
    }

    public function test_inactive_resources_not_available(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => false]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $availableDrivers = $this->availabilityService->getAvailableDrivers();
        $availableVehicles = $this->availabilityService->getAvailableVehicles();

        $this->assertFalse($availableDrivers->contains($driver));
        $this->assertFalse($availableVehicles->contains($vehicle));
    }
}
