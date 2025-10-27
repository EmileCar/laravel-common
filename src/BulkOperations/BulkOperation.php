<?php

namespace Carone\Common\BulkOperations;

use Closure;
use Exception;

class BulkOperation
{
    private Closure $operation;

    private function __construct(Closure $operation)
    {
        $this->operation = $operation;
    }

    public static function create(Closure $operation): self
    {
        return new self($operation);
    }

    /**
     * Execute the bulk operation on the provided subjects
     *
     * @param array $subjects The items to process
     * @return BulkOperationResult
     */
    public function execute(
        array $subjects,
        ?Closure $successHandler = null,
        ?Closure $failureHandler = null
    ): BulkOperationResult
    {
        $result = new BulkOperationResult();

        foreach ($subjects as $subject) {
            try {
                call_user_func($this->operation, $subject);
                $result->addSucceeded($subject);
                if ($successHandler) {
                    call_user_func($successHandler, $subject);
                }
            } catch (Exception $e) {
                $result->addFailed($subject, $e->getMessage());
                if ($failureHandler) {
                    call_user_func($failureHandler, $subject, $e);
                }
            }
        }

        return $result;
    }

    /**
     * Execute the bulk operation and return only successful items
     *
     * @param array $subjects The items to process
     * @return array Array of successful subjects
     */
    public function executeAndGetSuccessful(array $subjects): array
    {
        $result = $this->execute($subjects);
        return $result->getSucceeded();
    }

    /**
     * Execute the bulk operation and return only failed items
     *
     * @param array $subjects The items to process
     * @return array Array of failed items with error messages
     */
    public function executeAndGetFailed(array $subjects): array
    {
        $result = $this->execute($subjects);
        // Return failed entries with error messages
        return $result->getFailedWithErrors();
    }
}