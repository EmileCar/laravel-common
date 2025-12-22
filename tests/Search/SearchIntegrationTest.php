<?php

namespace Carone\Common\Tests\Feature\Search;

use Carone\Common\Search\SearchCriteria;
use Carone\Common\Search\SearchFilter;
use Carone\Common\Search\SearchTerm;
use Carone\Common\Search\AppliesSearchCriteria;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;
use Mockery;

class SearchIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_complete_search_workflow(): void
    {
        // Create a search term with complex input
        $searchTerm = new SearchTerm('john-doe@example.com, active user');

        // Verify term parsing
        $this->assertTrue($searchTerm->hasValue());
        $terms = $searchTerm->getTerms();
        $this->assertContains('john', $terms);
        $this->assertContains('doe@example', $terms);
        $this->assertContains('com', $terms);
        $this->assertContains('active', $terms);
        $this->assertContains('user', $terms);

        // Test query term limiting
        $queryTerms = $searchTerm->getTermsForQuery(3);
        $this->assertCount(3, $queryTerms);
        $this->assertEquals('john', $queryTerms[0]);
        $this->assertEquals('doe@example', $queryTerms[1]);
        $this->assertStringContainsString('com', $queryTerms[2]); // Should contain remaining terms
    }

    public function test_search_criteria_with_multiple_filters(): void
    {
        $searchTerm = new SearchTerm('product search');

        // Create various filters
        $categoryFilter = new CategoryFilter('electronics');
        $priceFilter = new PriceRangeFilter(100, 500);
        $availabilityFilter = new AvailabilityFilter(true);

        $filters = [$categoryFilter, $priceFilter, $availabilityFilter];
        $criteria = new SearchCriteria($searchTerm, $filters);

        // Verify criteria structure
        $this->assertSame($searchTerm, $criteria->searchTerm);
        $this->assertCount(3, $criteria->filters);
        $this->assertInstanceOf(CategoryFilter::class, $criteria->filters[0]);
        $this->assertInstanceOf(PriceRangeFilter::class, $criteria->filters[1]);
        $this->assertInstanceOf(AvailabilityFilter::class, $criteria->filters[2]);
    }

    public function test_search_criteria_application(): void
    {
        // Mock a query builder
        $queryBuilder = Mockery::mock(Builder::class);

        // Set up expectations for filter applications
        $queryBuilder->shouldReceive('where')
            ->with('category', 'books')
            ->once()
            ->andReturnSelf();

        $queryBuilder->shouldReceive('where')
            ->with('status', 'published')
            ->once()
            ->andReturnSelf();

        $queryBuilder->shouldReceive('where')
            ->with('title', 'like', '%search term%')
            ->once()
            ->andReturnSelf();

        // Create search criteria
        $searchTerm = new SearchTerm('search term');
        $filters = [
            new SimpleFilter('category', 'books'),
            new SimpleFilter('status', 'published'),
            new LikeFilter('title', 'search term')
        ];
        $criteria = new SearchCriteria($searchTerm, $filters);

        // Apply all filters
        $result = $queryBuilder;
        foreach ($criteria->filters as $filter) {
            $result = $filter->apply($result);
        }

        $this->assertSame($queryBuilder, $result);
    }

    public function test_search_service_integration(): void
    {
        $searchTerm = new SearchTerm('integration test query');
        $filters = [
            new SimpleFilter('active', true),
            new SimpleFilter('type', 'premium')
        ];

        $criteria = new SearchCriteria($searchTerm, $filters);
        $searchService = new TestSearchService();

        $queryBuilder = $searchService->applySearchCriteria($criteria);

        // Verify the service properly applied the criteria
        $this->assertInstanceOf(Builder::class, $queryBuilder);
    }
}

// Test implementations for the feature tests

class CategoryFilter implements SearchFilter
{
    private string $category;

    public function __construct(string $category)
    {
        $this->category = $category;
    }

    public function apply(Builder $query): Builder
    {
        return $query->where('category', $this->category);
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}

class PriceRangeFilter implements SearchFilter
{
    private float $minPrice;
    private float $maxPrice;

    public function __construct(float $minPrice, float $maxPrice)
    {
        $this->minPrice = $minPrice;
        $this->maxPrice = $maxPrice;
    }

    public function apply(Builder $query): Builder
    {
        return $query->whereBetween('price', [$this->minPrice, $this->maxPrice]);
    }

    public function getMinPrice(): float
    {
        return $this->minPrice;
    }

    public function getMaxPrice(): float
    {
        return $this->maxPrice;
    }
}

class AvailabilityFilter implements SearchFilter
{
    private bool $available;

    public function __construct(bool $available)
    {
        $this->available = $available;
    }

    public function apply(Builder $query): Builder
    {
        return $query->where('in_stock', $this->available);
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }
}

class SimpleFilter implements SearchFilter
{
    private string $field;
    private $value;

    public function __construct(string $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function apply(Builder $query): Builder
    {
        return $query->where($this->field, $this->value);
    }
}

class LikeFilter implements SearchFilter
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
        return $query->where($this->field, 'like', '%' . $this->value . '%');
    }
}

class TestSearchService implements AppliesSearchCriteria
{
    public function applySearchCriteria(SearchCriteria $searchCriteria): Builder
    {
        // Mock builder for demonstration
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('whereBetween')->andReturnSelf();
        
        // Apply all filters from the search criteria
        $result = $builder;
        foreach ($searchCriteria->filters as $filter) {
            $result = $filter->apply($result);
        }
        
        return $result;
    }
}