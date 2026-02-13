<?php

use App\Services\ScoreCalculator;

test('test_calculate_pending_score', function () {
    $calculator = new ScoreCalculator();

    $pending = $calculator->calculatePendingScore(
        eligibleYear: 2565,
        suspendedYears: 1,
        currentYear: 2569,
    );

    expect($pending)->toBe(3.0);
});

test('test_pending_score_not_negative', function () {
    $calculator = new ScoreCalculator();

    $pending = $calculator->calculatePendingScore(
        eligibleYear: 2570,
        suspendedYears: 5,
        currentYear: 2569,
    );

    expect($pending)->toBe(0.0);
});

test('test_calculate_total_score', function () {
    $calculator = new ScoreCalculator();

    $total = $calculator->calculateTotalScore(4.0, 2.5);

    expect($total)->toBe(6.5);
});
