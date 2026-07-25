<?php

namespace App\Services;

use App\Models\AiLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TextGeneratorService
{
    /**
     * Generate a product description via Gemini.
     *
     * Every call is recorded in the ai_logs table (response on success,
     * error_message on failure). Failures are re-thrown so the queued job
     * can mark the request as failed and retry.
     *
     * @param  array<string, mixed>  $data  name, description, details, job_id, user_id
     */
    public function generateText(array $data): string
    {
        $model = config('ai.models.generateDescText');
        $prompt = $this->userPrompt(
            $data['name'] ?? '',
            $data['details'] ?? $data['description'] ?? '',
        );

        $context = [
            'prompt' => $prompt,
            'model' => $model,
            'request_id' => $data['job_id'] ?? null,
            'call_site' => static::class.'@generateText',
        ];

        try {
            $response = $this->client()
                ->post($this->endpoint($model), $this->payload($prompt))
                ->throw();

            $text = $response->json('candidates.0.content.parts.0.text');

            if (blank($text)) {
                throw new \RuntimeException('Gemini returned an empty response.');
            }

            AiLog::create([...$context, 'response' => $text]);

            return $text;
        } catch (Throwable $exception) {
            AiLog::create([...$context, 'error_message' => $exception->getMessage()]);

            Log::channel('ai_desc_generation')->error('AI text generation failed', [
                'request_id' => $context['request_id'],
                'model' => $model,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Gemini "generateContent" request body: system instruction, the user
     * prompt, and generation tuning.
     *
     * @return array<string, mixed>
     */
    private function payload(string $prompt): array
    {
        return [
            'system_instruction' => [
                'parts' => [['text' => $this->systemPrompt()]],
            ],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature' => 0.5,
                'maxOutputTokens' => 500,
            ],
        ];
    }

    public function userPrompt(string $productName, string $productDetails): string
    {
        return 'Product Name: '.$productName."\n".'Product Details: '.$productDetails."\n\n".'Please generate a compelling product description based on the above information.';
    }

    public function systemPrompt(): string
    {
        return "You are a professional copywriter. Generate a compelling product description based on the following details:\n\n";
    }

    /**
     * Pre-configured Gemini HTTP client: base URL, auth header, timeout,
     * and automatic retries on transient (429/5xx) failures.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl(config('ai.base_url'))
            ->withHeaders(['x-goog-api-key' => config('ai.api_key')])
            ->timeout(config('ai.timeout'))
            ->retry(2, 200)
            ->acceptJson();
    }

    private function endpoint(string $model): string
    {
        return "models/{$model}:generateContent";
    }
}
