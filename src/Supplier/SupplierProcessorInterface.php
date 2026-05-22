<?php

declare(strict_types=1);

namespace App\Supplier;

use App\Supplier\Dto\StockRowDto;

interface SupplierProcessorInterface
{
    /**
     * Canonical supplier key used in CLI and database (e.g. "lorotom").
     */
    public function supplierName(): string;

    public function supports(string $supplier): bool;

    /**
     * @return iterable<StockRowDto>
     */
    public function process(string $filePath, ImportSkipLoggerInterface $skipLog): iterable;
}
