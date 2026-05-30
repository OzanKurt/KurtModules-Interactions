<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Emoji;

use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;
use Kurt\Modules\Interactions\Engagement\Exceptions\InvalidReaction;

/**
 * Validates and resolves a reaction token. A token is either a raw unicode
 * emoji ("🎉") or a custom emoji shortcode (":party:"). Custom shortcodes must
 * match an active row in the emoji registry.
 */
final class EmojiResolver
{
    public function isCustomShortcode(string $emoji): bool
    {
        return strlen($emoji) > 2
            && str_starts_with($emoji, ':')
            && str_ends_with($emoji, ':');
    }

    public function validate(string $emoji): void
    {
        $emoji = trim($emoji);

        if ($emoji === '') {
            throw InvalidReaction::empty();
        }

        if ($this->isCustomShortcode($emoji)) {
            if (! (bool) config('interactions.reactions.allow_custom', true)) {
                throw InvalidReaction::customDisabled();
            }

            if (! CustomEmoji::query()->active()->where('shortcode', $this->shortcode($emoji))->exists()) {
                throw InvalidReaction::unknownCustom($emoji);
            }

            return;
        }

        if (! (bool) config('interactions.reactions.allow_unicode', true)) {
            throw InvalidReaction::unicodeDisabled();
        }
    }

    public function customEmojiId(string $emoji): ?int
    {
        if (! $this->isCustomShortcode($emoji)) {
            return null;
        }

        $id = CustomEmoji::query()->where('shortcode', $this->shortcode($emoji))->value('id');

        return $id === null ? null : (int) $id;
    }

    private function shortcode(string $emoji): string
    {
        return trim($emoji, ':');
    }
}
