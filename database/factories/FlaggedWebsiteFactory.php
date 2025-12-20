<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\FlaggedWebsite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Flagged Website Factory
 * 
 * Factory for creating test FlaggedWebsite models.
 * Used in tests to quickly create flagged website instances with realistic data.
 */
class FlaggedWebsiteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FlaggedWebsite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->domainName();
        $url = 'https://' . $domain . '/' . fake()->slug();

        return [
            'device_id' => Device::factory(),
            'url' => $url,
            'domain' => $domain,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}

