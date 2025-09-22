<?php

use App\Rules\NoOverlappingTrips;

describe('NoOverlappingTrips Rule', function () {

    test('fails when new trip overlaps existing by start', function () {
        $resources = createCompanyWithResources();

        // Existing trip: 10:00 - 12:00
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(12, 0));

        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(11, 0), // overlaps start-end
            today()->setTime(13, 0)
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeTrue();
    });

    test('fails when new trip overlaps existing by end', function () {
        $resources = createCompanyWithResources();

        // Existing trip: 10:00 - 12:00
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(12, 0));

        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(9, 0),
            today()->setTime(11, 0) // overlaps
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeTrue();
    });

    test('fails when new trip is fully enclosed by existing', function () {
        $resources = createCompanyWithResources();

        // Existing trip: 10:00 - 14:00
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(14, 0));

        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(11, 0),
            today()->setTime(13, 0)
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeTrue();
    });

    test('fails when trips touch at boundary (inclusive overlap)', function () {
        $resources = createCompanyWithResources();

        // Existing trip: 10:00 - 12:00
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(12, 0));

        // New trip exactly after: 12:00 - 14:00 (considered overlapping by rule)
        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(12, 0),
            today()->setTime(14, 0)
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeTrue();
    });

    test('passes when excluding current trip during update', function () {
        $resources = createCompanyWithResources();

        // Existing trip to be updated
        $existing = createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(12, 0));

        // Same window but excluding its own id should pass
        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(10, 0),
            today()->setTime(12, 0),
            excludeTripId: $existing->id
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeFalse();
    });

    test('ignores completed and cancelled trips', function () {
        $resources = createCompanyWithResources();

        // Completed trip in the same window
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(10, 0), today()->setTime(12, 0), 'completed');

        // Cancelled trip in the same window
        createTrip($resources['company'], $resources['driver'], $resources['vehicle'], today()->setTime(13, 0), today()->setTime(14, 0), 'cancelled');

        // New trip overlapping those should still pass as rule only blocks scheduled/in_progress
        $rule = new NoOverlappingTrips(
            $resources['driver']->id,
            $resources['vehicle']->id,
            today()->setTime(11, 0),
            today()->setTime(13, 30)
        );

        $failed = false;
        $rule->validate('trip', null, function () use (&$failed) { $failed = true; });

        expect($failed)->toBeFalse();
    });
});


