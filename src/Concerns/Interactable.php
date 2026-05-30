<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Concerns\Favoritable;
use Kurt\Modules\Interactions\Engagement\Concerns\Followable;
use Kurt\Modules\Interactions\Engagement\Concerns\HasInteractions;
use Kurt\Modules\Interactions\Engagement\Concerns\Likeable;
use Kurt\Modules\Interactions\Engagement\Concerns\Rateable;
use Kurt\Modules\Interactions\Engagement\Concerns\Reactable;
use Kurt\Modules\Interactions\Engagement\Concerns\Subscribable;
use Kurt\Modules\Interactions\Engagement\Concerns\Voteable;

/**
 * Convenience aggregate for a model that should receive the full engagement
 * surface. Compose the granular target traits instead when you only need a
 * subset. Reactable / Commentable / Mentionable are folded in by their phases.
 *
 * @mixin Model
 */
trait Interactable
{
    use Favoritable;
    use Followable;
    use HasInteractions;
    use Likeable;
    use Rateable;
    use Reactable;
    use Subscribable;
    use Voteable;
}
