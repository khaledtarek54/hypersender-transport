<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\AvailabilityService;
use Carbon\Carbon;

describe('Availability Feature Tests', function () {
    
    beforeEach(function () {
        $this->availabilityService = new AvailabilityService();
    });

    describe('Integration Tests', function () {
        
        test('complete availability workflow', function () {
            // Setup multiple companies with resources
            $company1 = Company::factory()->create(['name' => 'Company A']);
            $company2 = Company::factory()->create(['name' => 'Company B']);

            $driver1 = Driver::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
            $driver2 = Driver::factory()->create(['company_id' => $company2->id, 'is_active' => true]);
            $driver3 = Driver::factory()->create(['company_id' => $company1->id, 'is_active' => false]); // Inactive

            $vehicle1 = Vehicle::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
            $vehicle2 = Vehicle::factory()->create(['company_id' => $company2->id, 'is_active' => true]);

            // Create a trip for driver1 and vehicle1
            $trip = createTrip($company1, $driver1, $vehicle1);

            // Check availability
            $availableDrivers = $this->availabilityService->getAvailableDrivers();
            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            expect($availableDrivers->contains(fn($d) => $d->is($driver1)))->toBeFalse();
            expect($availableDrivers->contains(fn($d) => $d->is($driver2)))->toBeTrue();
            expect($availableDrivers->contains(fn($d) => $d->is($driver3)))->toBeFalse();

            expect($availableVehicles->contains(fn($v) => $v->is($vehicle1)))->toBeFalse();
            expect($availableVehicles->contains(fn($v) => $v->is($vehicle2)))->toBeTrue();
        });

        test('multi-company resource isolation', function () {
            $company1 = Company::factory()->create();
            $company2 = Company::factory()->create();

            $driver1 = Driver::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
            $driver2 = Driver::factory()->create(['company_id' => $company2->id, 'is_active' => true]);

            $vehicle1 = Vehicle::factory()->create(['company_id' => $company1->id, 'is_active' => true]);
            $vehicle2 = Vehicle::factory()->create(['company_id' => $company2->id, 'is_active' => true]);

            // Create trip in company1
            createTrip($company1, $driver1, $vehicle1);

            // Company2 resources should still be available
            $availableDrivers = $this->availabilityService->getAvailableDrivers();
            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            expect($availableDrivers->contains(fn($d) => $d->is($driver2)))->toBeTrue();
            expect($availableVehicles->contains(fn($v) => $v->is($vehicle2)))->toBeTrue();
        });

        test('time-based availability checks across multiple trips', function () {
            $resources = createCompanyWithResources();

            // Create multiple trips at different times
            $trip1 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::tomorrow()->setTime(9, 0),
                Carbon::tomorrow()->setTime(11, 0)
            );

            $trip2 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::tomorrow()->setTime(14, 0),
                Carbon::tomorrow()->setTime(16, 0)
            );

            // Check availability between trips
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                Carbon::tomorrow()->setTime(12, 0),
                Carbon::tomorrow()->setTime(13, 0)
            ))->toBeTrue();

            // Check availability during trips
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                Carbon::tomorrow()->setTime(10, 0),
                Carbon::tomorrow()->setTime(12, 0) // Overlaps with trip1
            ))->toBeFalse();

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                Carbon::tomorrow()->setTime(13, 0),
                Carbon::tomorrow()->setTime(15, 0) // Overlaps with trip2
            ))->toBeFalse();
        });

        test('bulk availability operations', function () {
            // Create 10 drivers and vehicles
            $company = Company::factory()->create();
            $drivers = Driver::factory(10)->create(['company_id' => $company->id, 'is_active' => true]);
            $vehicles = Vehicle::factory(10)->create(['company_id' => $company->id, 'is_active' => true]);

            // Create trips for half of them
            for ($i = 0; $i < 5; $i++) {
                createTrip($company, $drivers[$i], $vehicles[$i]);
            }

            $availableDrivers = $this->availabilityService->getAvailableDrivers();
            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            expect($availableDrivers)->toHaveCount(5);
            expect($availableVehicles)->toHaveCount(5);

            // Verify correct ones are available
            for ($i = 5; $i < 10; $i++) {
                expect($availableDrivers->contains(fn($d) => $d->is($drivers[$i])))->toBeTrue();
                expect($availableVehicles->contains(fn($v) => $v->is($vehicles[$i])))->toBeTrue();
            }
        });

        test('real-time availability updates', function () {
            $resources = createCompanyWithResources();
            $startTime = now()->addHour();
            $endTime = now()->addHours(3);

            // Initially available
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeTrue();

            // Create trip
            $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle'], $startTime, $endTime);

            // Now unavailable
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeFalse();

            // Cancel trip
            $trip->update(['status' => 'cancelled']);

            // Available again
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeTrue();
        });

        test('performance with large datasets', function () {
            $company = Company::factory()->create();
            
            // Create many resources
            $drivers = Driver::factory(50)->create(['company_id' => $company->id, 'is_active' => true]);
            $vehicles = Vehicle::factory(50)->create(['company_id' => $company->id, 'is_active' => true]);

            // Create many trips
            for ($i = 0; $i < 100; $i++) {
                createTrip(
                    $company, 
                    $drivers[rand(0, 49)], 
                    $vehicles[rand(0, 49)],
                    now()->addHours(rand(1, 72)),
                    now()->addHours(rand(73, 168))
                );
            }

            $startTime = microtime(true);
            
            $availableDrivers = $this->availabilityService->getAvailableDrivers();
            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Should complete within reasonable time (adjust as needed)
            expect($executionTime)->toBeLessThan(1.0); // 1 second
            expect($availableDrivers)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($availableVehicles)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        });
    });

    describe('Error Handling', function () {
        
        test('handles database constraints gracefully', function () {
            $resources = createCompanyWithResources();

            // Create initial trip
            $first = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

            // Create overlapping trip (no exception expected at model layer)
            $second = createTrip(
                $resources['company'],
                $resources['driver'],
                $resources['vehicle'],
                now()->addMinutes(30),
                now()->addHours(2)
            );

            // Assert both persisted
            expect(App\Models\Trip::where('driver_id', $resources['driver']->id)->count())->toBe(2);

            // Service should report unavailability during overlapping window
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $second->start_time,
                $second->end_time
            ))->toBeFalse();
        });

        test('handles soft-deleted resources', function () {
            $resources = createCompanyWithResources();

            // Soft delete driver
            $resources['driver']->delete();

            $availableDrivers = $this->availabilityService->getAvailableDrivers();

            expect($availableDrivers)->not->toContain($resources['driver']);
        });
    });
});
