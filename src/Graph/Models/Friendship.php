<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Interactions\Graph\Enums\FriendshipStatus;

/**
 * A directed friendship row from sender to recipient. Acceptance makes it a
 * mutual friendship; "between" lookups check both directions.
 *
 * @property int $id
 * @property int $sender_id
 * @property int $recipient_id
 * @property FriendshipStatus $status
 * @property Carbon|null $accepted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Friendship extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_friendships';

    /** @var list<string> */
    protected $fillable = ['sender_id', 'recipient_id', 'status', 'accepted_at'];

    /** @var array<string, string> */
    protected $casts = [
        'status' => FriendshipStatus::class,
        'accepted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->userBelongsTo('sender_id');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->userBelongsTo('recipient_id');
    }
}
