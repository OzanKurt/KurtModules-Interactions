<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Concerns\Interactable;

/**
 * Test target — receives the full engagement surface via the aggregate
 * Interactable trait.
 */
class Post extends Model
{
    use Interactable;

    protected $table = 'posts';

    /** @var list<string> */
    protected $fillable = ['title'];
}
