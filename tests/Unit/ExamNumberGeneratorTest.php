<?php

use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Models\User;
use App\Services\ExamNumberGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('test_number_format', function () {
    $session = ExamSession::create([
        'year' => 2572,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDay(),
        'registration_end' => Carbon::today()->addDay(),
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch = Branch::create(['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true]);
    $location = TestLocation::create([
        'name' => 'กรุงเทพ',
        'code' => '2',
        'address' => '-',
        'capacity' => 200,
        'is_active' => true,
    ]);

    foreach (['Alpha', 'Beta'] as $firstName) {
        $user = User::factory()->examinee()->create([
            'first_name' => $firstName,
            'last_name' => 'Format',
        ]);

        $examinee = Examinee::create([
            'user_id' => $user->id,
            'position' => 'ผบ.หมู่',
            'branch_id' => $branch->id,
            'age' => 30,
            'eligible_year' => 2566,
            'suspended_years' => 0,
            'pending_score' => 3,
            'special_score' => 0,
            'border_area_id' => null,
        ]);

        ExamRegistration::create([
            'examinee_id' => $examinee->id,
            'exam_session_id' => $session->id,
            'test_location_id' => $location->id,
            'status' => ExamRegistration::STATUS_PENDING,
            'registered_at' => now(),
        ]);
    }

    app(ExamNumberGenerator::class)->generate($session->id);

    $numbers = ExamRegistration::query()
        ->where('exam_session_id', $session->id)
        ->pluck('exam_number');

    foreach ($numbers as $number) {
        expect((bool) preg_match('/^[1-9][1-9][0-9]{3}$/', (string) $number))->toBeTrue();
    }
});

test('test_number_uniqueness', function () {
    $session = ExamSession::create([
        'year' => 2573,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDay(),
        'registration_end' => Carbon::today()->addDay(),
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch = Branch::create(['name' => 'ทหารม้า', 'code' => '3', 'is_active' => true]);
    $location = TestLocation::create([
        'name' => 'เชียงใหม่',
        'code' => '4',
        'address' => '-',
        'capacity' => 200,
        'is_active' => true,
    ]);

    foreach (['A', 'B', 'C', 'D'] as $firstName) {
        $user = User::factory()->examinee()->create([
            'first_name' => $firstName,
            'last_name' => 'Unique',
        ]);

        $examinee = Examinee::create([
            'user_id' => $user->id,
            'position' => 'ผบ.หมู่',
            'branch_id' => $branch->id,
            'age' => 30,
            'eligible_year' => 2566,
            'suspended_years' => 0,
            'pending_score' => 3,
            'special_score' => 0,
            'border_area_id' => null,
        ]);

        ExamRegistration::create([
            'examinee_id' => $examinee->id,
            'exam_session_id' => $session->id,
            'test_location_id' => $location->id,
            'status' => ExamRegistration::STATUS_PENDING,
            'registered_at' => now(),
        ]);
    }

    app(ExamNumberGenerator::class)->generate($session->id);

    $numbers = ExamRegistration::query()
        ->where('exam_session_id', $session->id)
        ->pluck('exam_number')
        ->filter()
        ->values();

    expect($numbers->count())->toBe(4);
    expect($numbers->unique()->count())->toBe(4);
});

test('test_numbers_sorted_by_name', function () {
    $session = ExamSession::create([
        'year' => 2574,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDay(),
        'registration_end' => Carbon::today()->addDay(),
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch = Branch::create(['name' => 'ทหารปืนใหญ่', 'code' => '5', 'is_active' => true]);
    $location = TestLocation::create([
        'name' => 'ขอนแก่น',
        'code' => '6',
        'address' => '-',
        'capacity' => 200,
        'is_active' => true,
    ]);

    // Intentionally create unsorted names to verify sorting by first_name ASC.
    foreach (['Charlie', 'Alice', 'Bob'] as $firstName) {
        $user = User::factory()->examinee()->create([
            'first_name' => $firstName,
            'last_name' => 'Sort',
        ]);

        $examinee = Examinee::create([
            'user_id' => $user->id,
            'position' => 'ผบ.หมู่',
            'branch_id' => $branch->id,
            'age' => 30,
            'eligible_year' => 2566,
            'suspended_years' => 0,
            'pending_score' => 3,
            'special_score' => 0,
            'border_area_id' => null,
        ]);

        ExamRegistration::create([
            'examinee_id' => $examinee->id,
            'exam_session_id' => $session->id,
            'test_location_id' => $location->id,
            'status' => ExamRegistration::STATUS_PENDING,
            'registered_at' => now(),
        ]);
    }

    app(ExamNumberGenerator::class)->generate($session->id);

    $sequenceByName = ExamRegistration::query()
        ->where('exam_session_id', $session->id)
        ->with('examinee.user:id,first_name')
        ->get()
        ->mapWithKeys(function (ExamRegistration $registration) {
            $name = (string) optional(optional($registration->examinee)->user)->first_name;
            $sequence = (int) substr((string) $registration->exam_number, -3);

            return [$name => $sequence];
        });

    expect($sequenceByName->get('Alice'))->toBe(1);
    expect($sequenceByName->get('Bob'))->toBe(2);
    expect($sequenceByName->get('Charlie'))->toBe(3);
});
