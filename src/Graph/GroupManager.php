<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Kurt\Modules\Interactions\Graph\Models\Group;
use Kurt\Modules\Interactions\Graph\Models\GroupMember;

/**
 * Owner-scoped friend groups and their membership.
 */
final class GroupManager
{
    public function create(Model $owner, string $name): Group
    {
        return Group::query()->create([
            'user_id' => $owner->getKey(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
        ]);
    }

    public function addMember(Group $group, Model $member): GroupMember
    {
        return GroupMember::query()->firstOrCreate([
            'group_id' => $group->getKey(),
            'member_id' => $member->getKey(),
        ]);
    }

    public function removeMember(Group $group, Model $member): bool
    {
        return GroupMember::query()
            ->where('group_id', $group->getKey())
            ->where('member_id', $member->getKey())
            ->delete() > 0;
    }
}
