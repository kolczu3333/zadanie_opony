<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\GetStocksQuery;
use App\Entity\StockItem;
use App\Exception\InvalidStockSearchQueryException;
use App\Repository\StockItemRepository;
use App\Service\StockSearchService;
use PHPUnit\Framework\TestCase;

final class StockSearchServiceTest extends TestCase
{
    public function testSearchRequiresAtLeastOneFilter(): void
    {
        $repository = $this->createMock(StockItemRepository::class);
        $repository->expects(self::never())->method('findByFilters');

        $service = new StockSearchService($repository);

        $this->expectException(InvalidStockSearchQueryException::class);
        $this->expectExceptionMessage('At least one query parameter is required: mpn or ean.');

        $service->search(new GetStocksQuery());
    }

    public function testSearchDelegatesToRepository(): void
    {
        $item = new StockItem();

        $repository = $this->createMock(StockItemRepository::class);
        $repository
            ->expects(self::once())
            ->method('findByFilters')
            ->with('19-598', null)
            ->willReturn([$item]);

        $service = new StockSearchService($repository);
        $result = $service->search(new GetStocksQuery(mpn: '19-598'));

        self::assertSame([$item], $result);
    }
}
