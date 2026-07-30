# Interactions

A full-featured, polymorphic social & engagement toolkit for Laravel. Any
Eloquent model opts in via traits to gain emoji reactions, threaded comments,
@mentions, the engagement primitives (like / dislike / vote / rate / favorite /
subscribe / follow), and a social graph (friendships + friend groups).

Part of the **KurtModules** family. Headless by design, with an optional Filament
admin (Filament 3, 4 & 5). Core-only hard dependency.

Inspired by [laravel-acquaintances](https://github.com/multicaret/laravel-acquaintances),
overtrue's social suite, [laravel-reactions](https://github.com/qirolab/laravel-reactions),
and [Laravel-Mentions](https://github.com/CrixuAMG/Laravel-Mentions).

## Features

- **Engagement** — `like`/`dislike`, `vote` (up/down + net score), `rate`
  (averaged), `favorite`, `subscribe`, `follow`. Idempotent; denormalized
  counters with a live-query fallback.
- **Reactions** — any unicode emoji **and** Discord-style custom emoji
  (`:shortcode:`), multiple distinct emoji per user, per-emoji summaries.
- **Comments** — polymorphic, unlimited threading, markdown (safe-rendered),
  edit history, soft-delete + moderation states.
- **Mentions** — `@handle` parsing against a configurable pool, recorded and
  kept in sync with the content.
- **Social graph** — one-way follows, mutual friendships (request / accept /
  deny / block), and named friend groups.
- **Events + optional notifications** — domain events for everything, plus
  toggleable Notification classes.
- **REST API (optional)** — an out-of-the-box, safe-by-default JSON API for
  reactions and engagement over `{type}/{id}` morph-aliased subjects. See [API](#api).

## Requirements

- PHP 8.3+, Laravel 12/13
- [`ozankurt/laravel-modules-core`](https://github.com/OzanKurt/KurtModules-Core) ^2.0

## Installation

```bash
composer require ozankurt/laravel-modules-interactions
```

Core is not on Packagist yet — add it as a VCS repository in your app's
`composer.json`:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/OzanKurt/KurtModules-Core" }
]
```

Publish + migrate:

```bash
php artisan vendor:publish --tag=modules-interactions-config
php artisan vendor:publish --tag=modules-interactions-migrations
php artisan migrate
```

## Setup

Add the actor trait to your user model and the target trait to anything that can
be interacted with:

```php
use Kurt\Modules\Interactions\Concerns\Interactor;   // the user (actor)
use Kurt\Modules\Interactions\Concerns\Interactable; // any target (full surface)

class User extends Authenticatable { use Interactor; }
class Post extends Model { use Interactable; }
```

Prefer the granular target traits (`Likeable`, `Voteable`, `Rateable`,
`Favoritable`, `Subscribable`, `Followable`, `Reactable`, `Commentable`) when a
model only needs a subset.

## Usage

### Engagement

```php
$user->like($post);          $post->likesCount();      $post->isLikedBy($user);
$user->dislike($post);       // like and dislike are mutually exclusive
$user->upvote($post);        $post->votesScore();      // net of up/down
$user->downvote($post);      $post->votesCount();
$user->rate($post, 5);       $post->averageRating();   $post->ratingsCount();
$user->favorite($post);      $post->favoritesCount();
$user->subscribe($post);     $post->subscribersCount();
$user->follow($otherUser);   $otherUser->followersCount();
```

### Reactions

```php
$user->reactWith($post, '🎉');
$user->reactWith($post, ':partyblob:');   // custom emoji (registered)
$user->toggleReaction($post, '👍');
$post->reactionSummary();                 // ['🎉' => 5, ':partyblob:' => 2]
```

### Comments + mentions

```php
$comment = $user->comment($post, 'Great work @jane!');   // records the @jane mention
$reply   = $user->comment($post, 'Thanks!', $comment);   // threaded reply
$comment->renderedBody();                                // safe HTML from markdown

app(\Kurt\Modules\Interactions\Comments\CommentManager::class)
    ->moderate($comment, \Kurt\Modules\Interactions\Comments\Enums\CommentStatus::Spam);
```

### Social graph

```php
$alice->befriend($bob);
$bob->acceptFriendRequest($alice);   // now mutual
$alice->isFriendWith($bob);          // true
$alice->blockFriend($carol);

$group = $alice->createFriendGroup('Close Friends');
app(\Kurt\Modules\Interactions\Graph\GroupManager::class)->addMember($group, $bob);
```

### Events & notifications

Every verb fires a domain event (`Liked`, `Voted`, `Reacted`, `Commented`,
`CommentReplied`, `UserMentioned`, `Followed`, `FriendRequested`, …). Set
`interactions.notifications.enabled` to also dispatch the bundled Notification
classes (new follower, mention, comment reply, friend request) to Notifiable
recipients.

### Facade

```php
use Kurt\Modules\Interactions\Facades\Interactions;

Interactions::reactions()->summary($post);
Interactions::friendships()->areFriends($alice, $bob);
```

## Filament admin (optional)

Register the version-dispatching plugin on a panel — the same call works on
Filament 3, 4, and 5:

```php
use Kurt\Modules\Interactions\Filament\InteractionsPlugin;

$panel->plugin(InteractionsPlugin::make());
```

It adds a **Comments** resource (moderate: approve / mark-spam / delete, filter
by status; create disabled — comments come from the API/manager), a
**Custom emoji** resource (full CRUD), and a read-only **Friendships** overview.

## API

An out-of-the-box JSON REST API, built on the Core [API kit]. **Safe by default:**
nothing is registered until you opt in. Set the mode in `config/interactions.php`
(or via env) to expose the endpoints:

```env
INTERACTIONS_HTTP_MODE=api   # headless (default) | api | ui
```

```php
// config/interactions.php
'http' => [
    'mode' => env('INTERACTIONS_HTTP_MODE', 'headless'),
    'prefix' => 'api/interactions',        // URL prefix for every route
    'middleware' => ['api'],               // base middleware (public reads)
    'auth_middleware' => ['auth'],         // appended to writes + the per-user read
    'rate_limit' => '60,1',                // maxAttempts,decayMinutes for interactions-api
],
```

Reads over the denormalized data are public; every write (and the per-user
engagement state) additionally requires `auth_middleware`. A host restricts
access further — its own guard, `auth:sanctum`, a policy middleware — purely by
changing `auth_middleware`; the module ships no policy of its own, so an
authenticated user may react/engage on any resolvable subject.

### The `{type}/{id}` morph-alias contract

Every endpoint addresses a polymorphic **subject** as `{type}/{id}`, where
`{type}` is a **morph alias** — never a class name. It is resolved through
Laravel's morph map (`Relation::enforceMorphMap`), so **only the types a host has
registered are addressable**; an unknown or unmapped alias is a `404`, and an
arbitrary class named in the URL is never instantiated. The model is then loaded
by `id` (`404` when no such row exists).

```php
// The host registers the subjects it wants exposed, e.g. in a service provider:
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'post' => \App\Models\Post::class,
    'comment' => \App\Models\Comment::class,
]);
```

### Endpoints

All paths are relative to the configured prefix (default `api/interactions`).
Writes flow through the `ReactionManager` / `InteractionManager`, so the
denormalized counters and reaction summaries stay consistent.

| Method | Path | Auth | Purpose |
|---|---|:---:|---|
| `POST` | `{type}/{id}/reactions` | ✓ | React with an emoji (`{ "emoji": "🎉" }`). Returns the updated summary. |
| `DELETE` | `{type}/{id}/reactions` | ✓ | Remove one of your emoji reactions (`{ "emoji": "🎉" }`). |
| `GET` | `{type}/{id}/reactions/summary` | — | The denormalized per-emoji tally, e.g. `{ "🎉": 5, ":party:": 2 }`. |
| `POST` | `{type}/{id}/engagement/{kind}` | ✓ | Toggle an engagement kind on/off. Returns `active` + counts. |
| `GET` | `{type}/{id}/engagement` | ✓ | The acting user's per-kind state (+ vote value / rating) and counts. |
| `GET` | `{type}/{id}/counts` | — | The public engagement + reaction tallies. |

`{kind}` is one of `like`, `dislike`, `vote`, `favorite`, `subscribe`, `follow`.
Toggling `like`/`dislike` clears its opposite. `vote` accepts an optional
`{ "value": 1 }` or `{ "value": -1 }` (default `1`) which feeds the net score.

Responses use the Core envelope: `{ "data": … }` on success, and
`{ "message": …, "errors": … }` on error (`401` for a guest on a write, `404`
for an unknown type/subject, `422` for a bad emoji, unsupported kind, or
self-interaction). Each group is throttled by the named `interactions-api`
limiter (keyed by user id, or client IP for guests).

[API kit]: https://github.com/OzanKurt/KurtModules-Core#api-kit

## Configuration

See `config/interactions.php`: mention pool + pattern, reaction rules
(unicode/custom/max), comment nesting/markdown/moderation defaults, graph
toggles, counter driver, and notification channels.

### Counters

`counters.driver` chooses where totals live: `table` (denormalized
`interactions_counters`, the default) or `none` (skip the table and read counts
via a live query).

When the table is used, `engagement.counters.driver` chooses how it is kept in
step with each write:

- `recompute` (default) – re-run a full `COUNT(*)` after each mutation.
  Self-healing but O(n) per write.
- `atomic` – O(1) in-transaction `increment`/`decrement` of the stored tally.
  Run the reconcile command periodically to bound any drift:

  ```bash
  php artisan interactions:reconcile
  ```

  It rewrites every counter from live interaction counts, so it is also useful
  after a bulk import that bypassed the write path.

`reactionSummary()` is backed by the same machinery: a denormalized
`interactions_reaction_counts` cache (per reactable + emoji) maintained on
react/unreact using the `engagement.counters.driver` strategy, so summaries are
served without a live `groupBy`. It falls back to a live aggregate when
`counters.driver` is `none`, and `interactions:reconcile` rebuilds the reaction
cache alongside the engagement counters.

### Cleanup on delete

Models using `HasInteractions` (or the aggregate `Interactable`) purge their
interactions, ratings, reactions and counters automatically when the subject is
deleted, so a removed subject leaves no orphaned engagement behind. Soft-deletes
are skipped; the rows are reclaimed only on a real (force) delete.

## Testing

```bash
composer test    # Pest
composer stan    # PHPStan level 8
composer lint    # Pint
```

## License

MIT © Ozan Kurt
