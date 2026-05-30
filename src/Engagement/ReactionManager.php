<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Emoji\EmojiResolver;
use Kurt\Modules\Interactions\Engagement\Exceptions\InvalidReaction;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;

/**
 * Write path for emoji reactions. Reacting with the same emoji twice is
 * idempotent; a user may hold multiple distinct emoji on one target (bounded by
 * reactions.max_per_user when set).
 */
final class ReactionManager
{
    public function __construct(private readonly EmojiResolver $emoji) {}

    public function react(Model $user, Model $reactable, string $emoji): Reaction
    {
        $emoji = trim($emoji);
        $this->emoji->validate($emoji);
        $this->enforceMax($user, $reactable, $emoji);

        return Reaction::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'reactable_type' => $reactable->getMorphClass(),
                'reactable_id' => $reactable->getKey(),
                'emoji' => $emoji,
            ],
            ['custom_emoji_id' => $this->emoji->customEmojiId($emoji)],
        );
    }

    public function unreact(Model $user, Model $reactable, string $emoji): bool
    {
        return $this->query($user, $reactable, trim($emoji))->delete() > 0;
    }

    public function toggle(Model $user, Model $reactable, string $emoji): bool
    {
        if ($this->has($user, $reactable, $emoji)) {
            $this->unreact($user, $reactable, $emoji);

            return false;
        }

        $this->react($user, $reactable, $emoji);

        return true;
    }

    public function has(Model $user, Model $reactable, string $emoji): bool
    {
        return $this->query($user, $reactable, trim($emoji))->exists();
    }

    /**
     * @return array<string, int>
     */
    public function summary(Model $reactable): array
    {
        $counts = Reaction::query()
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->groupBy('emoji')
            ->selectRaw('emoji, COUNT(*) as aggregate')
            ->pluck('aggregate', 'emoji');

        $summary = [];

        foreach ($counts as $emoji => $count) {
            $summary[(string) $emoji] = (int) $count;
        }

        return $summary;
    }

    private function enforceMax(Model $user, Model $reactable, string $emoji): void
    {
        $max = config('interactions.reactions.max_per_user');

        if (! is_int($max) || $max <= 0 || $this->has($user, $reactable, $emoji)) {
            return;
        }

        $distinct = Reaction::query()
            ->where('user_id', $user->getKey())
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->distinct()
            ->count('emoji');

        if ($distinct >= $max) {
            throw InvalidReaction::maxReached($max);
        }
    }

    /**
     * @return Builder<Reaction>
     */
    private function query(Model $user, Model $reactable, string $emoji): Builder
    {
        return Reaction::query()
            ->where('user_id', $user->getKey())
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->where('emoji', $emoji);
    }
}
