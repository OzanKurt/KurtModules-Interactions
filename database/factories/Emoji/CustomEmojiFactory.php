<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Interactions\Emoji;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;

/**
 * @extends Factory<CustomEmoji>
 */
class CustomEmojiFactory extends Factory
{
    /** @var class-string<CustomEmoji> */
    protected $model = CustomEmoji::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shortcode = Str::lower($this->faker->unique()->word()).Str::lower(Str::random(4));

        return [
            'shortcode' => $shortcode,
            'name' => Str::headline($shortcode),
            'url' => 'https://cdn.example.test/emoji/'.$shortcode.'.png',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
