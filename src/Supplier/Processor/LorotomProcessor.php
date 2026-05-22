<?php

declare(strict_types=1);

namespace App\Supplier\Processor;

use App\Supplier\Contract\ImportSkipLoggerInterface;
use App\Supplier\Dto\StockRowDto;

final class LorotomProcessor extends AbstractSupplierProcessor
{
    private const MAX_QUANTITY = 30;
    private const CAPPED_QUANTITY = 31;

    public function supplierName(): string
    {
        return 'lorotom';
    }

    public function process(string $filePath, ImportSkipLoggerInterface $skipLog): iterable
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Cannot open file: %s', $filePath));
        }

        try {
            fgetcsv($handle, 0, "\t", '"', ''); // skip header
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 0, "\t", '"', '')) !== false) {
                ++$lineNumber;

                if ($row === [null] || $row === false || \count($row) < 7) {
                    $skipLog->skip($lineNumber, $this->extractExternalId($row), 'Incomplete row (expected 7 columns)');

                    continue;
                }

                $externalId = trim($row[0]);
                $price = $this->normalizer->tryNormalizePrice($row[5]);
                if ($price === null) {
                    $skipLog->skip($lineNumber, $externalId, sprintf('Invalid price: "%s"', trim($row[5])));

                    continue;
                }

                yield new StockRowDto(
                    externalId: $externalId,
                    mpn: trim($row[1]),
                    producerName: trim($row[3]),
                    ean: $this->normalizer->normalizeEan($row[6] ?? null),
                    price: $price,
                    quantity: self::normalizeQuantity($row[4]),
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

        $externalId = trim((string) $row[0]);

        return $externalId === '' ? null : $externalId;
    }

    private static function normalizeQuantity(string $value): int
    {
        $quantity = (int) trim($value);

        if ($quantity > self::MAX_QUANTITY) {
            return self::CAPPED_QUANTITY;
        }

        return $quantity;
    }
}
