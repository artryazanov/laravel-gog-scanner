<?php

namespace Artryazanov\GogScanner\Tests\Jobs;

use Artryazanov\GogScanner\Jobs\ScanGameDetailJob;
use Artryazanov\GogScanner\Jobs\ScanPageJob;
use Artryazanov\GogScanner\Tests\TestCase;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;

class JobUniquenessTest extends TestCase
{
    public function test_scan_page_job_is_unique_per_page(): void
    {
        Queue::fake();

        // Dispatch the same page twice
        ScanPageJob::dispatch(1);
        ScanPageJob::dispatch(1);

        // Only one should be pushed due to ShouldBeUnique
        Queue::assertPushed(ScanPageJob::class, 1);

        // Different page should be allowed
        ScanPageJob::dispatch(2);
        Queue::assertPushed(ScanPageJob::class, 2);
    }

    public function test_scan_game_detail_job_is_unique_per_game(): void
    {
        Queue::fake();

        // Dispatch the same game twice
        ScanGameDetailJob::dispatch(123);
        ScanGameDetailJob::dispatch(123);

        Queue::assertPushed(ScanGameDetailJob::class, 1);

        // Different game should be allowed
        ScanGameDetailJob::dispatch(456);
        Queue::assertPushed(ScanGameDetailJob::class, 2);
    }

    public function test_unique_id_payloads_are_distinct(): void
    {
        $pageJob1 = new ScanPageJob(1);
        $pageJob2 = new ScanPageJob(2);
        $detailJobA = new ScanGameDetailJob(111);
        $detailJobB = new ScanGameDetailJob(222);

        $this->assertNotSame($pageJob1->uniqueId(), $pageJob2->uniqueId());
        $this->assertNotSame($detailJobA->uniqueId(), $detailJobB->uniqueId());
        $this->assertNotSame($pageJob1->uniqueId(), $detailJobA->uniqueId());

        $this->assertSame('{"page":1}', $pageJob1->uniqueId());
        $this->assertSame('{"gameId":111}', $detailJobA->uniqueId());
    }

    public function test_jobs_implement_should_be_unique_interface(): void
    {
        $this->assertContains(ShouldBeUnique::class, class_implements(ScanPageJob::class));
        $this->assertContains(ShouldBeUnique::class, class_implements(ScanGameDetailJob::class));
    }
}
