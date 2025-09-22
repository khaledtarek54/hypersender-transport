<?php

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Trip;

describe('Company Management', function () {
    
    test('can create a company', function () {
        $companyData = [
            'name' => 'Test Transport Company',
            'email' => 'test@company.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street, Test City',
        ];

        $company = Company::create($companyData);

        expect($company)
            ->toBeInstanceOf(Company::class)
            ->name->toBe($companyData['name'])
            ->email->toBe($companyData['email']);

        $this->assertDatabaseHas('companies', $companyData);
    });

    test('company has required attributes', function () {
        $company = Company::factory()->create();

        expect($company)
            ->toHaveKey('name')
            ->toHaveKey('email')
            ->toHaveKey('phone')
            ->toHaveKey('address');
    });

    test('company has drivers relationship', function () {
        $company = Company::factory()->create();
        $driver = Driver::factory()->create(['company_id' => $company->id]);

        expect($company->drivers)
            ->toHaveCount(1);
        expect($company->drivers->first()->is($driver))->toBeTrue();
    });

    test('company has vehicles relationship', function () {
        $company = Company::factory()->create();
        $vehicle = Vehicle::factory()->create(['company_id' => $company->id]);

        expect($company->vehicles)
            ->toHaveCount(1);
        expect($company->vehicles->first()->is($vehicle))->toBeTrue();
    });

    test('company has trips relationship', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        expect($resources['company']->trips)
            ->toHaveCount(1);
        expect($resources['company']->trips->first()->is($trip))->toBeTrue();
    });

    test('company cascade deletion removes related records', function () {
        $resources = createCompanyWithResources();
        $trip = createTrip($resources['company'], $resources['driver'], $resources['vehicle']);

        $companyId = $resources['company']->id;
        $driverId = $resources['driver']->id;
        $vehicleId = $resources['vehicle']->id;
        $tripId = $trip->id;

        $resources['company']->delete();

        $this->assertDatabaseMissing('companies', ['id' => $companyId]);
        $this->assertDatabaseMissing('drivers', ['id' => $driverId]);
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicleId]);
        $this->assertDatabaseMissing('trips', ['id' => $tripId]);
    });

    test('company name is required', function () {
        expect(fn() => Company::create([
            'email' => 'test@company.com',
            'phone' => '+1234567890',
            'address' => '123 Test Street',
        ]))->toThrow(\Exception::class);
    });

    // Company email validation isn't enforced at the model layer
});
