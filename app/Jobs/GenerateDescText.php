<?php

namespace App\Jobs;

use App\aiTextGeneration;
use App\Services\textGeneratorService;
use cache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class GenerateDescText implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $tries = 3;

    public int $timeout = 120; // Set the timeout to 120 seconds (2 minutes)

    public array $backoff = [10, 30, 60]; // Retry after 10 seconds, then 30 seconds, then 60 seconds

    public int $uniqueFor = 600; // The job will be unique for 1 hour (3600 seconds)

    public int $maxExceptions = 3; // Maximum number of exceptions before failing the job

    public function __construct(public array $data, protected textGeneratorService $textGeneratorService)
    {
        $this->onQueue('ai_desc_generation');
    }

    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
        ];
    }

    public function uniqueId(): string
    {
        return "product-desc-ai-proccess{$this->data['product_id']}";
    }

    public function tags(): array
    {
        return [
            'ai_desc_generation',
            'product:'.$this->data['product_id'],
            'user:'.($this->data['user_id'] ?? 'system'),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cacheKey = 'ai_desc_'.$this->data['job_id'];
        Log::channel('ai_desc_generation')->info('Starting AI description generation job', [
            'product_id' => $this->data['product_id'],
            'user_id' => $this->data['user_id'] ?? null,
            'job_id' => $this->data['job_id'],
        ]);
        try {
            cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => 10,
                'step' => 'Generating AI description job started',
                'generated_text' => null,
            ], now()->addMinutes(10));

            $generatedText = $this->textGeneratorService->generateText($this->data);

            cache::put($cacheKey, [
                'status' => 'completed',
                'progress' => 100,
                'step' => 'AI description generation completed',
                'generated_text' => $generatedText,
            ], now()->addMinutes(10));

            Log::channel('ai_desc_generation')->info('AI description generation completed', [
                'product_id' => $this->data['product_id'],
                'user_id' => $this->data['user_id'] ?? null,
                'job_id' => $this->data['job_id'],
                'generated_text' => $generatedText,
            ]);

        } catch (\Throwable $exception) {
            Log::channel('ai_desc_generation')->error('Error in AI description generation job', [
                'product_id' => $this->data['product_id'],
                'user_id' => $this->data['user_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            cache::put($cacheKey, [
                'status' => 'failed',
                'progress' => 0,
                'step' => 'AI description generation failed',
                'generated_text' => null,
                'error' => $exception->getMessage(),
            ], now()->addMinutes(10));
            throw $exception; // Re-throw the exception to trigger the failed() method
        }

    }

    public function failed(\Throwable $exception): void
    {
        // Handle the failure, e.g., log the error, notify the user, etc.
        \Log::channel('ai_desc_generation')->error('AI description generation job failed', [
            'product_id' => $this->data['product_id'],
            'user_id' => $this->data['user_id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
