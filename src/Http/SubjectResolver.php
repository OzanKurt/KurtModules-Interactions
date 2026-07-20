<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves the polymorphic subject an API endpoint addresses by {type}/{id}.
 *
 * {type} is a morph alias, never a class name: it is looked up through Laravel's
 * morph map (the same map that backs every polymorphic relation), so only the
 * types a host has explicitly registered are addressable. An unknown/unmapped
 * alias is a 404 — an arbitrary class named in the URL is never instantiated.
 * The model is then loaded by id, a 404 when no such row exists.
 */
final class SubjectResolver
{
    public function resolve(string $type, int|string $id): Model
    {
        $class = Relation::getMorphedModel($type);

        if ($class === null || ! is_subclass_of($class, Model::class)) {
            throw new NotFoundHttpException("Unknown subject type [{$type}].");
        }

        $model = $class::query()->find($id);

        if (! $model instanceof Model) {
            throw new NotFoundHttpException("No [{$type}] found for identifier [{$id}].");
        }

        return $model;
    }
}
