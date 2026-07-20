<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kurt\Modules\Core\Http\Controllers\ApiController;
use Kurt\Modules\Interactions\Engagement\Exceptions\InvalidReaction;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Http\SubjectResolver;

/**
 * Emoji reactions over a polymorphic subject. Writes flow through the
 * ReactionManager so the denormalized per-emoji summary stays consistent; the
 * summary read is public.
 */
final class ReactionController extends ApiController
{
    public function __construct(
        private readonly SubjectResolver $subjects,
        private readonly ReactionManager $reactions,
    ) {}

    /**
     * POST {type}/{id}/reactions — react with an emoji.
     */
    public function store(Request $request, string $type, string $id): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        /** @var array{emoji: string} $data */
        $data = $request->validate(['emoji' => ['required', 'string']]);

        try {
            $this->reactions->react($this->actor($request), $subject, $data['emoji']);
        } catch (InvalidReaction $e) {
            return $this->fail($e->getMessage(), 422);
        }

        return $this->respondCreated(['summary' => $this->reactions->summary($subject)]);
    }

    /**
     * DELETE {type}/{id}/reactions — remove one of the actor's emoji reactions.
     */
    public function destroy(Request $request, string $type, string $id): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        /** @var array{emoji: string} $data */
        $data = $request->validate(['emoji' => ['required', 'string']]);

        $removed = $this->reactions->unreact($this->actor($request), $subject, $data['emoji']);

        return $this->respond([
            'removed' => $removed,
            'summary' => $this->reactions->summary($subject),
        ]);
    }

    /**
     * GET {type}/{id}/reactions/summary — the denormalized per-emoji tally.
     */
    public function summary(string $type, string $id): JsonResponse
    {
        $subject = $this->subjects->resolve($type, $id);

        return $this->respond(['summary' => $this->reactions->summary($subject)]);
    }

    private function actor(Request $request): Model
    {
        /** @var Model $user */
        $user = $request->user();

        return $user;
    }
}
