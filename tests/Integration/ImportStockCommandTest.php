<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Repository\StockItemRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ImportStockCommandTest extends KernelTestCase
{
    use ResetDatabase;

    private const FIXTURES_DIR = __DIR__ . '/../Fixtures';

    public function testImportLorotomCreatesRecordsWithBusinessRules(): void
    {
        self::bootKernel();
        $commandTester = $this->runImportCommand(
            self::FIXTURES_DIR . '/lorotom_import.csv',
            'lorotom',
        );

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('Processed: 2, created: 2, updated: 0, skipped: 1', $commandTester->getDisplay());
        self::assertStringContainsString('Skipped rows', $commandTester->getDisplay());
        self::assertStringContainsString('IMP003', $commandTester->getDisplay());
        self::assertStringContainsString('Invalid price', $commandTester->getDisplay());

        $repository = self::getContainer()->get(StockItemRepository::class);

        self::assertNull($repository->findOneBySupplierAndExternalId('lorotom', 'IMP003'));

        $item1 = $repository->findOneBySupplierAndExternalId('lorotom', 'IMP001');
        self::assertNotNull($item1);
        self::assertSame('MPN001', $item1->getMpn());
        self::assertSame('PROD_A', $item1->getProducerName());
        self::assertSame('5900000000001', $item1->getEan());
        self::assertSame('10.50', $item1->getPrice());
        self::assertSame(5, $item1->getQuantity());

        $item2 = $repository->findOneBySupplierAndExternalId('lorotom', 'IMP002');
        self::assertNotNull($item2);
        self::assertNull($item2->getEan());
        self::assertSame('20.00', $item2->getPrice());
        self::assertSame(31, $item2->getQuantity());
    }

    public function testImportTrahSkipsExcludedProducerAndCapsQuantity(): void
    {
        self::bootKernel();
        $commandTester = $this->runImportCommand(
            self::FIXTURES_DIR . '/trah_import.csv',
            'trah',
        );

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('Processed: 1, created: 1, updated: 0, skipped: 1', $commandTester->getDisplay());
        self::assertStringContainsString('SKIP01', $commandTester->getDisplay());
        self::assertStringContainsString('NARZEDZIA WARSZTAT', $commandTester->getDisplay());

        $repository = self::getContainer()->get(StockItemRepository::class);

        self::assertNull($repository->findOneBySupplierAndExternalId('trah', 'SKIP01'));

        $item = $repository->findOneBySupplierAndExternalId('trah', 'TRAH01');
        self::assertNotNull($item);
        self::assertSame('19-598', $item->getMpn());
        self::assertSame('AMTRA', $item->getProducerName());
        self::assertSame('5905694015970', $item->getEan());
        self::assertSame('10.34', $item->getPrice());
        self::assertSame(11, $item->getQuantity());
    }

    public function testImportSkipsRowsWithInvalidPrice(): void
    {
        self::bootKernel();
        $fixture = tempnam(sys_get_temp_dir(), 'lorotom_invalid_');
        file_put_contents($fixture, <<<'TSV'
our_code	producer_code	name	producer	quantity	price	ean
OK001	MPN001	NAME	PROD	1	9,99	111
BAD001	MPN002	NAME	PROD	1	xx,yy	222
TSV);

        try {
            $commandTester = $this->runImportCommand($fixture, 'lorotom');
            $commandTester->assertCommandIsSuccessful();
            self::assertStringContainsString('Processed: 1, created: 1, updated: 0, skipped: 1', $commandTester->getDisplay());
            self::assertStringContainsString('BAD001', $commandTester->getDisplay());

            $repository = self::getContainer()->get(StockItemRepository::class);
            self::assertNotNull($repository->findOneBySupplierAndExternalId('lorotom', 'OK001'));
            self::assertNull($repository->findOneBySupplierAndExternalId('lorotom', 'BAD001'));
        } finally {
            unlink($fixture);
        }
    }

    public function testImportUpsertUpdatesExistingRecords(): void
    {
        self::bootKernel();
        $filepath = self::FIXTURES_DIR . '/lorotom_import.csv';

        $firstRun = $this->runImportCommand($filepath, 'lorotom');
        $firstRun->assertCommandIsSuccessful();

        $secondRun = $this->runImportCommand($filepath, 'lorotom');
        $secondRun->assertCommandIsSuccessful();
        self::assertStringContainsString('Processed: 2, created: 0, updated: 2, skipped: 1', $secondRun->getDisplay());

        $repository = self::getContainer()->get(StockItemRepository::class);
        self::assertCount(2, $repository->findBy(['supplier' => 'lorotom']));
    }

    public function testImportFailsForMissingFile(): void
    {
        self::bootKernel();
        $commandTester = $this->runImportCommand('/nonexistent/file.csv', 'lorotom');

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('File not found or not readable', $commandTester->getDisplay());
    }

    public function testImportFailsForUnknownSupplier(): void
    {
        self::bootKernel();
        $commandTester = $this->runImportCommand(
            self::FIXTURES_DIR . '/lorotom_import.csv',
            'unknown',
        );

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('Unsupported supplier', $commandTester->getDisplay());
    }

    private function runImportCommand(string $filepath, string $supplier): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('app:import-stock');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'filepath' => $filepath,
            'supplier' => $supplier,
        ]);

        return $commandTester;
    }
}
