<?php

declare(strict_types=1);

namespace App\Supplier\Normalizer;

final class CsvValueNormalizer
{
    public function tryNormalizePrice(string $value): ?string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return $normalized;
    }

    public function normalizeEan(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $ean = trim($value);

        return $ean === '' ? null : $ean;
    }
}
