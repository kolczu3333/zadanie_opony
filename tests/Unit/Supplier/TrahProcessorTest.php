<?php

declare(strict_types=1);

namespace App\Tests\Unit\Supplier;

use App\Supplier\TrahProcessor;
use PHPUnit\Framework\Attributes\DataProvider;

final class TrahProcessorTest extends ProcessorTestCase
{
    private TrahProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new TrahProcessor($this->normalizer);
    }

    public function testSupplierName(): void
    {
        self::assertSame('trah', $this->processor->supplierName());
    }

    public function testSupportsTrah(): void
    {
        self::assertTrue($this->processor->supports('trah'));
        self::assertTrue($this->processor->supports('TRAH'));
        self::assertFalse($this->processor->supports('lorotom'));
    }

    public function testProcessesSampleRow(): void
    {
        $file = $this->createTempFile('"000 014";>10;10,34;19-598;5905694015970;AMTRA' . "\n");

        $rows = iterator_to_array($this->processor->process($file, $this->createSkipLog()));

        self::assertCount(1, $rows);
        self::assertSame('000 014', $rows[0]->externalId);
        self::assertSame('19-598', $rows[0]->mpn);
        self::assertSame('AMTRA', $rows[0]->producerName);
        self::assertSame('5905694015970', $rows[0]->ean);
        self::assertSame('10.34', $rows[0]->price);
        self::assertSame(11, $rows[0]->quantity);
    }

    #[DataProvider('quantityProvider')]
    public function testQuantityCapping(string $quantity, int $expected): void
    {
        $file = $this->createTempFile(sprintf(
            '"EXT001";%s;5,00;MPN001;1234567890123;PRODUCER' . "\n",
            $quantity,
        ));

        $rows = iterator_to_array($this->processor->process($file, $this->createSkipLog()));

        self::assertSame($expected, $rows[0]->quantity);
    }

    public static function quantityProvider(): array
    {
        return [
            'greater than ten string' => ['>10', 11],
            'numeric above cap' => ['15', 11],
            'at cap' => ['10', 10],
            'below cap' => ['7', 7],
        ];
    }

    public function testSkipsRowWithInvalidPriceAndLogsReason(): void
    {
        $file = $this->createTempFile(
            '"VALID01";1;5,00;MPN001;123;PRODUCER' . "\n" .
            '"INVALID01";1;bad-price;MPN002;456;PRODUCER' . "\n",
        );

        $skipLog = $this->createSkipLog();
        $rows = iterator_to_array($this->processor->process($file, $skipLog));

        self::assertCount(1, $rows);
        self::assertSame('VALID01', $rows[0]->externalId);
        self::assertSame(1, $skipLog->count());
        self::assertSame('INVALID01', $skipLog->all()[0]->externalId);
        self::assertStringContainsString('Invalid price', $skipLog->all()[0]->reason);
    }

    public function testSkipsNarzedziaWarsztatAndLogsReason(): void
    {
        $file = $this->createTempFile(
            '"SKIP001";1;1,00;MPN;123;NARZEDZIA WARSZTAT' . "\n" .
            '"KEEP001";1;2,00;MPN2;456;VALID PRODUCER' . "\n",
        );

        $skipLog = $this->createSkipLog();
        $rows = iterator_to_array($this->processor->process($file, $skipLog));

        self::assertCount(1, $rows);
        self::assertSame('KEEP001', $rows[0]->externalId);
        self::assertSame(1, $skipLog->count());
        self::assertSame('SKIP001', $skipLog->all()[0]->externalId);
        self::assertStringContainsString('NARZEDZIA WARSZTAT', $skipLog->all()[0]->reason);
    }

    private function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'trah_');
        file_put_contents($path, $content);

        return $path;
    }
}
