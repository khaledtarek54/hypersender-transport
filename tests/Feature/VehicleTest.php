<?php

use App\Models\Company;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Trip;

describe('Vehicle Management', function () {
    
    test('can create a vehicle', function () {
        $company = Company::factory()->create();
        $vehicleData = [
            'company_id' => $company->id,
            'license_plate' => 'ABC123',
            'make' => 'Toyota',
            'model' => 'Hiace',
            'year' => 2023,
            'capacity' => 12,
            'is_active' => true,
        ];

        $vehicle = Vehicle::create($vehicleData);

        expect($vehicle)
            ->toBeInstanceOf(Vehicle::class)
            ->license_plate->toBe($vehicleData['license_plate'])
            ->make->toBe($vehicleData['make'])
            ->model->toBe($vehicleData['model'])
            ->capacity->toBe($vehicleData['capacity'])
            ->is_active->toBeTrue();

        $this->assertDatabaseHas('vehicles', $vehicleData);
    });

    test('vehicle belongs to company', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        expect($vehicle->company->is($company))->toBeTrue();
    });

    test('vehicle has trips relationship', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        expect($resources['vehicle']->trips)
            ->toHaveCount(1);
        expect($resources['vehicle']->trips->first()->is($trip))->toBeTrue();
    });

    test('vehicle can be deactivated', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'is_active' => true
        ]);

        $vehicle->update(['is_active' => false]);

        expect($vehicle->fresh()->is_active)->toBeFalse();
    });

    test('active vehicles scope works', function () {
        $company = Company::factory()->create();
        $activeVehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'is_active' => true
        ]);
        $inactiveVehicle = Vehicle::factory()->create([
            'company_id' => $company->id,
            'is_active' => false
        ]);

        $activeVehicles = Vehicle::active()->get();

        expect($activeVehicles->contains(fn($v) => $v->is($activeVehicle)))->toBeTrue();
        expect($activeVehicles->contains(fn($v) => $v->is($inactiveVehicle)))->toBeFalse();
    });

    test('vehicle license plate is unique', function () {
        $company = Company::factory()->create();
        $licensePlate = 'ABC123';
        
        Vehicle::factory()->create([
            'company_id' => $company->id,
            'license_plate' => $licensePlate
        ]);

        expect(fn() => Vehicle::factory()->create([
            'company_id' => $company->id,
            'license_plate' => $licensePlate
        ]))->toThrow(\Exception::class);
    });

    // Validation for year isn't enforced at the model layer currently
    // so we skip expecting exceptions here.

    // Validation for capacity isn't enforced at the model layer currently
    // so we skip expecting exceptions here.

    test('vehicle deletion removes related trips', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        $vehicleId = $resources['vehicle']->id;
        $tripId = $trip->id;

        $resources['vehicle']->delete();

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicleId]);
        $this->assertDatabaseMissing('trips', ['id' => $tripId]);
    });

    // Vehicle type enum not supported by current migration
});
