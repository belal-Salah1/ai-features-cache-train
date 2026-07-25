<?php

namespace App\Jobs;

use App\Services\TextGeneratorService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateDescText implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120; // 2 minutes

    public array $backoff = [10, 30, 60]; // retry after 10s, then 30s, then 60s

    public int $uniqueFor = 600; // unique lock held for 10 minutes

    public int $maxExceptions = 3;

    public function __construct(public array $data)
    {
        $this->onQueue('ai_desc_generation');
    }

    /**
     * Single source of truth for the status cache key. The controller, this job,
     * and the status endpoint all derive the key from here so they read/write
     * the same cache entry.
     */
    public static function cacheKey(string $jobId): string
    {
        return 'ai_desc_'.$jobId;
    }

    public function uniqueId(): string
    {
        return 'ai-desc-'.$this->data['job_id'];
    }

    public function tags(): array
    {
        return [
            'ai_desc_generation',
            'job:'.$this->data['job_id'],
            'user:'.($this->data['user_id'] ?? 'system'),
        ];
    }

    public function handle(TextGeneratorService $textGeneratorService): void
    {
        $cacheKey = self::cacheKey($this->data['job_id']);

        Log::channel('ai_desc_generation')->info('Starting AI description generation job', [
            'job_id' => $this->data['job_id'],
            'user_id' => $this->data['user_id'] ?? null,
        ]);

        try {
            Cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => 10,
                'step' => 'Generating AI description job started',
                'generated_text' => null,
            ], now()->addMinutes(10));

            $generatedText = $textGeneratorService->generateText($this->data);

            Cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'step' => 'AI description generation completed',
                'generated_text' => $generatedText,
            ], now()->addMinutes(10));

            Log::channel('ai_desc_generation')->info('AI description generation completed', [
                'job_id' => $this->data['job_id'],
                'user_id' => $this->data['user_id'] ?? null,
            ]);
        } catch (\Throwable $exception) {
            Log::channel('ai_desc_generation')->error('Error in AI description generation job', [
                'job_id' => $this->data['job_id'],
                'user_id' => $this->data['user_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            Cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 0,
                'step' => 'AI description generation failed',
                'generated_text' => null,
                'error' => $exception->getMessage(),
            ], now()->addMinutes(10));

            throw $exception; // re-throw so failed() runs and the job is marked failed
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('ai_desc_generation')->error('AI description generation job failed', [
            'job_id' => $this->data['job_id'],
            'user_id' => $this->data['user_id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
