<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Comments\Enums\CommentStatus;
use Kurt\Modules\Interactions\Comments\Models\Comment;
use Kurt\Modules\Interactions\Comments\Models\CommentRevision;
use Kurt\Modules\Interactions\Events\Commented;
use Kurt\Modules\Interactions\Events\CommentReplied;
use Kurt\Modules\Interactions\Mentions\MentionParser;

/**
 * Write path for comments: create (resolving mentions), edit (snapshotting the
 * previous body to a revision), moderate, and soft-delete.
 */
final class CommentManager
{
    public function __construct(private readonly MentionParser $mentions) {}

    public function create(Model $author, Model $commentable, string $body, ?Comment $parent = null): Comment
    {
        $comment = new Comment;
        $comment->fill([
            'user_id' => $author->getKey(),
            'commentable_type' => $commentable->getMorphClass(),
            'commentable_id' => $commentable->getKey(),
            'parent_id' => $parent?->getKey(),
            'body' => $body,
            'status' => (string) config('interactions.comments.default_status', 'published'),
        ]);
        $comment->save();

        $this->mentions->syncFor($comment, $body);

        event(new Commented($comment));

        if ($parent !== null) {
            event(new CommentReplied($comment, $parent));
        }

        return $comment;
    }

    public function edit(Comment $comment, string $body, ?Model $editor = null): Comment
    {
        if ((bool) config('interactions.comments.revisions', true)) {
            CommentRevision::query()->create([
                'comment_id' => $comment->getKey(),
                'body' => $comment->body,
                'edited_by' => $editor?->getKey(),
            ]);
        }

        $comment->update(['body' => $body, 'edited_at' => now()]);
        $this->mentions->syncFor($comment, $body);

        return $comment;
    }

    public function moderate(Comment $comment, CommentStatus $status, ?Model $moderator = null): Comment
    {
        $comment->update([
            'status' => $status->value,
            'moderated_by' => $moderator?->getKey(),
            'moderated_at' => now(),
        ]);

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
