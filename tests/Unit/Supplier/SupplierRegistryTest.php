<?php

declare(strict_types=1);

namespace App\Tests\Unit\Supplier;

use App\Supplier\CsvValueNormalizer;
use App\Supplier\LorotomProcessor;
use App\Supplier\SupplierRegistry;
use App\Supplier\TrahProcessor;
use PHPUnit\Framework\TestCase;

final class SupplierRegistryTest extends TestCase
{
    public function testListsSupportedSuppliers(): void
    {
        $normalizer = new CsvValueNormalizer();
        $registry = new SupplierRegistry([
            new LorotomProcessor($normalizer),
            new TrahProcessor($normalizer),
        ]);

        self::assertSame(['lorotom', 'trah'], $registry->supportedSupplierNames());
    }

    public function testUnknownSupplierMessageIncludesSupportedList(): void
    {
        $registry = new SupplierRegistry([
            new LorotomProcessor(new CsvValueNormalizer()),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supported: lorotom');

        $registry->get('unknown');
    }
}
