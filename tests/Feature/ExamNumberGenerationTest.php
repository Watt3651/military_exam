<?php

use App\Models\Branch;
use App\Models\Examinee;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Models\User;
use App\Services\ExamNumberGenerator;
use Carbon\Carbon;

test('test_exam_numbers_generated_correctly', function () {
    $session = ExamSession::create([
        'year' => 2570,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDays(3),
        'registration_end' => Carbon::today()->addDays(3),
        'exam_date' => Carbon::today()->addDays(20),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch1 = Branch::create(['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true]);
    $branch2 = Branch::create(['name' => 'ทหารม้า', 'code' => '2', 'is_active' => true]);
    $loc1 = TestLocation::create(['name' => 'กรุงเทพ', 'code' => '1', 'address' => '-', 'capacity' => 200, 'is_active' => true]);
    $loc2 = TestLocation::create(['name' => 'เชียงใหม่', 'code' => '2', 'address' => '-', 'capacity' => 200, 'is_active' => true]);

    $cases = [
        ['Amy', $branch1->id, $loc1->id],
        ['Bob', $branch1->id, $loc1->id],
        ['Cindy', $branch2->id, $loc2->id],
        ['Dale', $branch2->id, $loc2->id],
    ];

    foreach ($cases as [$firstName, $branchId, $locationId]) {
        $user = User::factory()->examinee()->create([
            'first_name' => $firstName,
            'last_name' => 'Tester',
        ]);

        $examinee = Examinee::create([
            'user_id' => $user->id,
            'position' => 'ผบ.หมู่',
            'branch_id' => $branchId,
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
            'test_location_id' => $locationId,
            'status' => ExamRegistration::STATUS_PENDING,
            'registered_at' => now(),
        ]);
    }

    $count = app(ExamNumberGenerator::class)->generate($session->id);
    expect($count)->toBe(4);

    $rows = ExamRegistration::query()
        ->where('exam_session_id', $session->id)
        ->get();

    expect($rows->where('status', ExamRegistration::STATUS_CONFIRMED)->count())->toBe(4);

    $numbers = $rows->pluck('exam_number')->filter();
    expect($numbers->count())->toBe(4);
    expect($numbers->unique()->count())->toBe(4); // ไม่มีเลขซ้ำ

    foreach ($numbers as $num) {
        expect((bool) preg_match('/^[1-9][1-9][0-9]{3}$/', (string) $num))->toBeTrue();
    }
});

test('test_numbers_sorted_by_name', function () {
    $session = ExamSession::create([
        'year' => 2571,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDays(2),
        'registration_end' => Carbon::today()->addDays(2),
        'exam_date' => Carbon::today()->addDays(15),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch = Branch::create(['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true]);
    $location = TestLocation::create(['name' => 'กรุงเทพ', 'code' => '1', 'address' => '-', 'capacity' => 200, 'is_active' => true]);

    foreach (['Charlie', 'Alice', 'Bob'] as $firstName) {
        $user = User::factory()->examinee()->create([
            'first_name' => $firstName,
            'last_name' => 'Sorter',
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

    $orderedByExamNumber = ExamRegistration::query()
        ->where('exam_session_id', $session->id)
        ->with('examinee.user:id,first_name')
        ->orderBy('exam_number')
        ->get()
        ->map(fn (ExamRegistration $registration) => $registration->examinee?->user?->first_name)
        ->values()
        ->all();

    expect($orderedByExamNumber)->toBe(['Alice', 'Bob', 'Charlie']);
});
