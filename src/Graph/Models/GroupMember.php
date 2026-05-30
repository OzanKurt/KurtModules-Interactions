<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * Pivot row placing one user into a friend group.
 *
 * @property int $id
 * @property int $group_id
 * @property int $member_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GroupMember extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_group_members';

    /** @var list<string> */
    protected $fillable = ['group_id', 'member_id'];

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function member(): BelongsTo
    {
        return $this->userBelongsTo('member_id');
    }
}
