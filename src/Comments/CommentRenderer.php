<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments;

use League\CommonMark\CommonMarkConverter;

/**
 * Renders a comment body to safe HTML. Markdown is converted with raw HTML
 * stripped and unsafe links disabled; when markdown is off the body is simply
 * escaped.
 */
final class CommentRenderer
{
    public function toHtml(string $body): string
    {
        if (! (bool) config('interactions.comments.markdown', true)) {
            return e($body);
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($body)->getContent();
    }
}
