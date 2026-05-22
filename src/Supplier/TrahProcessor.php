<?php

declare(strict_types=1);

namespace App\Supplier;

use App\Supplier\Dto\StockRowDto;

final class TrahProcessor extends AbstractSupplierProcessor
{
    private const SKIP_PRODUCER = 'NARZEDZIA WARSZTAT';
    private const MAX_QUANTITY = 10;
    private const CAPPED_QUANTITY = 11;

    public function supplierName(): string
    {
        return 'trah';
    }

    public function process(string $filePath, ImportSkipLoggerInterface $skipLog): iterable
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Cannot open file: %s', $filePath));
        }

        try {
            $lineNumber = 0;

            while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
                ++$lineNumber;

                if ($row === [null] || $row === false || \count($row) < 6) {
                    $skipLog->skip($lineNumber, $this->extractExternalId($row), 'Incomplete row (expected 6 columns)');

                    continue;
                }

                $externalId = trim($row[0], " \t\n\r\0\x0B\"");
                $producerName = trim($row[5], " \t\n\r\0\x0B\"");
                if ($producerName === self::SKIP_PRODUCER) {
                    $skipLog->skip($lineNumber, $externalId !== '' ? $externalId : null, 'Producer excluded: NARZEDZIA WARSZTAT');

                    continue;
                }

                $price = $this->normalizer->tryNormalizePrice($row[2]);
                if ($price === null) {
                    $skipLog->skip($lineNumber, $externalId !== '' ? $externalId : null, sprintf('Invalid price: "%s"', trim($row[2], " \t\n\r\0\x0B\"")));

                    continue;
                }

                yield new StockRowDto(
                    externalId: $externalId,
                    mpn: trim($row[3], " \t\n\r\0\x0B\""),
                    producerName: $producerName,
                    ean: $this->normalizer->normalizeEan($row[4] ?? null),
                    price: $price,
                    quantity: self::normalizeQuantity($row[1]),
                );
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<int, string|null>|false $row
     */
    private function extractExternalId(array|false $row): ?string
    {
        if ($row === false || !isset($row[0])) {
            return null;
        }

        $externalId = trim((string) $row[0], " \t\n\r\0\x0B\"");

        return $externalId === '' ? null : $externalId;
    }

    private static function normalizeQuantity(string $value): int
    {
        $trimmed = trim($value);

        if ($trimmed === '>10' || $trimmed === '> 10') {
            return self::CAPPED_QUANTITY;
        }

        $quantity = (int) $trimmed;

        if ($quantity > self::MAX_QUANTITY) {
            return self::CAPPED_QUANTITY;
        }

        return $quantity;
    }
}
