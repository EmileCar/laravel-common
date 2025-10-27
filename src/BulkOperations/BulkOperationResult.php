<?php

namespace Carone\Common\BulkOperations;

class BulkOperationResult
{
    private string $operationType;
    private array $succeeded;
    private array $failed;

    private function __construct(string $operationType, array $succeeded = [], array $failed = [])
    {
        $this->operationType = $operationType;
        $this->succeeded = $succeeded;
        $this->failed = $failed;
    }

    public static function createFor(string $operationType, array $succeeded = [], array $failed = []): self
    {
        return new self($operationType, $succeeded, $failed);
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }

    public function getSucceeded(): array
    {
        return $this->succeeded;
    }

    public function getFailed(): array
    {
        return $this->failed;
    }

    public function addSucceeded($item): self
    {
        $this->succeeded[] = $item;
        return $this;
    }

    public function addFailed($item): self
    {
        $this->failed[] = $item;
        return $this;
    }

    public function getSucceededCount(): int
    {
        return count($this->succeeded);
    }

    public function getFailedCount(): int
    {
        return count($this->failed);
    }

    public function getTotalCount(): int
    {
        return $this->getSucceededCount() + $this->getFailedCount();
    }

    public function hasFailures(): bool
    {
        return !empty($this->failed);
    }

    public function toArray(): array
    {
        return [
            'operation_type' => $this->operationType,
            'successful' => $this->succeeded,
            'failed' => $this->failed,
            'counts' => [
                'successful' => $this->getSucceededCount(),
                'failed' => $this->getFailedCount(),
                'total' => $this->getTotalCount()
            ]
        ];
    }
}