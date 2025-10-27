<?php

namespace Carone\Common\BulkOperations;

class BulkOperationResult
{
    private array $succeeded;
    private array $failed;

    public function __construct(array $succeeded = [], array $failed = [])
    {
        $this->succeeded = $succeeded;
        $this->failed = $failed;
    }

    public function getSucceeded(): array
    {
        return $this->succeeded;
    }

    public function getFailed(): array
    {
        return array_map(function ($failedEntry) {
            return is_array($failedEntry) ? $failedEntry['item'] : $failedEntry;
        }, $this->failed);
    }

    public function getFailedWithErrors(): array
    {
        return array_values(array_filter($this->failed, function ($entry) {
            // Keep only entries where 'error' is a non-empty string
            return isset($entry['error']) && $entry['error'] !== null && $entry['error'] !== '';
        }));
    }

    public function addSucceeded($item): self
    {
        $this->succeeded[] = $item;
        return $this;
    }

    public function addFailed($item, ?string $errorMessage = null): self
    {
        $failedEntry = [
            'item' => $item,
            'error' => $errorMessage
        ];
        $this->failed[] = $failedEntry;
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
            'succeeded' => $this->getSucceeded(),
            'failed' => $this->getFailed(),
            'failed_with_errors' => $this->getFailedWithErrors(),
            'counts' => [
                'successful' => $this->getSucceededCount(),
                'failed' => $this->getFailedCount(),
                'total' => $this->getTotalCount()
            ]
        ];
    }
}