<?php

declare(strict_types=1);

namespace App\Supplier\Import;

use App\Supplier\Contract\ImportSkipLoggerInterface;
use Psr\Log\LoggerInterface;

final class ImportSkipLog implements ImportSkipLoggerInterface
{
    /** @var list<ImportSkipRecord> */
    private array $skipped = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function skip(int $line, ?string $externalId, string $reason): void
    {
        $this->skipped[] = new ImportSkipRecord($line, $externalId, $reason);

        $this->logger->warning('Import row skipped', [
            'line' => $line,
            'external_id' => $externalId,
            'reason' => $reason,
        ]);
    }

    /**
     * @return list<ImportSkipRecord>
     */
    public function all(): array
    {
        return $this->skipped;
    }

    public function count(): int
    {
        return \count($this->skipped);
    }
}
