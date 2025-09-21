<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = new AvailabilityService();
    }

    public function test_get_available_drivers_returns_active_drivers(): void
    {
        $company = Company::factory()->create();
        $activeDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $inactiveDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $availableDrivers = $this->availabilityService->getAvailableDrivers();

        $this->assertTrue($availableDrivers->contains($activeDriver));
        $this->assertFalse($availableDrivers->contains($inactiveDriver));
    }

    public function test_get_available_vehicles_returns_active_vehicles(): void
    {
        $company = Company::factory()->create();
        $activeVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $inactiveVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => false]);

        $availableVehicles = $this->availabilityService->getAvailableVehicles();

        $this->assertTrue($availableVehicles->contains($activeVehicle));
        $this->assertFalse($availableVehicles->contains($inactiveVehicle));
    }

    public function test_driver_availability_with_custom_time_range(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = Carbon::now()->addDay();
        $endTime = Carbon::now()->addDay()->addHours(2);

        // No trips, driver should be available
        $this->assertTrue($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));

        // Create a trip outside the time range
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addDays(2),
            'end_time' => Carbon::now()->addDays(2)->addHours(1),
            'status' => 'scheduled',
        ]);

        // Driver should still be available
        $this->assertTrue($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));
    }

    public function test_vehicle_availability_with_custom_time_range(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = Carbon::now()->addDay();
        $endTime = Carbon::now()->addDay()->addHours(2);

        // No trips, vehicle should be available
        $this->assertTrue($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));

        // Create a trip outside the time range
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addDays(2),
            'end_time' => Carbon::now()->addDays(2)->addHours(1),
            'status' => 'scheduled',
        ]);

        // Vehicle should still be available
        $this->assertTrue($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));
    }

    public function test_get_upcoming_trips_for_driver(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create past trip (should not be included)
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
            'status' => 'completed',
        ]);

        // Create future trips
        $trip1 = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(3),
            'status' => 'scheduled',
        ]);

        $trip2 = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addHours(4),
            'end_time' => Carbon::now()->addHours(6),
            'status' => 'scheduled',
        ]);

        $upcomingTrips = $this->availabilityService->getUpcomingTripsForDriver($driver->id);

        $this->assertEquals(2, $upcomingTrips->count());
        $this->assertTrue($upcomingTrips->contains($trip1));
        $this->assertTrue($upcomingTrips->contains($trip2));
    }

    public function test_get_upcoming_trips_for_vehicle(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create future trips
        $trip1 = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(3),
            'status' => 'scheduled',
        ]);

        $trip2 = Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => Carbon::now()->addHours(4),
            'end_time' => Carbon::now()->addHours(6),
            'status' => 'scheduled',
        ]);

        $upcomingTrips = $this->availabilityService->getUpcomingTripsForVehicle($vehicle->id);

        $this->assertEquals(2, $upcomingTrips->count());
        $this->assertTrue($upcomingTrips->contains($trip1));
        $this->assertTrue($upcomingTrips->contains($trip2));
    }

    public function test_completed_trips_do_not_affect_availability(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = Carbon::now()->addHour();
        $endTime = Carbon::now()->addHours(3);

        // Create a completed trip
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'completed',
        ]);

        // Driver and vehicle should still be available
        $this->assertTrue($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));
        $this->assertTrue($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));
    }

    public function test_cancelled_trips_do_not_affect_availability(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        $startTime = Carbon::now()->addHour();
        $endTime = Carbon::now()->addHours(3);

        // Create a cancelled trip
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'cancelled',
        ]);

        // Driver and vehicle should still be available
        $this->assertTrue($this->availabilityService->isDriverAvailable($driver->id, $startTime, $endTime));
        $this->assertTrue($this->availabilityService->isVehicleAvailable($vehicle->id, $startTime, $endTime));
    }
}
