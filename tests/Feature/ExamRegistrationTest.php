<?php

use App\Livewire\Examinee\ExamRegistration as ExamRegistrationComponent;
use App\Models\BorderArea;
use App\Models\Branch;
use App\Models\ExamRegistration;
use App\Models\ExamSession;
use App\Models\TestLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('test_examinee_can_register_for_exam', function () {
    $user = User::factory()->examinee()->create();

    $session = ExamSession::create([
        'year' => 2569,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDay(),
        'registration_end' => Carbon::today()->addDay(),
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $branch = Branch::create(['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true]);
    $location = TestLocation::create([
        'name' => 'ศูนย์สอบกรุงเทพ',
        'code' => '1',
        'address' => 'กรุงเทพ',
        'capacity' => 500,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(ExamRegistrationComponent::class)
        ->set('position', 'ผบ.หมู่')
        ->set('branch_id', (string) $branch->id)
        ->set('age', '30')
        ->set('eligible_year', '2566')
        ->set('suspended_years', '0')
        ->set('test_location_id', (string) $location->id)
        ->set('exam_level', ExamSession::LEVEL_SERGEANT_MAJOR)
        ->call('register')
        ->assertHasNoErrors()
        ->assertSet('registrationSuccess', true);

    $exists = DB::table('exam_registrations')
        ->where('exam_session_id', $session->id)
        ->where('test_location_id', $location->id)
        ->where('status', ExamRegistration::STATUS_PENDING)
        ->exists();
    expect($exists)->toBeTrue();
});

test('test_cannot_register_outside_period', function () {
    $user = User::factory()->examinee()->create();

    ExamSession::create([
        'year' => 2568,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDays(10),
        'registration_end' => Carbon::today()->subDays(5), // ปิดรับสมัครแล้ว
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    $this->actingAs($user)
        ->get('/examinee/register-exam')
        ->assertRedirect(route('examinee.dashboard'))
        ->assertSessionHas('error');
});

test('test_scores_calculated_correctly', function () {
    $user = User::factory()->examinee()->create();

    ExamSession::create([
        'year' => 2569,
        'exam_level' => ExamSession::LEVEL_SERGEANT_MAJOR,
        'registration_start' => Carbon::today()->subDay(),
        'registration_end' => Carbon::today()->addDay(),
        'exam_date' => Carbon::today()->addDays(10),
        'is_active' => true,
        'is_archived' => false,
    ]);

    Branch::create(['name' => 'ทหารราบ', 'code' => '1', 'is_active' => true]);
    TestLocation::create([
        'name' => 'ศูนย์สอบกรุงเทพ',
        'code' => '1',
        'address' => 'กรุงเทพ',
        'capacity' => 500,
        'is_active' => true,
    ]);

    $borderArea = BorderArea::create([
        'code' => 'BA01',
        'name' => 'นราธิวาส',
        'special_score' => 2.5,
        'is_active' => true,
    ]);

    $currentBuddhistYear = (int) date('Y') + 543;

    $this->actingAs($user);

    $component = Livewire::test(ExamRegistrationComponent::class)
        ->set('eligible_year', (string) ($currentBuddhistYear - 5))
        ->set('suspended_years', '1')
        ->set('border_area_id', (string) $borderArea->id);

    expect((float) $component->get('calculatedPendingScore'))->toBe(4.0);
    expect((float) $component->get('calculatedSpecialScore'))->toBe(2.5);
    expect((float) $component->get('calculatedTotalScore'))->toBe(6.5);
});
