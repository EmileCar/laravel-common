<?php

namespace Carone\Common\Tests\Unit\Search;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchTerm;
use Carone\Common\Search\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

class SearchCriteriaTest extends TestCase
{
    public function test_can_create_search_criteria_with_search_term_only(): void
    {
        $searchTerm = new SearchTerm('test query');
        $criteria = new SearchCriteria($searchTerm);

        $this->assertSame($searchTerm, $criteria->searchTerm);
        $this->assertEmpty($criteria->filters);
    }

    public function test_can_create_search_criteria_with_filters(): void
    {
        $searchTerm = new SearchTerm('test query');
        $filter1 = new TestSearchFilter('name', 'John');
        $filter2 = new TestSearchFilter('status', 'active');
        $filters = [$filter1, $filter2];

        $criteria = new SearchCriteria($searchTerm, $filters);

        $this->assertSame($searchTerm, $criteria->searchTerm);
        $this->assertSame($filters, $criteria->filters);
        $this->assertCount(2, $criteria->filters);
    }

    public function test_can_create_search_criteria_with_empty_filters_array(): void
    {
        $searchTerm = new SearchTerm('test query');
        $criteria = new SearchCriteria($searchTerm, []);

        $this->assertSame($searchTerm, $criteria->searchTerm);
        $this->assertEmpty($criteria->filters);
    }

    public function test_search_criteria_properties_are_public(): void
    {
        $searchTerm = new SearchTerm('test');
        $filter = new TestSearchFilter('category', 'books');
        $criteria = new SearchCriteria($searchTerm, [$filter]);

        // Test that properties are accessible publicly
        $this->assertInstanceOf(SearchTerm::class, $criteria->searchTerm);
        $this->assertIsArray($criteria->filters);
        $this->assertContains($filter, $criteria->filters);
    }

    public function test_search_criteria_with_multiple_filters(): void
    {
        $searchTerm = new SearchTerm('multiple filters');
        $filter1 = new TestSearchFilter('author', 'Smith');
        $filter2 = new TestSearchFilter('year', '2023');
        $filter3 = new TestSearchFilter('genre', 'fiction');

        $filters = [$filter1, $filter2, $filter3];
        $criteria = new SearchCriteria($searchTerm, $filters);

        $this->assertCount(3, $criteria->filters);
        $this->assertSame($filter1, $criteria->filters[0]);
        $this->assertSame($filter2, $criteria->filters[1]);
        $this->assertSame($filter3, $criteria->filters[2]);
    }
}

// Test implementation of SearchFilter for testing purposes
class TestSearchFilter implements SearchFilter
{
    private string $field;
    private string $value;

    public function __construct(string $field, string $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function apply(Builder $query): Builder
    {
        return $query->where($this->field, $this->value);
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}