<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;

/**
 * The actor surface: the verbs a user performs on any target model. Reaction,
 * comment and mention verbs are folded in by their phases.
 *
 * @mixin Model
 */
trait Interactor
{
    protected function interactionManager(): InteractionManager
    {
        return app(InteractionManager::class);
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
        $this->interactionManager()->remove($this, $subject, InteractionType::Dislike);

        return $this->interactionManager()->add($this, $subject, InteractionType::Like);
    }

    public function dislike(Model $subject): Interaction
    {
        $this->interactionManager()->remove($this, $subject, InteractionType::Like);

        return $this->interactionManager()->add($this, $subject, InteractionType::Dislike);
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
