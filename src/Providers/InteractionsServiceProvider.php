<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Comments\CommentRenderer;
use Kurt\Modules\Interactions\Emoji\EmojiResolver;
use Kurt\Modules\Interactions\Engagement\Commands\ReconcileCountersCommand;
use Kurt\Modules\Interactions\Engagement\CounterSync;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\ReactionCounterSync;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Graph\FriendshipManager;
use Kurt\Modules\Interactions\Graph\GroupManager;
use Kurt\Modules\Interactions\Listeners\InteractionNotificationSubscriber;
use Kurt\Modules\Interactions\Mentions\MentionParser;
use Kurt\Modules\Interactions\Support\Interactions;
use Spatie\LaravelPackageTools\Package;

final class InteractionsServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'interactions';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-interactions')
            ->hasConfigFile('interactions')
            ->hasCommand(ReconcileCountersCommand::class)
            ->discoversMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CounterSync::class);
        $this->app->singleton(InteractionManager::class);
        $this->app->singleton(EmojiResolver::class);
        $this->app->singleton(ReactionCounterSync::class);
        $this->app->singleton(ReactionManager::class);
        $this->app->singleton(MentionParser::class);
        $this->app->singleton(CommentRenderer::class);
        $this->app->singleton(CommentManager::class);
        $this->app->singleton(FriendshipManager::class);
        $this->app->singleton(GroupManager::class);
        $this->app->singleton(Interactions::class);
    }

    public function packageBooted(): void
    {
        if ((bool) config('interactions.notifications.enabled', false)) {
            /** @var Dispatcher $events */
            $events = $this->app->make(Dispatcher::class);
            $events->subscribe(InteractionNotificationSubscriber::class);
        }

        // Register the out-of-the-box REST API. A no-op in headless mode.
        $this->registerModuleApi(__DIR__.'/../../routes/api.php');
    }
}
