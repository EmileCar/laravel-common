<?php

namespace Carone\Common\Tests\Unit\BulkOperations;

use Carone\Common\BulkOperations\BulkOperationResult;
use PHPUnit\Framework\TestCase;

class BulkOperationResultTest extends TestCase
{
    public function test_can_create_empty_bulk_operation_result(): void
    {
        $result = BulkOperationResult::createFor('create');

        $this->assertEquals('create', $result->getOperationType());
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
        $failed = ['item3'];

        $result = BulkOperationResult::createFor('update', $successful, $failed);

        $this->assertEquals('update', $result->getOperationType());
        $this->assertEquals($successful, $result->getSucceeded());
        $this->assertEquals($failed, $result->getFailed());
        $this->assertEquals(2, $result->getSucceededCount());
        $this->assertEquals(1, $result->getFailedCount());
        $this->assertEquals(3, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_can_add_successful_item(): void
    {
        $result = BulkOperationResult::createFor('delete');

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
        $result = BulkOperationResult::createFor('create');

        $returnedResult = $result->addFailed('failed_item');

        $this->assertSame($result, $returnedResult); // Test fluent interface
        $this->assertEquals(['failed_item'], $result->getFailed());
        $this->assertEquals(0, $result->getSucceededCount());
        $this->assertEquals(1, $result->getFailedCount());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_can_add_multiple_items_fluently(): void
    {
        $result = BulkOperationResult::createFor('batch');

        $result
            ->addSucceeded('success1')
            ->addSucceeded('success2')
            ->addFailed('error1')
            ->addSucceeded('success3')
            ->addFailed('error2');

        $this->assertEquals(['success1', 'success2', 'success3'], $result->getSucceeded());
        $this->assertEquals(['error1', 'error2'], $result->getFailed());
        $this->assertEquals(3, $result->getSucceededCount());
        $this->assertEquals(2, $result->getFailedCount());
        $this->assertEquals(5, $result->getTotalCount());
        $this->assertTrue($result->hasFailures());
    }

    public function test_has_failures_returns_false_when_no_failures(): void
    {
        $result = BulkOperationResult::createFor('test');
        $result->addSucceeded('item1');

        $this->assertFalse($result->hasFailures());
    }

    public function test_has_failures_returns_true_when_failures_exist(): void
    {
        $result = BulkOperationResult::createFor('test');
        $result->addFailed('failed_item');

        $this->assertTrue($result->hasFailures());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $result = BulkOperationResult::createFor('import');
        $result
            ->addSucceeded('user1')
            ->addSucceeded('user2')
            ->addFailed('invalid_user');

        $array = $result->toArray();

        $expected = [
            'operation_type' => 'import',
            'successful' => ['user1', 'user2'],
            'failed' => ['invalid_user'],
            'counts' => [
                'successful' => 2,
                'failed' => 1,
                'total' => 3
            ]
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_supports_different_item_types(): void
    {
        $result = BulkOperationResult::createFor('mixed');

        $stringItem = 'string_item';
        $arrayItem = ['id' => 1, 'name' => 'test'];
        $objectItem = (object) ['id' => 2, 'status' => 'failed'];

        $result
            ->addSucceeded($stringItem)
            ->addSucceeded($arrayItem)
            ->addFailed($objectItem);

        $this->assertContains($stringItem, $result->getSucceeded());
        $this->assertContains($arrayItem, $result->getSucceeded());
        $this->assertContains($objectItem, $result->getFailed());
    }

    public function test_empty_result_counts_are_zero(): void
    {
        $result = BulkOperationResult::createFor('empty_test');

        $this->assertEquals(0, $result->getSucceededCount());
        $this->assertEquals(0, $result->getFailedCount());
        $this->assertEquals(0, $result->getTotalCount());
    }

    public function test_total_count_is_sum_of_successful_and_failed(): void
    {
        $result = BulkOperationResult::createFor('sum_test');
        
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
}