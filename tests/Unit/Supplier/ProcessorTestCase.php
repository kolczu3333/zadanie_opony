<?php

declare(strict_types=1);

namespace App\Tests\Unit\Supplier;

use App\Supplier\CsvValueNormalizer;
use App\Supplier\ImportSkipLog;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

abstract class ProcessorTestCase extends TestCase
{
    protected CsvValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new CsvValueNormalizer();
    }

    protected function createSkipLog(): ImportSkipLog
    {
        return new ImportSkipLog(new NullLogger());
    }
}
