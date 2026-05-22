<?php

declare(strict_types=1);

namespace App\Tests\Unit\Supplier;

use App\Supplier\Normalizer\CsvValueNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CsvValueNormalizerTest extends TestCase
{
    private CsvValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CsvValueNormalizer();
    }

    #[DataProvider('validPriceProvider')]
    public function testTryNormalizePriceAcceptsValidValues(string $input, string $expected): void
    {
        self::assertSame($expected, $this->normalizer->tryNormalizePrice($input));
    }

    #[DataProvider('invalidPriceProvider')]
    public function testTryNormalizePriceRejectsInvalidValues(string $input): void
    {
        self::assertNull($this->normalizer->tryNormalizePrice($input));
    }

    public static function validPriceProvider(): array
    {
        return [
            'comma decimal' => ['10,34', '10.34'],
            'dot decimal' => ['87.00', '87.00'],
            'integer' => ['5', '5'],
        ];
    }

    public static function invalidPriceProvider(): array
    {
        return [
            'text' => ['invalid'],
            'empty' => [''],
            'mixed alphanumeric' => ['12abc'],
        ];
    }
}
