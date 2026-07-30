<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Interactions\Engagement\CounterSync;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Exceptions\SelfInteractionException;
use Kurt\Modules\Interactions\Http\SubjectResolver;

/**
 * Engagement (likes, votes, favorites, subscriptions, follows) over a
 * polymorphic subject. Writes flow through the InteractionManager so the
 * denormalized counters stay consistent; count reads are public while the
 * per-user engagement state requires auth.
 */
final class EngagementController extends ApiController
{
    public function __construct(
        private readonly SubjectResolver $subjects,
        private readonly InteractionManager $interactions,
        private readonly ReactionManager $reactions,
        private readonly CounterSync $counters,
    ) {}

    /**
     * POST {type}/{id}/engagement/{kind} — toggle an engagement kind on/off.
     *
     * {kind} is an InteractionType value (like, dislike, vote, favorite,
     * subscribe, follow). Vote accepts an optional {value} of 1 (default) or -1.
     */
    public function toggle(Request $request, string $type, string $id, string $kind): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        $interactionType = InteractionType::tryFrom($kind);

        if ($interactionType === null) {
            return $this->fail("Unsupported engagement kind [{$kind}].", 422);
        }

        $value = $interactionType === InteractionType::Vote ? $this->voteValue($request) : null;
        $user = $this->actor($request);

        try {
            $active = DB::transaction(fn (): bool => $this->applyToggle($user, $subject, $interactionType, $value));
        } catch (SelfInteractionException $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->respond([
            'kind' => $interactionType->value,
            'active' => $active,
            'counts' => $this->tally($subject),
        ]);
    }

    /**
     * GET {type}/{id}/engagement — the acting user's state plus the counts.
     */
    public function show(Request $request, string $type, string $id): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        return $this->respond([
            'state' => $this->state($this->actor($request), $subject),
            'counts' => $this->tally($subject),
        ]);
    }

    /**
     * GET {type}/{id}/counts — the public engagement + reaction tallies.
     */
    public function counts(string $type, string $id): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        return $this->respond($this->tally($subject));
    }

    private function applyToggle(Model $user, Model $subject, InteractionType $type, ?int $value): bool
    {
        if ($this->interactions->has($user, $subject, $type)) {
            $this->interactions->remove($user, $subject, $type);

            return false;
        }

        // Keep like/dislike mutually exclusive, mirroring the Interactor verbs so
        // the two counters never both hold the same user.
        $opposite = match ($type) {
            InteractionType::Like => InteractionType::Dislike,
            InteractionType::Dislike => InteractionType::Like,
            default => null,
        };

        if ($opposite !== null) {
            $this->interactions->remove($user, $subject, $opposite);
        }

        $this->interactions->add($user, $subject, $type, $value);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function state(Model $user, Model $subject): array
    {
        $state = [];

        foreach (InteractionType::cases() as $type) {
            $state[$type->value] = $this->interactions->has($user, $subject, $type);
        }

        $state['vote_value'] = $this->voteValueFor($user, $subject);
        $state['rating'] = $this->interactions->ratingBy($user, $subject);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    private function tally(Model $subject): array
    {
        return [
            'likes' => $this->counters->get($subject, InteractionType::Like),
            'dislikes' => $this->counters->get($subject, InteractionType::Dislike),
            'favorites' => $this->counters->get($subject, InteractionType::Favorite),
            'subscribers' => $this->counters->get($subject, InteractionType::Subscribe),
            'followers' => $this->counters->get($subject, InteractionType::Follow),
            'votes' => [
                'count' => $this->counters->get($subject, InteractionType::Vote),
                'score' => (int) $this->subjectInteractions($subject, InteractionType::Vote)->sum('value'),
            ],
            'ratings' => [
                'count' => $this->subjectRatings($subject)->count(),
                'average' => $this->averageRating($subject),
            ],
            'reactions' => $this->reactions->summary($subject),
        ];
    }

    private function averageRating(Model $subject): ?float
    {
        $average = $this->subjectRatings($subject)->avg('score');

        return $average === null ? null : (float) $average;
    }

    private function voteValueFor(Model $user, Model $subject): ?int
    {
        $value = $this->subjectInteractions($subject, InteractionType::Vote)
            ->where('user_id', $user->getKey())
            ->value('value');

        return $value === null ? null : (int) $value;
    }

    /**
     * @return Builder<Interaction>
     */
    private function subjectInteractions(Model $subject, InteractionType $type)
    {
        return Interaction::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type->value);
    }

    /**
     * @return Builder<Rating>
     */
    private function subjectRatings(Model $subject)
    {
        return Rating::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    private function voteValue(Request $request): int
    {
        /** @var array{value?: int} $data */
        $data = $request->validate(['value' => ['sometimes', 'integer', 'in:-1,1']]);

        return (int) ($data['value'] ?? 1);
    }

    private function actor(Request $request): Model
    {
        /** @var Model $user */
        $user = $request->user();

        return $user;
    }
}
