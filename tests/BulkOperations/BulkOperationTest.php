<?php

namespace Carone\Common\Tests\Unit\BulkOperations;

use Carone\Common\BulkOperations\BulkOperation;
use Carone\Common\BulkOperations\BulkOperationResult;
use PHPUnit\Framework\TestCase;
use Exception;

class BulkOperationTest extends TestCase
{
    public function test_can_create_bulk_operation(): void
    {
        $operation = BulkOperation::create(function ($item) {
            // Simple operation that always succeeds
            return $item;
        });

        $this->assertInstanceOf(BulkOperation::class, $operation);
    }

    public function test_execute_all_successful(): void
    {
        $operation = BulkOperation::create(function ($number) {
            return $number + 1;
        });

        $subjects = [1, 2, 3, 4, 5];
        $result = $operation->execute($subjects);

        $this->assertInstanceOf(BulkOperationResult::class, $result);
        $this->assertEquals($subjects, $result->getSucceeded());
        $this->assertEmpty($result->getFailed());
        $this->assertEquals(5, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
        $this->assertFalse($result->hasFailures());
    }

    public function test_execute_with_some_failures(): void
    {
        $operation = BulkOperation::create(function ($number) {
            if ($number === 0) {
                throw new Exception('Cannot divide by zero');
            }
            return 10 / $number;
        });

        $subjects = [1, 2, 0, 4, 0, 5];
        $result = $operation->execute($subjects);

        $this->assertEquals([1, 2, 4, 5], $result->getSucceeded());
        $this->assertEquals([0, 0], $result->getFailed());
        $this->assertEquals(4, $result->getSucceededCount());
        $this->assertEquals(2, $result->getFailedCount());
        $this->assertTrue($result->hasFailures());

        $failedWithErrors = $result->getFailedWithErrors();
        $this->assertCount(2, $failedWithErrors);
        $this->assertEquals('Cannot divide by zero', $failedWithErrors[0]['error']);
    }

    public function test_execute_with_custom_handlers(): void
    {
        $successLog = [];
        $failureLog = [];

        $operation = BulkOperation::create(function ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            return $email;
        });

        $successHandler = function ($email) use (&$successLog) {
            $successLog[] = "Processed: $email";
        };

        $failureHandler = function ($email, $exception) use (&$failureLog) {
            $failureLog[] = "Failed: $email - " . $exception->getMessage();
        };

        $subjects = ['test@example.com', 'invalid-email', 'user@domain.org', 'bad-format'];
        $result = $operation->execute($subjects, $successHandler, $failureHandler);

        $this->assertEquals(['test@example.com', 'user@domain.org'], $result->getSucceeded());
        $this->assertEquals(['invalid-email', 'bad-format'], $result->getFailed());

        $this->assertCount(2, $successLog);
        $this->assertCount(2, $failureLog);
        $this->assertStringContainsString('Processed: test@example.com', $successLog[0]);
        $this->assertStringContainsString('Failed: invalid-email', $failureLog[0]);
    }

    public function test_execute_and_get_successful(): void
    {
        $operation = BulkOperation::create(function ($number) {
            if ($number % 2 !== 0) {
                throw new Exception('Number is odd');
            }
            return $number;
        });

        $subjects = [1, 2, 3, 4, 5, 6];
        $successful = $operation->executeAndGetSuccessful($subjects);

        $this->assertEquals([2, 4, 6], $successful);
    }

    public function test_execute_and_get_failed(): void
    {
        $operation = BulkOperation::create(function ($value) {
            if (!is_string($value) || strlen($value) < 3) {
                throw new Exception('String must be at least 3 characters');
            }
            return $value;
        });

        $subjects = ['ab', 'hello', 123, 'world', 'x'];
        $failed = $operation->executeAndGetFailed($subjects);

        $this->assertCount(3, $failed); // 'ab', 123, 'x'
        // executeAndGetFailed returns the failed entries (item + error)
        $this->assertEquals('ab', $failed[0]['item']);
        $this->assertEquals(123, $failed[1]['item']);
        $this->assertEquals('x', $failed[2]['item']);
    }

    public function test_real_world_example_delete_multiple(): void
    {
        // Simulate a service with some items that can't be deleted
        $mediaService = new class {
            private array $items = [1, 2, 3, 4, 5];
            private array $protected = [3]; // Item 3 is protected

            public function delete(int $id): void
            {
                if (in_array($id, $this->protected)) {
                    throw new Exception("Item $id is protected and cannot be deleted");
                }
                if (!in_array($id, $this->items)) {
                    throw new Exception("Item $id not found");
                }
                // Simulate successful deletion
            }
        };

        $operation = BulkOperation::create(function ($id) use ($mediaService) {
            $mediaService->delete($id);
        });

        $idsToDelete = [1, 2, 3, 4, 99]; // 3 is protected, 99 doesn't exist
        $result = $operation->execute($idsToDelete);

        $this->assertEquals([1, 2, 4], $result->getSucceeded());
        $this->assertEquals([3, 99], $result->getFailed());

        $failed = $result->getFailedWithErrors();
        $this->assertStringContainsString('protected', $failed[0]['error']);
        $this->assertStringContainsString('not found', $failed[1]['error']);
    }

    public function test_empty_subjects_array(): void
    {
        $operation = BulkOperation::create(function ($item) {
            return $item;
        });

        $result = $operation->execute([]);

        $this->assertEmpty($result->getSucceeded());
        $this->assertEmpty($result->getFailed());
        $this->assertEquals(0, $result->getTotalCount());
    }
}