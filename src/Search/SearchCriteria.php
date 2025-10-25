<?php

namespace Carone\Common\Search;

/**
 * Represents the criteria used for searching, including the search term and optional filters.
 */
class SearchCriteria
{
    public SearchTerm $searchTerm;
    public array $filters;

    public function __construct(SearchTerm $searchTerm, array $filters = [])
    {
        $this->searchTerm = $searchTerm;
        $this->filters = $filters;
    }
}
