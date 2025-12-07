<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Device Factory
 * 
 * Factory for creating test Device models.
 * Used in tests to quickly create device instances with realistic data.
 */
class DeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a random MAC address in standard format
        $macAddress = $this->generateMacAddress();

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true) . ' Device',
            'mac_address' => $macAddress,
            'status' => fake()->randomElement(['active', 'blocked', 'whitelisted']),
            'role' => fake()->randomElement(['child', 'guest', 'parent']),
            'remaining_time_minutes' => fake()->numberBetween(0, 120),
            'total_time_allocated' => fake()->numberBetween(0, 500),
            'last_seen_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Generate a random MAC address in standard format (XX:XX:XX:XX:XX:XX).
     *
     * @return string
     */
    private function generateMacAddress(): string
    {
        $mac = [];
        for ($i = 0; $i < 6; $i++) {
            $mac[] = str_pad(dechex(rand(0, 255)), 2, '0', STR_PAD_LEFT);
        }
        return strtoupper(implode(':', $mac));
    }

    /**
     * Indicate that the device is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the device is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
        ]);
    }

    /**
     * Indicate that the device is whitelisted.
     */
    public function whitelisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'whitelisted',
        ]);
    }

    /**
     * Indicate that the device has a specific role.
     */
    public function role(string $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    /**
     * Indicate that the device has time remaining.
     */
    public function withTime(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'remaining_time_minutes' => $minutes,
            'total_time_allocated' => $minutes,
        ]);
    }
}

