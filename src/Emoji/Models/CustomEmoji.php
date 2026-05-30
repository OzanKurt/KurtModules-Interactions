<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Emoji\Models;

use Database\Factories\Kurt\Modules\Interactions\Emoji\CustomEmojiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A Discord-style custom emoji, referenced in reactions/comments as
 * `:shortcode:` and rendered from `url`.
 *
 * @property int $id
 * @property string $shortcode
 * @property string|null $name
 * @property string|null $url
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CustomEmoji extends Model
{
    /** @use HasFactory<CustomEmojiFactory> */
    use HasFactory;

    protected $table = 'interactions_emojis';

    /** @var list<string> */
    protected $fillable = ['shortcode', 'name', 'url', 'is_active'];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CustomEmojiFactory
    {
        return CustomEmojiFactory::new();
    }

    /**
     * @param  Builder<CustomEmoji>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
