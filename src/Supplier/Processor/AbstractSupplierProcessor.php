<?php

declare(strict_types=1);

namespace App\Supplier\Processor;

use App\Supplier\Contract\SupplierProcessorInterface;
use App\Supplier\Normalizer\CsvValueNormalizer;

abstract class AbstractSupplierProcessor implements SupplierProcessorInterface
{
    public function __construct(
        protected readonly CsvValueNormalizer $normalizer,
    ) {
    }

    public function supports(string $supplier): bool
    {
        return strtolower($supplier) === $this->supplierName();
    }
}
