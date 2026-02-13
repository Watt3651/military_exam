<?php

use App\Livewire\Staff\BorderAreas\Create as BorderAreaCreateComponent;
use App\Livewire\Staff\BorderAreas\Edit as BorderAreaEditComponent;
use App\Models\BorderArea;
use App\Models\BorderAreaScoreHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('test_staff_can_create_border_area', function () {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    Livewire::test(BorderAreaCreateComponent::class)
        ->set('code', 'BA99')
        ->set('name', 'พื้นที่ทดสอบ')
        ->set('special_score', '4.50')
        ->set('description', 'สร้างจาก feature test')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $exists = DB::table('border_areas')
        ->where('code', 'BA99')
        ->where('name', 'พื้นที่ทดสอบ')
        ->exists();
    expect($exists)->toBeTrue();
});

test('test_score_change_logged', function () {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    $borderArea = BorderArea::create([
        'code' => 'BA10',
        'name' => 'พื้นที่เดิม',
        'special_score' => 3.00,
        'description' => null,
        'is_active' => true,
        'created_by' => $staff->id,
        'updated_by' => $staff->id,
    ]);

    Livewire::test(BorderAreaEditComponent::class, ['id' => $borderArea->id])
        ->set('special_score', '6.50')
        ->set('reason', 'ปรับเกณฑ์คะแนนประจำปี')
        ->call('save')
        ->assertHasNoErrors();

    $history = BorderAreaScoreHistory::query()
        ->where('border_area_id', $borderArea->id)
        ->latest('id')
        ->first();

    expect($history)->not->toBeNull();
    expect((float) $history->old_score)->toBe(3.0);
    expect((float) $history->new_score)->toBe(6.5);
    expect($history->reason)->toBe('ปรับเกณฑ์คะแนนประจำปี');
});

test('test_examinee_cannot_modify_border_area', function () {
    $examinee = User::factory()->examinee()->create();

    $this->actingAs($examinee)
        ->get('/staff/border-areas/create')
        ->assertStatus(403);
});
