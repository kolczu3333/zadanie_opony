<?php

declare(strict_types=1);

namespace App\Supplier\Import;

use App\Supplier\Contract\ImportSkipLoggerInterface;
use Psr\Log\LoggerInterface;

final class ImportSkipLogFactory
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(): ImportSkipLoggerInterface
    {
        return new ImportSkipLog($this->logger);
    }
}
