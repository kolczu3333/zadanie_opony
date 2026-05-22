<?php

declare(strict_types=1);

namespace App\Supplier\Contract;

use App\Supplier\Import\ImportSkipRecord;

interface ImportSkipLoggerInterface
{
    public function skip(int $line, ?string $externalId, string $reason): void;

    public function count(): int;

    /**
     * @return list<ImportSkipRecord>
     */
    public function all(): array;
}
