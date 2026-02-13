<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'national_id' => fake()->unique()->numerify('#############'), // 13 digits
            'rank' => fake()->randomElement(['พลฯ', 'ส.ท.', 'ส.อ.', 'จ.ส.ต.', 'จ.ส.ท.', 'จ.ส.อ.']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'examinee',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * User เป็น examinee
     */
    public function examinee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'examinee',
        ]);
    }

    /**
     * User เป็น staff
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'staff',
            'email' => $attributes['email'] ?? fake()->unique()->safeEmail(),
        ]);
    }

    /**
     * User เป็น commander
     */
    public function commander(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'commander',
            'email' => $attributes['email'] ?? fake()->unique()->safeEmail(),
        ]);
    }

    /**
     * User ที่ถูก deactivate
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Email ยังไม่ verified
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
