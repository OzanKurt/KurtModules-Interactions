<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Comments\Models\Comment;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Graph\Concerns\HasGraph;

/**
 * The actor surface: the verbs a user performs on any target model — engagement,
 * reactions, comments, and (via HasGraph) the social graph.
 *
 * @mixin Model
 */
trait Interactor
{
    use HasGraph;

    protected function interactionManager(): InteractionManager
    {
        return app(InteractionManager::class);
    }

    protected function reactionManager(): ReactionManager
    {
        return app(ReactionManager::class);
    }

    public function reactWith(Model $subject, string $emoji): Reaction
    {
        return $this->reactionManager()->react($this, $subject, $emoji);
    }

    public function unreact(Model $subject, string $emoji): bool
    {
        return $this->reactionManager()->unreact($this, $subject, $emoji);
    }

    public function toggleReaction(Model $subject, string $emoji): bool
    {
        return $this->reactionManager()->toggle($this, $subject, $emoji);
    }

    public function hasReactedWith(Model $subject, string $emoji): bool
    {
        return $this->reactionManager()->has($this, $subject, $emoji);
    }

    public function comment(Model $subject, string $body, ?Comment $parent = null): Comment
    {
        return app(CommentManager::class)->create($this, $subject, $body, $parent);
    }

    public function follow(Model $subject): Interaction
    {
        return $this->interactionManager()->add($this, $subject, InteractionType::Follow);
    }

    public function unfollow(Model $subject): bool
    {
        return $this->interactionManager()->remove($this, $subject, InteractionType::Follow);
    }

    public function isFollowing(Model $subject): bool
    {
        return $this->interactionManager()->has($this, $subject, InteractionType::Follow);
    }

    public function like(Model $subject): Interaction
    {
        return DB::transaction(function () use ($subject): Interaction {
            $this->interactionManager()->remove($this, $subject, InteractionType::Dislike);

            return $this->interactionManager()->add($this, $subject, InteractionType::Like);
        });
    }

    public function dislike(Model $subject): Interaction
    {
        return DB::transaction(function () use ($subject): Interaction {
            $this->interactionManager()->remove($this, $subject, InteractionType::Like);

            return $this->interactionManager()->add($this, $subject, InteractionType::Dislike);
        });
    }

    public function unlike(Model $subject): bool
    {
        return $this->interactionManager()->remove($this, $subject, InteractionType::Like);
    }

    public function hasLiked(Model $subject): bool
    {
        return $this->interactionManager()->has($this, $subject, InteractionType::Like);
    }

    public function upvote(Model $subject): Interaction
    {
        return $this->interactionManager()->add($this, $subject, InteractionType::Vote, 1);
    }

    public function downvote(Model $subject): Interaction
    {
        return $this->interactionManager()->add($this, $subject, InteractionType::Vote, -1);
    }

    public function cancelVote(Model $subject): bool
    {
        return $this->interactionManager()->remove($this, $subject, InteractionType::Vote);
    }

    public function hasVoted(Model $subject): bool
    {
        return $this->interactionManager()->has($this, $subject, InteractionType::Vote);
    }

    public function favorite(Model $subject): Interaction
    {
        return $this->interactionManager()->add($this, $subject, InteractionType::Favorite);
    }

    public function unfavorite(Model $subject): bool
    {
        return $this->interactionManager()->remove($this, $subject, InteractionType::Favorite);
    }

    public function hasFavorited(Model $subject): bool
    {
        return $this->interactionManager()->has($this, $subject, InteractionType::Favorite);
    }

    public function subscribe(Model $subject): Interaction
    {
        return $this->interactionManager()->add($this, $subject, InteractionType::Subscribe);
    }

    public function unsubscribe(Model $subject): bool
    {
        return $this->interactionManager()->remove($this, $subject, InteractionType::Subscribe);
    }

    public function hasSubscribed(Model $subject): bool
    {
        return $this->interactionManager()->has($this, $subject, InteractionType::Subscribe);
    }

    public function rate(Model $subject, int $score): Rating
    {
        return $this->interactionManager()->rate($this, $subject, $score);
    }

    public function unrate(Model $subject): bool
    {
        return $this->interactionManager()->removeRating($this, $subject);
    }

    public function ratingForMe(Model $subject): ?int
    {
        return $this->interactionManager()->ratingBy($this, $subject);
    }
}
