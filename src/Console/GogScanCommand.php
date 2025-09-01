<?php

namespace Artryazanov\GogScanner\Console;

use Artryazanov\GogScanner\Jobs\ScanPageJob;
use Illuminate\Console\Command;

class GogScanCommand extends Command
{
    protected $signature = 'gog:scan {page=1 : Starting page number}';

    protected $description = 'Queue jobs to scan GOG games (paginated) and their details';

    public function handle(): int
    {
        $page = (int) $this->argument('page');

        $connection = config('gogscanner.queue.connection');
        $queue = config('gogscanner.queue.queue');

        ScanPageJob::dispatch($page)->onConnection($connection)->onQueue($queue);

        $this->info("Dispatched ScanPageJob for page {$page}");

        return self::SUCCESS;
    }
}
