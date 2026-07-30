<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Interactions\Http\Controllers\EngagementController;
use Kurt\Modules\Interactions\Http\Controllers\ReactionController;

/*
|--------------------------------------------------------------------------
| Interactions API routes
|--------------------------------------------------------------------------
|
| Registered by InteractionsServiceProvider::registerModuleApi() only when
| interactions.http.mode is 'api' or 'ui'. The outer group (prefix, base
| middleware, throttle and "interactions.api." name prefix) is applied by the
| Core API kit; this file only splits public reads from authenticated writes.
|
| Every endpoint addresses a polymorphic subject as {type}/{id}, where {type} is
| a morph alias resolved through the host's morph map (see SubjectResolver).
|
*/

/** @var array<int, string> $auth */
$auth = config('interactions.http.auth_middleware', ['auth']);

// Public reads — the denormalized reaction summary and engagement counts.
Route::get('{type}/{id}/reactions/summary', [ReactionController::class, 'summary'])->name('reactions.summary');
Route::get('{type}/{id}/counts', [EngagementController::class, 'counts'])->name('counts');

// Authenticated: the acting user's own state, plus every write.
Route::get('{type}/{id}/engagement', [EngagementController::class, 'show'])->name('engagement.show')->middleware($auth);
Route::post('{type}/{id}/reactions', [ReactionController::class, 'store'])->name('reactions.store')->middleware($auth);
Route::delete('{type}/{id}/reactions', [ReactionController::class, 'destroy'])->name('reactions.destroy')->middleware($auth);
Route::post('{type}/{id}/engagement/{kind}', [EngagementController::class, 'toggle'])->name('engagement.toggle')->middleware($auth);
