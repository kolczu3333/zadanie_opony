<?php

declare(strict_types=1);

namespace App\Tests\Unit\Supplier;

use App\Supplier\LorotomProcessor;
use PHPUnit\Framework\Attributes\DataProvider;

final class LorotomProcessorTest extends ProcessorTestCase
{
    private LorotomProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new LorotomProcessor($this->normalizer);
    }

    public function testSupplierName(): void
    {
        self::assertSame('lorotom', $this->processor->supplierName());
    }

    public function testSupportsLorotom(): void
    {
        self::assertTrue($this->processor->supports('lorotom'));
        self::assertTrue($this->processor->supports('LOROTOM'));
        self::assertFalse($this->processor->supports('trah'));
    }

    public function testProcessesSampleRow(): void
    {
        $file = $this->createTempFile(<<<'TSV'
our_code	producer_code	name	producer	quantity	price	ean
0AU7561489	7561489	AUTOMAT STOPU	POLONEZ/FSO	0	87,00	0007561489
TSV);

        $rows = iterator_to_array($this->processor->process($file, $this->createSkipLog()));

        self::assertCount(1, $rows);
        self::assertSame('0AU7561489', $rows[0]->externalId);
        self::assertSame('7561489', $rows[0]->mpn);
        self::assertSame('POLONEZ/FSO', $rows[0]->producerName);
        self::assertSame('0007561489', $rows[0]->ean);
        self::assertSame('87.00', $rows[0]->price);
        self::assertSame(0, $rows[0]->quantity);
    }

    #[DataProvider('quantityProvider')]
    public function testQuantityCapping(string $quantity, int $expected): void
    {
        $file = $this->createTempFile(<<<TSV
our_code	producer_code	name	producer	quantity	price	ean
TEST001	MPN001	NAME	PRODUCER	{$quantity}	10,00	
TSV);

        $rows = iterator_to_array($this->processor->process($file, $this->createSkipLog()));

        self::assertSame($expected, $rows[0]->quantity);
    }

    public static function quantityProvider(): array
    {
        return [
            'below cap' => ['5', 5],
            'at cap' => ['30', 30],
            'above cap' => ['35', 31],
        ];
    }

    public function testSkipsRowWithInvalidPriceAndLogsReason(): void
    {
        $file = $this->createTempFile(<<<'TSV'
our_code	producer_code	name	producer	quantity	price	ean
VALID01	MPN001	NAME	PRODUCER	1	10,00	123
INVALID01	MPN002	NAME	PRODUCER	1	not-a-price	456
TSV);

        $skipLog = $this->createSkipLog();
        $rows = iterator_to_array($this->processor->process($file, $skipLog));

        self::assertCount(1, $rows);
        self::assertSame('VALID01', $rows[0]->externalId);
        self::assertSame(1, $skipLog->count());
        self::assertSame('INVALID01', $skipLog->all()[0]->externalId);
        self::assertStringContainsString('Invalid price', $skipLog->all()[0]->reason);
        self::assertSame(3, $skipLog->all()[0]->line);
    }

    public function testEmptyEanBecomesNull(): void
    {
        $file = $this->createTempFile(<<<'TSV'
our_code	producer_code	name	producer	quantity	price	ean
TEST002	MPN002	NAME	PRODUCER	1	10,00	
TSV);

        $rows = iterator_to_array($this->processor->process($file, $this->createSkipLog()));

        self::assertNull($rows[0]->ean);
    }

    private function createTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lorotom_');
        file_put_contents($path, $content);

        return $path;
    }
}
