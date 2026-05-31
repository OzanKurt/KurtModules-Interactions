# Changelog

All notable changes to `ozankurt/laravel-modules-interactions` are documented here.

## v1.3.0

### Added

- Mention read receipts: a nullable `seen_at` column on `interactions_mentions`,
  a `Mention::markSeen()` helper, and a `scopeUnseen()` query scope. Lets
  consuming modules (e.g. chat) track which mentions a user has seen.

## v1.2.0

### Added

- Comment moderation audit: `moderated_by` + `moderated_at` columns on
  `interactions_comments`, a `Comment::moderatedBy()` relation, and an optional
  `$moderator` argument to `CommentManager::moderate()` that records who
  moderated and when. Lets consuming modules (e.g. Blog) keep an approver trail.

## v1.1.0

### Filament admin (v3 · v4 · v5)

- `CommentResource` — moderation: approve / mark-spam / delete, status filter;
  create disabled (comments are authored via the API/manager).
- `CustomEmojiResource` — full CRUD for custom reaction emoji.
- `FriendshipResource` — read-only graph overview, status filter.
- Version-dispatching `InteractionsPlugin::make()`; per-Filament-major PHPStan
  configs + guarded introspection smoke tests; CI analyses each version dir.

## v1.0.0

Initial release — polymorphic social & engagement toolkit (headless).

### Engagement

- Unified `interactions_interactions` table + `Interaction`/`Rating`/`Counter`
  models; idempotent like/dislike/vote/rate/favorite/subscribe/follow via
  `InteractionManager`, with `CounterSync` maintaining denormalized counts.
- Actor trait `Interactor`; target traits `Likeable`, `Voteable`, `Rateable`,
  `Favoritable`, `Subscribable`, `Followable`, and aggregate `Interactable`.

### Reactions & emoji

- `interactions_reactions` + `interactions_emojis`; unicode and custom
  `:shortcode:` emoji, multiple distinct per user, per-emoji summaries.
- `EmojiResolver` validation, `ReactionManager`, `Reactable` trait.

### Comments & mentions

- Threaded, markdown, soft-deletable comments with edit revisions and
  moderation states; `CommentManager` + `CommentRenderer` (CommonMark, safe).
- `MentionParser` resolves `@handles` against a configurable pool and keeps
  `interactions_mentions` in sync with the content.

### Social graph

- Friendships (request/accept/deny/block) and owner-scoped friend groups via
  `FriendshipManager` / `GroupManager`; `HasGraph` actor verbs (folded into
  `Interactor`).

### Events, notifications & facade

- Ten domain events fired from the managers.
- Optional, toggleable Notification classes + `InteractionNotificationSubscriber`.
- `Interactions` facade over the managers.

### Tooling

- Pest suite (34 tests), PHPStan level 8, Laravel Pint.
- CI across PHP 8.4 / Laravel 12 / Filament 3·4·5.

Filament admin (Comment moderation, custom emoji, friendships) lands in v1.1.
