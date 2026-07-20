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
