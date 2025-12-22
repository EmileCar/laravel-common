<?php

namespace Carone\Common\Tests\Unit\Search;

use Carone\Common\Search\SearchTerm;
use PHPUnit\Framework\TestCase;

class SearchTermTest extends TestCase
{
    public function test_creates_search_term_with_value(): void
    {
        $term = new SearchTerm('hello world');

        $this->assertTrue($term->hasValue());
    }

    public function test_trims_whitespace_from_value(): void
    {
        $term = new SearchTerm('  hello world  ');

        $this->assertEquals(['hello', 'world'], $term->getTerms());
    }

    public function test_empty_string_has_no_value(): void
    {
        $term = new SearchTerm('');

        $this->assertFalse($term->hasValue());
    }

    public function test_whitespace_only_string_has_no_value(): void
    {
        $term = new SearchTerm('   ');

        $this->assertFalse($term->hasValue());
    }

    public function test_gets_terms_splits_on_whitespace(): void
    {
        $term = new SearchTerm('hello world test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_splits_on_hyphens(): void
    {
        $term = new SearchTerm('hello-world-test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_splits_on_commas(): void
    {
        $term = new SearchTerm('hello,world,test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_splits_on_slashes(): void
    {
        $term = new SearchTerm('hello/world/test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_splits_on_periods(): void
    {
        $term = new SearchTerm('hello.world.test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_splits_on_mixed_delimiters(): void
    {
        $term = new SearchTerm('hello-world test/foo.bar,baz');

        $this->assertEquals(['hello', 'world', 'test', 'foo', 'bar', 'baz'], $term->getTerms());
    }

    public function test_gets_terms_ignores_empty_parts(): void
    {
        $term = new SearchTerm('hello--world  test');

        $this->assertEquals(['hello', 'world', 'test'], $term->getTerms());
    }

    public function test_gets_terms_for_query_returns_all_when_under_limit(): void
    {
        $term = new SearchTerm('hello world');

        $result = $term->getTermsForQuery(3);

        $this->assertEquals(['hello', 'world'], $result);
    }

    public function test_gets_terms_for_query_returns_all_when_at_limit(): void
    {
        $term = new SearchTerm('hello world test');

        $result = $term->getTermsForQuery(3);

        $this->assertEquals(['hello', 'world', 'test'], $result);
    }

    public function test_gets_terms_for_query_concatenates_excess_terms(): void
    {
        $term = new SearchTerm('hello world test foo bar');

        $result = $term->getTermsForQuery(3);

        $this->assertEquals(['hello', 'world', 'test foo bar'], $result);
    }

    public function test_gets_terms_for_query_with_limit_of_one(): void
    {
        $term = new SearchTerm('hello world test');

        $result = $term->getTermsForQuery(1);

        $this->assertEquals(['hello world test'], $result);
    }

    public function test_gets_terms_for_query_handles_single_term(): void
    {
        $term = new SearchTerm('hello');

        $result = $term->getTermsForQuery(3);

        $this->assertEquals(['hello'], $result);
    }

    public function test_gets_terms_for_query_handles_empty_term(): void
    {
        $term = new SearchTerm('');

        $result = $term->getTermsForQuery(3);

        $this->assertEquals([], $result);
    }

    public function test_complex_search_term_parsing(): void
    {
        $term = new SearchTerm('user@example.com, john-doe/profile test.file');

        $terms = $term->getTerms();
        $queryTerms = $term->getTermsForQuery(3);

        $this->assertEquals(['user@example', 'com', 'john', 'doe', 'profile', 'test', 'file'], $terms);
        $this->assertEquals(['user@example', 'com', 'john doe profile test file'], $queryTerms);
    }
}