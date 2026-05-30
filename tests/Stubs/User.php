<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Tests\Stubs;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Test actor. The Interactor trait is added in the engagement phase so the
 * social/engagement verbs ($user->follow(), ->like(), ->reactWith(), ...) are
 * exercised against a realistic Authenticatable + Notifiable user.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'username'];

    public $timestamps = true;
}
