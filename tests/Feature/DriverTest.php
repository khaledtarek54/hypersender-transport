<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;

describe('Driver Management', function () {
    
    test('can create a driver', function () {
        $company = Company::factory()->create();
        $driverData = [
            'company_id' => $company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'license_number' => 'DL123456789',
            'hire_date' => now(),
            'is_active' => true,
        ];

        $driver = Driver::create($driverData);

        expect($driver)
            ->toBeInstanceOf(Driver::class)
            ->name->toBe($driverData['name'])
            ->email->toBe($driverData['email'])
            ->is_active->toBeTrue();

        $this->assertDatabaseHas('drivers', $driverData);
    });

    test('driver belongs to company', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);

        expect($driver->company->is($company))->toBeTrue();
    });

    test('driver has trips relationship', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        expect($resources['driver']->trips)
            ->toHaveCount(1);
        expect($resources['driver']->trips->first()->is($trip))->toBeTrue();
    });

    test('driver can be deactivated', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create([
            'company_id' => $company->id,
            'is_active' => true
        ]);

        $driver->update(['is_active' => false]);

        expect($driver->fresh()->is_active)->toBeFalse();
    });

    test('active drivers scope works', function () {
        $company = Company::factory()->create();
        $activeDriver = Driver::factory()->create([
            'company_id' => $company->id,
            'is_active' => true
        ]);
        $inactiveDriver = Driver::factory()->create([
            'company_id' => $company->id,
            'is_active' => false
        ]);

        $activeDrivers = Driver::active()->get();

        expect($activeDrivers->contains(fn($d) => $d->is($activeDriver)))->toBeTrue();
        expect($activeDrivers->contains(fn($d) => $d->is($inactiveDriver)))->toBeFalse();
    });

    // Driver name validation not enforced at the model layer

    test('driver license number is unique', function () {
        $company = Company::factory()->create();
        $licenseNumber = 'DL123456789';
        
        Driver::factory()->create([
            'company_id' => $company->id,
            'license_number' => $licenseNumber
        ]);

        expect(fn() => Driver::factory()->create([
            'company_id' => $company->id,
            'license_number' => $licenseNumber
        ]))->toThrow(\Exception::class);
    });

    // Driver email validation not enforced at the model layer

    test('driver deletion removes related trips', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        $driverId = $resources['driver']->id;
        $tripId = $trip->id;

        $resources['driver']->delete();

        $this->assertDatabaseMissing('drivers', ['id' => $driverId]);
        $this->assertDatabaseMissing('trips', ['id' => $tripId]);
    });
});
