<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\StockItem;

final class StockItemSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(StockItem $item): array
    {
        return [
            'id' => $item->getId(),
            'supplier' => $item->getSupplier(),
            'externalId' => $item->getExternalId(),
            'mpn' => $item->getMpn(),
            'producerName' => $item->getProducerName(),
            'ean' => $item->getEan(),
            'price' => $item->getPrice(),
            'quantity' => $item->getQuantity(),
        ];
    }

    /**
     * @param list<StockItem> $items
     *
     * @return list<array<string, mixed>>
     */
    public function serializeMany(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = $this->serialize($item);
        }

        return $result;
    }
}
