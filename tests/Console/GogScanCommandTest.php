<?php

namespace Artryazanov\GogScanner\Tests\Console;

use Artryazanov\GogScanner\Jobs\ScanPageJob;
use Artryazanov\GogScanner\Tests\TestCase;
use Illuminate\Support\Facades\Bus;

class GogScanCommandTest extends TestCase
{
    public function test_dispatches_scan_page_job_with_page_argument()
    {
        Bus::fake();

        $this->artisan('gog:scan', ['page' => 5])
            ->expectsOutput('Dispatched ScanPageJob for page 5')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(ScanPageJob::class, 1);
    }

    public function test_default_page_is_one()
    {
        Bus::fake();

        $this->artisan('gog:scan')
            ->expectsOutput('Dispatched ScanPageJob for page 1')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(ScanPageJob::class, 1);
    }
}
