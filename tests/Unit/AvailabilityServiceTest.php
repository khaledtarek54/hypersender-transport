<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Services\AvailabilityService;
use Carbon\Carbon;

describe('AvailabilityService', function () {
    
    beforeEach(function () {
        $this->availabilityService = new AvailabilityService();
    });

    describe('Driver Availability', function () {
        
        test('returns active drivers only', function () {
            $company = Company::factory()->create();
            $activeDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => true]);
            $inactiveDriver = Driver::factory()->create(['company_id' => $company->id, 'is_active' => false]);

            $availableDrivers = $this->availabilityService->getAvailableDrivers();

            expect($availableDrivers->contains(fn($d) => $d->is($activeDriver)))->toBeTrue();
            expect($availableDrivers->contains(fn($d) => $d->is($inactiveDriver)))->toBeFalse();
        });

        test('excludes drivers with scheduled trips', function () {
            $resources = createCompanyWithResources();
            $company2 = Company::factory()->create();
            $freeDriver = Driver::factory()->create(['company_id' => $company2->id, 'is_active' => true]);
            
            // Create trip for first driver
            createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

            $availableDrivers = $this->availabilityService->getAvailableDrivers();

            expect($availableDrivers->contains(fn($d) => $d->is($resources['driver'])))->toBeFalse();
            expect($availableDrivers->contains(fn($d) => $d->is($freeDriver)))->toBeTrue();
        });

        test('checks driver availability for specific time range', function () {
            $resources = createCompanyWithResources();
            $startTime = now()->addHour();
            $endTime = now()->addHours(3);

            // Driver should be available initially
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeTrue();

            // Create conflicting trip
            createTrip($resources['company'], $resources['driver'], $resources['vehicle'], $startTime, $endTime);

            // Driver should not be available now
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeFalse();
        });

        test('detects overlapping trips correctly', function () {
            $resources = createCompanyWithResources();

            // Create existing trip from 10:00 to 14:00
            createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                today()->setTime(10, 0),
                today()->setTime(14, 0)
            );

            // Test overlapping scenarios
            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                today()->setTime(9, 0),  // 09:00
                today()->setTime(11, 0)  // 11:00 (overlaps)
            ))->toBeFalse();

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                today()->setTime(13, 0), // 13:00
                today()->setTime(15, 0)  // 15:00 (overlaps)
            ))->toBeFalse();

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                today()->setTime(15, 0), // 15:00
                today()->setTime(17, 0)  // 17:00 (no overlap)
            ))->toBeTrue();
        });

        test('ignores completed and cancelled trips for availability', function () {
            $resources = createCompanyWithResources();
            $startTime = now()->addHour();
            $endTime = now()->addHours(3);

            // Create completed trip
            createTrip($resources['company'], $resources['driver'], $resources['vehicle'], $startTime, $endTime, 'completed');

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeTrue();

            // Create cancelled trip
            createTrip($resources['company'], $resources['driver'], $resources['vehicle'], $startTime, $endTime, 'cancelled');

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                $startTime,
                $endTime
            ))->toBeTrue();
        });
    });

    describe('Vehicle Availability', function () {
        
        test('returns active vehicles only', function () {
            $company = Company::factory()->create();
            $activeVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => true]);
            $inactiveVehicle = Vehicle::factory()->create(['company_id' => $company->id, 'is_active' => false]);

            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            expect($availableVehicles->contains(fn($v) => $v->is($activeVehicle)))->toBeTrue();
            expect($availableVehicles->contains(fn($v) => $v->is($inactiveVehicle)))->toBeFalse();
        });

        test('excludes vehicles with scheduled trips', function () {
            $resources = createCompanyWithResources();
            $company2 = Company::factory()->create();
            $freeVehicle = Vehicle::factory()->create(['company_id' => $company2->id, 'is_active' => true]);
            
            // Create trip for first vehicle
            createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

            $availableVehicles = $this->availabilityService->getAvailableVehicles();

            expect($availableVehicles->contains(fn($v) => $v->is($resources['vehicle'])))->toBeFalse();
            expect($availableVehicles->contains(fn($v) => $v->is($freeVehicle)))->toBeTrue();
        });

        test('checks vehicle availability for specific time range', function () {
            $resources = createCompanyWithResources();
            $startTime = now()->addHour();
            $endTime = now()->addHours(3);

            // Vehicle should be available initially
            expect($this->availabilityService->isVehicleAvailable(
                $resources['vehicle']->id,
                $startTime,
                $endTime
            ))->toBeTrue();

            // Create conflicting trip
            createTrip($resources['company'], $resources['driver'], $resources['vehicle'], $startTime, $endTime);

            // Vehicle should not be available now
            expect($this->availabilityService->isVehicleAvailable(
                $resources['vehicle']->id,
                $startTime,
                $endTime
            ))->toBeFalse();
        });
    });

    describe('Upcoming Trips', function () {
        
        test('gets upcoming trips for driver', function () {
            $resources = createCompanyWithResources();

            // Create past trip (should not be included)
            createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::now()->subHours(2),
                Carbon::now()->subHour(),
                'completed'
            );

            // Create future trips
            $trip1 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::now()->addHour(),
                Carbon::now()->addHours(3)
            );

            $trip2 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::now()->addHours(4),
                Carbon::now()->addHours(6)
            );

            $upcomingTrips = $this->availabilityService->getUpcomingTripsForDriver($resources['driver']->id);

            expect($upcomingTrips)
                ->toHaveCount(2);
            expect($upcomingTrips->contains(fn($t) => $t->is($trip1)))->toBeTrue();
            expect($upcomingTrips->contains(fn($t) => $t->is($trip2)))->toBeTrue();
        });

        test('gets upcoming trips for vehicle', function () {
            $resources = createCompanyWithResources();

            $trip1 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::now()->addHour(),
                Carbon::now()->addHours(3)
            );

            $trip2 = createTrip(
                $resources['company'], 
                $resources['driver'], 
                $resources['vehicle'], 
                Carbon::now()->addHours(4),
                Carbon::now()->addHours(6)
            );

            $upcomingTrips = $this->availabilityService->getUpcomingTripsForVehicle($resources['vehicle']->id);

            expect($upcomingTrips)
                ->toHaveCount(2);
            expect($upcomingTrips->contains(fn($t) => $t->is($trip1)))->toBeTrue();
            expect($upcomingTrips->contains(fn($t) => $t->is($trip2)))->toBeTrue();
        });
    });

    describe('Edge Cases', function () {
        
        test('handles non-existent driver gracefully', function () {
            expect($this->availabilityService->isDriverAvailable(
                999999, // Non-existent ID
                now()->addHour(),
                now()->addHours(2)
            ))->toBeTrue();
        });

        test('handles non-existent vehicle gracefully', function () {
            expect($this->availabilityService->isVehicleAvailable(
                999999, // Non-existent ID
                now()->addHour(),
                now()->addHours(2)
            ))->toBeTrue();
        });

        test('handles invalid time ranges', function () {
            $resources = createCompanyWithResources();

            expect($this->availabilityService->isDriverAvailable(
                $resources['driver']->id,
                now()->addHours(2), // End before start
                now()->addHour()
            ))->toBeTrue();
        });
    });
});
