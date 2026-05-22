<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Factory\StockItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class StockControllerTest extends WebTestCase
{
    use Factories;
    use ResetDatabase;

    public function testReturnsBadRequestWhenNoQueryParams(): void
    {
        $client = static::createClient();
        $client->request('GET', '/get-stocks');

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('At least one query parameter is required: mpn or ean.', $data['error']);
    }

    public function testReturnsStockByMpn(): void
    {
        $client = static::createClient();

        StockItemFactory::createOne([
            'supplier' => 'trah',
            'externalId' => '000 014',
            'mpn' => '19-598',
            'producerName' => 'AMTRA',
            'ean' => '5905694015970',
            'price' => '10.34',
            'quantity' => 11,
        ]);

        $client->request('GET', '/get-stocks?mpn=19-598');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $data);
        self::assertSame('19-598', $data[0]['mpn']);
        self::assertSame('AMTRA', $data[0]['producerName']);
        self::assertSame('5905694015970', $data[0]['ean']);
        self::assertSame('10.34', $data[0]['price']);
        self::assertSame(11, $data[0]['quantity']);
        self::assertSame('trah', $data[0]['supplier']);
        self::assertSame('000 014', $data[0]['externalId']);
    }

    public function testReturnsStockByEan(): void
    {
        $client = static::createClient();

        StockItemFactory::createOne([
            'mpn' => 'MPN-EAN-TEST',
            'ean' => '1234567890123',
        ]);

        $client->request('GET', '/get-stocks?ean=1234567890123');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $data);
        self::assertSame('1234567890123', $data[0]['ean']);
    }

    public function testReturnsEmptyArrayForUnknownMpn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/get-stocks?mpn=nonexistent');

        self::assertResponseIsSuccessful();
        self::assertSame('[]', $client->getResponse()->getContent());
    }

    public function testReturnsStockWhenMpnAndEanMatchSameItem(): void
    {
        $client = static::createClient();

        StockItemFactory::createOne([
            'mpn' => '19-598',
            'ean' => '5905694015970',
            'producerName' => 'AMTRA',
        ]);

        $client->request('GET', '/get-stocks?mpn=19-598&ean=5905694015970');

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $data);
        self::assertSame('19-598', $data[0]['mpn']);
        self::assertSame('5905694015970', $data[0]['ean']);
    }

    public function testReturnsEmptyWhenMpnAndEanDoNotMatchSameItem(): void
    {
        $client = static::createClient();

        StockItemFactory::createOne([
            'mpn' => 'MPN-ONLY',
            'ean' => '1111111111111',
        ]);
        StockItemFactory::createOne([
            'mpn' => 'MPN-OTHER',
            'ean' => '2222222222222',
        ]);

        $client->request('GET', '/get-stocks?mpn=MPN-ONLY&ean=2222222222222');

        self::assertResponseIsSuccessful();
        self::assertSame('[]', $client->getResponse()->getContent());
    }
}
