<?php

declare(strict_types=1);

namespace App\Repository;

use App\Supplier\Dto\StockRowDto;

interface StockBulkUpserterInterface
{
    /**
     * @param list<StockRowDto> $rows
     *
     * @return array{created: int, updated: int}
     */
    public function upsertBatch(string $supplier, array $rows): array;
}
