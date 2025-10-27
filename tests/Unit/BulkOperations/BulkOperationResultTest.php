<?php

namespace Carone\Common\Tests\Unit\BulkOperations;

use Carone\Common\BulkOperations\BulkOperationResult;
use PHPUnit\Framework\TestCase;

class BulkOperationResultTest extends TestCase
{
    public function test_can_create_empty_bulk_operation_result(): void
    {
        $result = new BulkOperationResult();
        $this->assertEmpty($result->getSucceeded());
    $this->assertEmpty($result->getFailed());
        $this->assertEquals(0, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
        $this->assertEquals(0, $result->getTotalCount());
        $this->assertFalse($result->hasFailures());
    }

    public function test_can_create_bulk_operation_result_with_initial_data(): void
    {
        $successful = ['item1', 'item2'];
        // failed entries must be structured as ['item' => ..., 'error' => ?]
        $failedEntries = [['item' => 'item3', 'error' => null]];

        $result = new BulkOperationResult($successful, $failedEntries);

        $this->assertEquals($successful, $result->getSucceeded());
        $this->assertEquals(['item3'], $result->getFailed());
        $this->assertEquals(2, $result->getSucceededCount());
        $this->assertEquals(1, $result->getFailedCount());
        $this->assertEquals(3, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_can_add_successful_item(): void
    {
        $result = new BulkOperationResult();

        $returnedResult = $result->addSucceeded('item1');

        $this->assertSame($result, $returnedResult); // Test fluent interface
        $this->assertEquals(['item1'], $result->getSucceeded());
        $this->assertEquals(1, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertFalse($result->hasFailures());
    }

    public function test_can_add_failed_item(): void
    {
        $result = new BulkOperationResult();

        $returnedResult = $result->addFailed('failed_item');

        $this->assertSame($result, $returnedResult); // Test fluent interface
        $this->assertEquals(['failed_item'], $result->getFailed());
        $this->assertEquals(0, $result->getSucceededCount());
        $this->assertEquals(1, $result->getFailedCount());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_can_add_failed_item_with_error_message(): void
    {
        $result = new BulkOperationResult();

        $result->addFailed('failed_item', 'Validation failed');

        $failedEntries = $result->getFailedWithErrors();
        $this->assertCount(1, $failedEntries);
        $this->assertEquals('failed_item', $failedEntries[0]['item']);
        $this->assertEquals('Validation failed', $failedEntries[0]['error']);
        
        $this->assertEquals(['failed_item'], $result->getFailed());
        $this->assertTrue($result->hasFailures());
    }

    public function test_can_add_failed_item_without_error_message(): void
    {
        $result = new BulkOperationResult();
        $result->addFailed('failed_item', null);

        // getFailedWithErrors filters out null/empty error messages
        $failedEntries = $result->getFailedWithErrors();
        $this->assertCount(0, $failedEntries);

        $this->assertEquals(['failed_item'], $result->getFailed());
    }

    public function test_can_add_multiple_items_fluently(): void
    {
        $result = new BulkOperationResult();

        $result
            ->addSucceeded('success1')
            ->addSucceeded('success2')
            ->addFailed('error1', 'First error')
            ->addSucceeded('success3')
            ->addFailed('error2', 'Second error');

    $this->assertEquals(['success1', 'success2', 'success3'], $result->getSucceeded());
    $this->assertEquals(['error1', 'error2'], $result->getFailed());
        $this->assertEquals(3, $result->getSucceededCount());
        $this->assertEquals(2, $result->getFailedCount());
        $this->assertEquals(5, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
        
        $failedWithErrors = $result->getFailedWithErrors();
        $this->assertCount(2, $failedWithErrors);
        $this->assertEquals('First error', $failedWithErrors[0]['error']);
        $this->assertEquals('Second error', $failedWithErrors[1]['error']);
    }

    public function test_has_failures_returns_false_when_no_failures(): void
    {
        $result = new BulkOperationResult();
        $result->addSucceeded('item1');

        $this->assertFalse($result->hasFailures());
    }

    public function test_has_failures_returns_true_when_failures_exist(): void
    {
        $result = new BulkOperationResult();
        $result->addFailed('failed_item');

        $this->assertTrue($result->hasFailures());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $result = new BulkOperationResult();
        $result
            ->addSucceeded('user1')
            ->addSucceeded('user2')
            ->addFailed('invalid_user', 'Invalid user data');

        $array = $result->toArray();

    $this->assertArrayHasKey('succeeded', $array);
    $this->assertArrayHasKey('failed', $array);
    $this->assertArrayHasKey('failed_with_errors', $array);
    $this->assertEquals(['user1', 'user2'], $array['succeeded']);
    // 'failed' contains raw failed item identifiers
    $this->assertEquals(['invalid_user'], $array['failed']);
    // 'failed_with_errors' contains the entries with errors
    $this->assertEquals([['item' => 'invalid_user', 'error' => 'Invalid user data']], $array['failed_with_errors']);
    }

    public function test_supports_different_item_types(): void
    {
        $result = new BulkOperationResult();

        $stringItem = 'string_item';
        $arrayItem = ['id' => 1, 'name' => 'test'];
        $objectItem = (object) ['id' => 2, 'status' => 'failed'];

        $result
            ->addSucceeded($stringItem)
            ->addSucceeded($arrayItem)
            ->addFailed($objectItem, 'Object validation failed');

        $this->assertContains($stringItem, $result->getSucceeded());
        $this->assertContains($arrayItem, $result->getSucceeded());
        $this->assertContains($objectItem, $result->getFailed());
    }

    public function test_empty_result_counts_are_zero(): void
    {
        $result = new BulkOperationResult();

        $this->assertEquals(0, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
        $this->assertEquals(0, $result->getTotalCount());
    }

    public function test_total_count_is_sum_of_successful_and_failed(): void
    {
        $result = new BulkOperationResult();
        
        // Add 5 successful and 3 failed items
        for ($i = 1; $i <= 5; $i++) {
            $result->addSucceeded("success_$i");
        }
        
        for ($i = 1; $i <= 3; $i++) {
            $result->addFailed("failed_$i");
        }

        $this->assertEquals(5, $result->getSucceededCount());
        $this->assertEquals(3, $result->getFailedCount());
        $this->assertEquals(8, $result->getTotalCount());
    }

    public function test_get_failed_with_errors_filters_correctly(): void
    {
        $result = new BulkOperationResult();
        
        $result
            ->addFailed('item1', 'Error message 1')
            ->addFailed('item2', null)
            ->addFailed('item3', 'Error message 3')
            ->addFailed('item4', '');

    $failedWithErrors = $result->getFailedWithErrors();

    // Only items with non-empty error messages should be returned
    $this->assertCount(2, $failedWithErrors);
    $this->assertEquals('item1', $failedWithErrors[0]['item']);
    $this->assertEquals('Error message 1', $failedWithErrors[0]['error']);
    $this->assertEquals('item3', $failedWithErrors[1]['item']);
    $this->assertEquals('Error message 3', $failedWithErrors[1]['error']);
    }

    public function test_backward_compatibility_with_simple_failed_items(): void
    {
        $result = new BulkOperationResult();

        $result
            ->addFailed('item1', 'Error 1')
            ->addFailed('item2');

    $failedItems = $result->getFailed();
    $this->assertEquals(['item1', 'item2'], $failedItems);
        $this->assertEquals(2, $result->getFailedCount());
    }

    public function test_to_array_includes_failed_items_separately(): void
    {
        $result = new BulkOperationResult();
        
        $result
            ->addSucceeded('success1')
            ->addFailed('fail1', 'Error 1')
            ->addFailed('fail2');
        
        $array = $result->toArray();
        
        $this->assertArrayHasKey('failed', $array);
        $this->assertEquals(['fail1','fail2'], $array['failed']);
        // failed_with_errors only contains entries with non-empty errors
        $this->assertEquals([['item'=>'fail1','error'=>'Error 1']], $array['failed_with_errors']);
    }
}