<?php

declare(strict_types=1);

namespace App\Repository;

use App\Supplier\Dto\StockRowDto;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Portable bulk upsert (MySQL / PostgreSQL): one SELECT, then bulk INSERT and/or bulk UPDATE per batch.
 */
final class StockItemBulkUpserter implements StockBulkUpserterInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param list<StockRowDto> $rows
     *
     * @return array{created: int, updated: int}
     */
    public function upsertBatch(string $supplier, array $rows): array
    {
        if ($rows === []) {
            return ['created' => 0, 'updated' => 0];
        }

        $supplier = strtolower($supplier);
        $rows = $this->deduplicateByExternalId($rows);

        return $this->connection->transactional(function () use ($supplier, $rows): array {
            $externalIds = [];
            foreach ($rows as $row) {
                $externalIds[] = $row->externalId;
            }

            $existingIds = $this->fetchExistingExternalIds($supplier, $externalIds);

            $toInsert = [];
            $toUpdate = [];
            foreach ($rows as $row) {
                if (isset($existingIds[$row->externalId])) {
                    $toUpdate[] = $row;
                } else {
                    $toInsert[] = $row;
                }
            }

            if ($toInsert !== []) {
                $this->bulkInsert($supplier, $toInsert);
            }

            if ($toUpdate !== []) {
                $this->bulkUpdate($supplier, $toUpdate);
            }

            return [
                'created' => \count($toInsert),
                'updated' => \count($toUpdate),
            ];
        });
    }

    /**
     * @param list<StockRowDto> $rows
     *
     * @return list<StockRowDto>
     */
    private function deduplicateByExternalId(array $rows): array
    {
        $byExternalId = [];
        foreach ($rows as $row) {
            $byExternalId[$row->externalId] = $row;
        }

        return array_values($byExternalId);
    }

    /**
     * @param list<StockRowDto> $rows
     */
    private function bulkInsert(string $supplier, array $rows): void
    {
        $placeholders = [];
        $params = [];
        foreach ($rows as $row) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?)';
            $params[] = $supplier;
            $params[] = $row->externalId;
            $params[] = $row->mpn;
            $params[] = $row->producerName;
            $params[] = $row->ean;
            $params[] = $row->price;
            $params[] = $row->quantity;
        }

        $sql = 'INSERT INTO stock_item (supplier, external_id, mpn, producer_name, ean, price, quantity) VALUES '
            . implode(', ', $placeholders);

        $this->connection->executeStatement($sql, $params);
    }

    /**
     * @param list<StockRowDto> $rows
     */
    private function bulkUpdate(string $supplier, array $rows): void
    {
        $externalIds = [];
        $params = [];
        $types = [];

        $mpnCase = $this->appendCasePairs('external_id', $rows, static fn (StockRowDto $r): string => $r->mpn, $params, $types);
        $producerCase = $this->appendCasePairs('external_id', $rows, static fn (StockRowDto $r): string => $r->producerName, $params, $types);
        $eanCase = $this->appendCasePairs('external_id', $rows, static fn (StockRowDto $r): ?string => $r->ean, $params, $types);
        $priceCase = $this->appendCasePairs('external_id', $rows, static fn (StockRowDto $r): string => $r->price, $params, $types);
        $quantityCase = $this->appendCasePairs('external_id', $rows, static fn (StockRowDto $r): int => $r->quantity, $params, $types, true);

        foreach ($rows as $row) {
            $externalIds[] = $row->externalId;
        }

        $params[] = $supplier;
        $params[] = $externalIds;
        $types[] = ParameterType::STRING;
        $types[] = ArrayParameterType::STRING;

        $sql = sprintf(
            'UPDATE stock_item SET mpn = %s, producer_name = %s, ean = %s, price = %s, quantity = %s WHERE supplier = ? AND external_id IN (?)',
            $mpnCase,
            $producerCase,
            $eanCase,
            $priceCase,
            $quantityCase,
        );

        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * @param list<StockRowDto>       $rows
     * @param callable(StockRowDto): mixed $valueExtractor
     * @param list<mixed>             $params
     * @param list<int|string>        $types
     */
    private function appendCasePairs(
        string $keyColumn,
        array $rows,
        callable $valueExtractor,
        array &$params,
        array &$types,
        bool $integerValue = false,
    ): string {
        $parts = ['CASE ' . $keyColumn];
        $valueType = $integerValue ? ParameterType::INTEGER : ParameterType::STRING;

        foreach ($rows as $row) {
            $parts[] = 'WHEN ? THEN ?';
            $params[] = $row->externalId;
            $params[] = $valueExtractor($row);
            $types[] = ParameterType::STRING;
            $types[] = $valueType;
        }

        $parts[] = 'END';

        return implode(' ', $parts);
    }

    /**
     * @param list<string> $externalIds
     *
     * @return array<string, true>
     */
    private function fetchExistingExternalIds(string $supplier, array $externalIds): array
    {
        if ($externalIds === []) {
            return [];
        }

        /** @var list<string> $result */
        $result = $this->connection->executeQuery(
            'SELECT external_id FROM stock_item WHERE supplier = ? AND external_id IN (?)',
            [$supplier, $externalIds],
            [ParameterType::STRING, ArrayParameterType::STRING],
        )->fetchFirstColumn();

        $map = [];
        foreach ($result as $externalId) {
            $map[(string) $externalId] = true;
        }

        return $map;
    }
}
