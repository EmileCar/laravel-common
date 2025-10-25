<?php

namespace Carone\Common\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Defines a contract for a search filter that can be applied to a query builder.
 */
interface SearchFilter
{

    /**
     * Applies the filter to the given query builder.
     *
     * @param Builder $query The query builder to which the filter will be applied.
     * @return Builder The modified query builder with the filter applied.
     */
    public function apply(Builder $query): Builder;
}
