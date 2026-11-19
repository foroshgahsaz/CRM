<?php

namespace App\Services;

class UserBulkImportResult
{
    /**
     * @param  list<array{row: int, message: string}>  $skipped
     * @param  list<array{row: int, message: string}>  $failures
     */
    public function __construct(
        public int $created = 0,
        public array $skipped = [],
        public array $failures = [],
    ) {}

    public function hasProblems(): bool
    {
        return $this->skipped !== [] || $this->failures !== [];
    }
}