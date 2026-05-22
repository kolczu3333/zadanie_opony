<?php

declare(strict_types=1);

namespace App\Supplier\Registry;

use App\Supplier\Contract\SupplierProcessorInterface;

final class SupplierRegistry
{
    /**
     * @param iterable<SupplierProcessorInterface> $processors
     */
    public function __construct(
        private readonly iterable $processors,
    ) {
    }

    /**
     * @return list<string>
     */
    public function supportedSupplierNames(): array
    {
        $names = [];
        foreach ($this->processors as $processor) {
            $names[] = $processor->supplierName();
        }

        sort($names);

        return $names;
    }

    public function get(string $supplier): SupplierProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($supplier)) {
                return $processor;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Unsupported supplier "%s". Supported: %s.',
            $supplier,
            implode(', ', $this->supportedSupplierNames()),
        ));
    }
}
