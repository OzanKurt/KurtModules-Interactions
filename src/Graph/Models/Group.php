<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * A named, owner-scoped friend group (e.g. "Close Friends", "Work").
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Group extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_groups';

    /** @var list<string> */
    protected $fillable = ['user_id', 'name', 'slug'];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->userBelongsTo('user_id');
    }

    /**
     * @return HasMany<GroupMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * @return list<int>
     */
    public function memberIds(): array
    {
        $ids = $this->memberships()->pluck('member_id')->all();

        return array_values(array_map(static fn ($id): int => (int) $id, $ids));
    }
}
