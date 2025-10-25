<?php

namespace Carone\Common\Search;

/**
 * A utility class that represents a search input value, designed to process and
 * tokenize user-entered strings for querying search indexes or databases.
 */
class SearchTerm
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $this->value = trim($value);
    }

    public function hasValue(): bool
    {
        return !empty($this->value);
    }

    /**
     * Splits the input string into individual terms.
     *
     * Terms are extracted by splitting the input string on:
     * - whitespace
     * - hyphens (-)
     * - commas (,)
     * - slashes (/)
     * - periods (.)
     *
     * @return array An array of non-empty string tokens.
     */
    public function getTerms(): array
    {
        return preg_split('/[\s\-\,\/\.]+/', $this->value, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Returns up to `$limit` terms suitable for a simplified query.
     *
     * If the number of terms exceeds the limit, the first (limit - 1)
     * terms are returned normally, and the remaining terms are concatenated
     * into the final item in the array.
     *
     * @param int $limit The number of terms to return.
     * @return array
     */
    public function getTermsForQuery(int $limit = 3): array
    {
        $terms = $this->getTerms();
        if (count($terms) <= $limit) {
            return $terms;
        }

        $firstTerms = array_slice($terms, 0, $limit - 1);
        $firstTerms[] = implode(' ', array_slice($terms, $limit - 1));

        return $firstTerms;
    }
}
