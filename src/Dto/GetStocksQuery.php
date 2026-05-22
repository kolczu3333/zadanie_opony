<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class GetStocksQuery
{
    public function __construct(
        public ?string $mpn = null,
        public ?string $ean = null,
    ) {
    }
}
