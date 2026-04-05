<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name'        => fake()->name(),
            'email'       => fake()->unique()->safeEmail(),
            'password'    => static::$password ??= Hash::make('password'),
            'role'        => 'owner',
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => 'owner']);
    }

    public function staff(): static
    {
        return $this->state(['role' => 'staff']);
    }

    public function client(): static
    {
        return $this->state([
            'role'                   => 'client',
            'invitation_accepted_at' => now(),
        ]);
    }
}
