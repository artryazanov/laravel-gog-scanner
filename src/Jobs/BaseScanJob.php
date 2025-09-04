<?php

namespace Artryazanov\GogScanner\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class BaseScanJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Number of attempts for the job */
    public int $tries = 3;

    /**
     * Child jobs must implement their logic here.
     */
    abstract protected function doJob(): void;

    /**
     * Wrapper handle with error handling and optional decay sleep.
     */
    public function handle(): void
    {
        $startedAt = microtime(true);
        try {
            $this->doJob();
        } catch (Throwable $e) {
            $this->fail($e);
        }

        $decaySeconds = (int) config('gogscanner.decay_seconds', 1);
        if ($decaySeconds > 0) {
            $elapsedMicros = (int) ((microtime(true) - $startedAt) * 1_000_000);
            $sleepMicros = max(0, ($decaySeconds * 1_000_000) - $elapsedMicros);
            if ($sleepMicros > 0) {
                usleep($sleepMicros);
            }
        }
    }

    /**
     * Return HTTP timeout from config.
     */
    protected function getTimeout(): int
    {
        return (int) config('gogscanner.http_timeout', 30);
    }

    /**
     * Perform a GET request and return JSON or release the job on failure.
     *
     * @param  string  $url
     * @param  array<string, mixed>  $params
     * @param  string  $errorMessage  Message to log on HTTP failure
     * @param  array<string, mixed>  $context       Extra context for the log
     * @param  int     $releaseAfter Seconds to delay before retrying
     * @return array<string, mixed>|null
     */
    protected function fetchJson(string $url, array $params, string $errorMessage, array $context = [], int $releaseAfter = 60): ?array
    {
        $resp = Http::timeout($this->getTimeout())->get($url, $params);
        if ($resp->failed()) {
            \Log::error($errorMessage, array_merge($context, ['status' => $resp->status()]));
            $this->release($releaseAfter);

            return null;
        }

        return $resp->json();
    }

    /**
     * Apply the default queue connection and queue name to a pending dispatch.
     *
     * @template T
     * @param  T  $pending
     * @return T
     */
    protected function queueDispatch($pending)
    {
        return $pending
            ->onConnection(config('gogscanner.queue.connection'))
            ->onQueue(config('gogscanner.queue.queue'));
    }

    /**
     * Provide a stable unique identifier so duplicate jobs aren't queued.
     */
    public function uniqueId(): string
    {
        $payload = [];
        foreach (['gameId', 'page'] as $key) {
            if (property_exists($this, $key)) {
                /** @phpstan-ignore-next-line */
                $payload[$key] = $this->{$key};
            }
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
