<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\StockBulkUpserterInterface;
use App\Supplier\Dto\StockRowDto;
use App\Supplier\ImportSkipLogFactory;
use App\Supplier\SupplierRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-stock',
    description: 'Import stock data from a supplier CSV file',
)]
final class ImportStockCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly SupplierRegistry $supplierRegistry,
        private readonly StockBulkUpserterInterface $bulkUpserter,
        private readonly ImportSkipLogFactory $skipLogFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filepath', InputArgument::REQUIRED, 'Absolute path to the CSV file')
            ->addArgument(
                'supplier',
                InputArgument::REQUIRED,
                sprintf('Supplier name (%s)', implode(', ', $this->supplierRegistry->supportedSupplierNames())),
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filepath = $input->getArgument('filepath');
        $supplier = strtolower((string) $input->getArgument('supplier'));

        if (!is_readable($filepath)) {
            $io->error(sprintf('File not found or not readable: %s', $filepath));

            return Command::FAILURE;
        }

        try {
            $processor = $this->supplierRegistry->get($supplier);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $skipLog = $this->skipLogFactory->create();
        $imported = 0;
        $updated = 0;
        $processed = 0;
        /** @var StockRowDto[] $batch */
        $batch = [];

        foreach ($processor->process($filepath, $skipLog) as $row) {
            ++$processed;
            $batch[] = $row;

            if (\count($batch) >= self::BATCH_SIZE) {
                $stats = $this->bulkUpserter->upsertBatch($supplier, $batch);
                $imported += $stats['created'];
                $updated += $stats['updated'];
                $batch = [];
            }
        }

        if ($batch !== []) {
            $stats = $this->bulkUpserter->upsertBatch($supplier, $batch);
            $imported += $stats['created'];
            $updated += $stats['updated'];
        }

        $io->success(sprintf(
            'Import finished. Processed: %d, created: %d, updated: %d, skipped: %d',
            $processed,
            $imported,
            $updated,
            $skipLog->count(),
        ));

        if ($skipLog->count() > 0) {
            $io->section('Skipped rows');
            foreach ($skipLog->all() as $skipped) {
                $io->writeln(sprintf(
                    '  line %d | external_id: %s | reason: %s',
                    $skipped->line,
                    $skipped->externalId ?? 'n/a',
                    $skipped->reason,
                ));
            }
        }

        return Command::SUCCESS;
    }
}
