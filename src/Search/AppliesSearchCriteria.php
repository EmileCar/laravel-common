<?php

namespace Carone\Common\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Defines a contract for applying search criteria filters to a query builder.
 */
interface AppliesSearchCriteria
{
    /**
     * Applies search criteria from the given SearchCriteria to the provided query builder.
     *
     * @param SearchCriteria $searchCriteria The search criteria containing filter expressions.
     * @return Builder The modified query builder with applied criteria.
     */
    public function applySearchCriteria(SearchCriteria $searchCriteria): Builder;
}
