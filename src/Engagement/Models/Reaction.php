<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;

/**
 * One user's emoji reaction on one reactable. `emoji` is a unicode character
 * ("🎉") or a custom shortcode (":party:"); for custom emoji `custom_emoji_id`
 * links the registry row. Unique (user, reactable, emoji) → multiple distinct
 * emoji per user, one of each.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reactable_type
 * @property int $reactable_id
 * @property string $emoji
 * @property int|null $custom_emoji_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomEmoji|null $customEmoji
 */
class Reaction extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_reactions';

    /** @var list<string> */
    protected $fillable = ['user_id', 'reactable_type', 'reactable_id', 'emoji', 'custom_emoji_id'];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<CustomEmoji, $this>
     */
    public function customEmoji(): BelongsTo
    {
        return $this->belongsTo(CustomEmoji::class);
    }
}
