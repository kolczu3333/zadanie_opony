<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\GetStocksQuery;
use App\Entity\StockItem;
use App\Exception\InvalidStockSearchQueryException;
use App\Repository\StockItemRepository;

final class StockSearchService
{
    private const MISSING_FILTER_MESSAGE = 'At least one query parameter is required: mpn or ean.';

    public function __construct(
        private readonly StockItemRepository $stockItemRepository,
    ) {
    }

    /**
     * @return list<StockItem>
     */
    public function search(GetStocksQuery $query): array
    {
        if ($query->mpn === null && $query->ean === null) {
            throw new InvalidStockSearchQueryException(self::MISSING_FILTER_MESSAGE);
        }

        return $this->stockItemRepository->findByFilters($query->mpn, $query->ean);
    }
}
