<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\StockItem;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<StockItem>
 */
final class StockItemFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return StockItem::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'supplier' => 'trah',
            'externalId' => self::faker()->uuid(),
            'mpn' => '19-598',
            'producerName' => 'AMTRA',
            'ean' => '5905694015970',
            'price' => '10.34',
            'quantity' => 11,
        ];
    }
}
