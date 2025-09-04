<?php

namespace Artryazanov\GogScanner\Tests\Jobs;

use Artryazanov\GogScanner\Jobs\BaseScanJob;
use Artryazanov\GogScanner\Tests\TestCase;
use Exception;
use Illuminate\Support\Facades\Http;

class BaseScanJobTest extends TestCase
{
    public function test_handle_respects_decay_seconds(): void
    {
        config(['gogscanner.decay_seconds' => 1]);

        $job = new class() extends BaseScanJob {
            protected function doJob(): void
            {
                // simulate a short job of ~10ms
                usleep(10_000);
            }
        };

        $start = microtime(true);
        $job->handle();
        $elapsed = microtime(true) - $start;

        // Should be at least close to 1 second (allowing for timing jitter)
        $this->assertGreaterThanOrEqual(0.90, $elapsed, 'Job did not respect decay sleep >= 0.9s');
        // And should not be excessively longer under normal conditions
        $this->assertLessThan(2.5, $elapsed, 'Job sleep exceeded reasonable upper bound');
    }

    public function test_handle_catches_exception_and_marks_failed(): void
    {
        config(['gogscanner.decay_seconds' => 0]);

        $job = new class() extends BaseScanJob {
            public ?\Throwable $failedWith = null;

            protected function doJob(): void
            {
                throw new Exception('boom');
            }

            public function fail($e = null): void
            {
                $this->failedWith = $e;
            }
        };

        $job->handle();

        $this->assertInstanceOf(Exception::class, $job->failedWith);
        $this->assertSame('boom', $job->failedWith->getMessage());
    }

    public function test_fetch_json_logs_and_returns_null_on_failure(): void
    {
        config(['gogscanner.decay_seconds' => 0]);

        Http::fake(['*' => Http::response('Server error', 500)]);

        $job = new class() extends BaseScanJob {
            public $result;

            protected function doJob(): void
            {
                $this->result = $this->fetchJson('https://example.com', [], 'failure', ['ctx' => 1], 0);
            }
        };

        $job->handle();

        $this->assertNull($job->result);
    }
}
