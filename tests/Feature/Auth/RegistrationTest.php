<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register as RegisterComponent;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeLivewire(RegisterComponent::class);
});

test('new users can register', function () {
    Role::findOrCreate('examinee', 'web');

    $component = Livewire::test(RegisterComponent::class)
        ->set('national_id', '1234567890123')
        ->set('rank', 'ส.อ.')
        ->set('first_name', 'Test')
        ->set('last_name', 'User')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
