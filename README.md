# Laravel Common

A collection of reusable Laravel utilities and patterns for common application needs.

## Use Cases

### 1. Search

A flexible search system for building complex queries with search terms and filters.

**Components:**
- **SearchTerm**: Processes and tokenizes user input, splitting on whitespace, hyphens, commas, and slashes
- **SearchCriteria**: Combines a search term with optional filters to define search parameters
- **SearchFilter**: Interface for implementing custom query filters
- **AppliesSearchCriteria**: Interface for repositories/services that apply search criteria to Eloquent queries

**Usage Example:**
```php
use Carone\Common\Search\SearchTerm;
use Carone\Common\Search\SearchCriteria;

$searchTerm = new SearchTerm('john doe');
$criteria = new SearchCriteria($searchTerm, [$statusFilter, $dateFilter]);

// In your repository implementing AppliesSearchCriteria:
$query = $this->applySearchCriteria($criteria);
```

**When to use:** Building search functionality with multiple filters, faceted search, or complex query builders.

---

### 2. Bulk Operations

A robust pattern for executing operations on multiple items with automatic error handling and result tracking.

**Components:**
- **BulkOperation**: Executes a closure on multiple subjects, capturing successes and failures
- **BulkOperationResult**: Tracks which items succeeded, which failed, and provides detailed error information

**Usage Example:**
```php
use Carone\Common\BulkOperations\BulkOperation;

$operation = BulkOperation::create(function($user) {
    $user->activate();
    $user->save();
});

$result = $operation->execute(
    $users,
    successHandler: fn($user) => Log::info("Activated: {$user->id}"),
    failureHandler: fn($user, $e) => Log::error("Failed: {$user->id}")
);

$succeeded = $result->getSucceeded();
$failed = $result->getFailed();
$failedWithErrors = $result->getFailedWithErrors();
```

**When to use:** Batch processing, mass updates, import operations, or any scenario where you need to track partial success/failure across multiple items.

---

## Installation

```bash
composer require carone/laravel-common
```

## Testing

Run all tests:
```bash
vendor/bin/phpunit
```

Run specific test suite:
```bash
vendor/bin/phpunit --testsuite=Search
vendor/bin/phpunit --testsuite=BulkOperations
```