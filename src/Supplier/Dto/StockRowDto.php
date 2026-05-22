<?php

declare(strict_types=1);

namespace App\Supplier\Dto;

final readonly class StockRowDto
{
    public function __construct(
        public string $externalId,
        public string $mpn,
        public string $producerName,
        public ?string $ean,
        public string $price,
        public int $quantity,
    ) {
    }
}
