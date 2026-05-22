<?php

declare(strict_types=1);

namespace App\Supplier\Import;

final readonly class ImportSkipRecord
{
    public function __construct(
        public int $line,
        public ?string $externalId,
        public string $reason,
    ) {
    }
}
