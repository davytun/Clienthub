<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Automatically filters every query on tenant models to the authenticated business.
 *
 * Applied via the boot() method on each model that has a business_id column.
 * When no business is authenticated (e.g. during migrations or CLI commands),
 * the scope does nothing, which is safe because no real user session exists.
 */
class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $businessId = $this->resolveBusinessId();

        if ($businessId !== null) {
            $builder->where($model->getTable() . '.business_id', $businessId);
        }
    }

    private function resolveBusinessId(): ?int
    {
        // Staff/owner guard
        if (auth()->guard('web')->check()) {
            return auth()->guard('web')->user()->business_id;
        }

        // Client guard
        if (auth()->guard('client')->check()) {
            return auth()->guard('client')->user()->business_id;
        }

        return null;
    }
}
