<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kurt\Modules\Interactions\Concerns\Interactor;

/**
 * Test actor — performs the social/engagement verbs ($user->follow(),
 * ->like(), ->upvote(), ->rate(), ...) against a realistic
 * Authenticatable + Notifiable user.
 */
class User extends Authenticatable
{
    use Interactor;
    use Notifiable;

    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'username'];

    public $timestamps = true;
}
