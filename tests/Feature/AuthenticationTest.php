<?php

use App\Livewire\Auth\Register as RegisterComponent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

test('test_examinee_can_register', function () {
    Role::findOrCreate('examinee', 'web');

    Livewire::test(RegisterComponent::class)
        ->set('national_id', '1234567890123')
        ->set('rank', 'ส.อ.')
        ->set('first_name', 'สมชาย')
        ->set('last_name', 'ใจดี')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $exists = DB::table('users')
        ->where('national_id', '1234567890123')
        ->where('role', 'examinee')
        ->exists();
    expect($exists)->toBeTrue();
});

test('test_examinee_can_login', function () {
    $user = User::factory()->examinee()->create([
        'national_id' => '1234567890999',
    ]);

    Volt::test('pages.auth.login')
        ->set('form.national_id', $user->national_id)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('test_staff_cannot_self_register', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get('/register')
        ->assertRedirect('/dashboard');
});
