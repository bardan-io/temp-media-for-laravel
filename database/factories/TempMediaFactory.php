<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Database\Factories;

use BardanIO\TempMedia\Models\TempMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\BardanIO\TempMedia\Models\TempMedia>
 */
final class TempMediaFactory extends Factory
{
    protected $model = TempMedia::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'session_id' => $this->faker->uuid(),
            'user_id' => null,
            'original_name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(100000, 5000000),
            'expires_at' => Carbon::now()->addHours(24),
            'is_processed' => false,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subHours(1),
        ]);
    }

    public function processed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_processed' => true,
        ]);
    }

    public function withUser(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function withSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
        ]);
    }

    public function png(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->word().'.png',
            'mime_type' => 'image/png',
        ]);
    }

    public function webp(): static
    {
        return $this->state(fn (array $attributes) => [
            'original_name' => $this->faker->word().'.webp',
            'mime_type' => 'image/webp',
        ]);
    }
}
