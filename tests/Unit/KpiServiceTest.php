<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\KpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class KpiServiceTest extends TestCase
{
    use RefreshDatabase;

    private KpiService $kpiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kpiService = new KpiService();
    }

    public function test_get_trip_statistics(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        // Create trips with different statuses
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'cancelled',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'scheduled',
            'created_at' => now(),
        ]);

        $stats = $this->kpiService->getTripStatistics();

        $this->assertEquals(4, $stats['total_trips']);
        $this->assertEquals(2, $stats['completed_trips']);
        $this->assertEquals(1, $stats['cancelled_trips']);
        $this->assertEquals(50, $stats['completion_rate']);
        $this->assertEquals(25, $stats['cancellation_rate']);
    }

    public function test_get_driver_performance(): void
    {
        $company = Company::factory()->create();
        $driver1 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $driver2 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        // Create trips for driver1
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'cancelled',
            'created_at' => now(),
        ]);

        // Create trips for driver2
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $performance = $this->kpiService->getDriverPerformance();

        $this->assertCount(2, $performance);

        $driver1Performance = collect($performance)->firstWhere('id', $driver1->id);
        $driver2Performance = collect($performance)->firstWhere('id', $driver2->id);

        $this->assertEquals(2, $driver1Performance['completed_trips_count']);
        $this->assertEquals(3, $driver1Performance['total_trips_count']);
        $this->assertEquals(66.67, $driver1Performance['completion_rate']);

        $this->assertEquals(1, $driver2Performance['completed_trips_count']);
        $this->assertEquals(1, $driver2Performance['total_trips_count']);
        $this->assertEquals(100, $driver2Performance['completion_rate']);
    }

    public function test_get_vehicle_utilization(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle1 = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $vehicle2 = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);

        // Create trips for vehicle1
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle1->id,
            'status' => 'scheduled',
            'created_at' => now(),
        ]);

        $utilization = $this->kpiService->getVehicleUtilization();

        $this->assertCount(2, $utilization);

        $vehicle1Utilization = collect($utilization)->firstWhere('id', $vehicle1->id);
        $vehicle2Utilization = collect($utilization)->firstWhere('id', $vehicle2->id);

        $this->assertEquals(2, $vehicle1Utilization['active_trips_count']);
        $this->assertEquals(0, $vehicle2Utilization['active_trips_count']);
    }

    public function test_get_company_performance(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $driver1 = Driver::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
        $driver2 = Driver::factory()->create(['company_id' => $company2->id, 'is_active' => true]);
        $vehicle1 = Vehicle::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
        $vehicle2 = Vehicle::factory()->create(['company_id' => $company2->id, 'is_active' => true]);

        // Create trips for company1
        Trip::factory()->create([
            'company_id' => $company1->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle1->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company1->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle1->id,
            'status' => 'scheduled',
            'created_at' => now(),
        ]);

        // Create trips for company2
        Trip::factory()->create([
            'company_id' => $company2->id,
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle2->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $performance = $this->kpiService->getCompanyPerformance();

        $this->assertCount(2, $performance);

        $company1Performance = collect($performance)->firstWhere('id', $company1->id);
        $company2Performance = collect($performance)->firstWhere('id', $company2->id);

        $this->assertEquals(2, $company1Performance['total_trips_count']);
        $this->assertEquals(1, $company1Performance['completed_trips_count']);
        $this->assertEquals(50, $company1Performance['completion_rate']);
        $this->assertEquals(1, $company1Performance['active_drivers_count']);
        $this->assertEquals(1, $company1Performance['active_vehicles_count']);

        $this->assertEquals(1, $company2Performance['total_trips_count']);
        $this->assertEquals(1, $company2Performance['completed_trips_count']);
        $this->assertEquals(100, $company2Performance['completion_rate']);
    }

    public function test_get_monthly_trip_trends(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        // Create trips in different months
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now()->subMonth(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $trends = $this->kpiService->getMonthlyTripTrends(12);

        $this->assertCount(12, $trends);
        $this->assertArrayHasKey('month', $trends[0]);
        $this->assertArrayHasKey('total_trips', $trends[0]);
        $this->assertArrayHasKey('completed_trips', $trends[0]);
        $this->assertArrayHasKey('completion_rate', $trends[0]);
    }

    public function test_get_top_performing_drivers(): void
    {
        $company = Company::factory()->create();
        $driver1 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $driver2 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        $driver3 = Driver::factory()->create(['company_id' => $company->id, 'is_active' => false]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        // Create trips for driver1 (2 completed)
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver1->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        // Create trips for driver2 (1 completed)
        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver2->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        $topDrivers = $this->kpiService->getTopPerformingDrivers();

        $this->assertCount(2, $topDrivers); // Only active drivers
        $this->assertEquals($driver1->id, $topDrivers[0]['id']);
        $this->assertEquals(2, $topDrivers[0]['completed_trips_count']);
        $this->assertEquals($driver2->id, $topDrivers[1]['id']);
        $this->assertEquals(1, $topDrivers[1]['completed_trips_count']);
    }

    public function test_get_distance_statistics(): void
    {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'distance' => 100.5,
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'distance' => 200.25,
            'created_at' => now(),
        ]);

        Trip::factory()->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'distance' => null, // No distance
            'created_at' => now(),
        ]);

        $distanceStats = $this->kpiService->getDistanceStatistics();

        $this->assertEquals(300.75, $distanceStats['total_distance']);
        $this->assertEquals(150.38, $distanceStats['average_distance']);
        $this->assertEquals(200.25, $distanceStats['longest_trip']);
        $this->assertEquals(100.5, $distanceStats['shortest_trip']);
        $this->assertEquals(2, $distanceStats['total_trips_with_distance']);
    }
}
