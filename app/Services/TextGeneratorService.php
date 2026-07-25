<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TextGeneratorService
{
    // Implement your AI text generation logic here.
    public function generateText(array $data, string $role = 'user'): string
    {
        $model = config('ai.models.generateDescText');
       
        $messages = [
            [
                'role' => $role,
                'content' => [
                    ['type' => 'text', 'text' => $this->getPromptRole('user')],
                ],

            ],
            [
                'role' => 'system',
                'content' => [
                    ['type' => 'text', 'text' => $this->getPromptRole('system')],
                ],
            ],
        ];
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.5,
            'max_output_tokens' => 500,
        ];
        try{
            $response = $this->client()
                ->post($this->endpoint($model), $payload)
                ->throw();
            return $response->json('candidates.0.content.parts.0.text');
        }
        catch(\Exception $e){
            \Log::channel('ai')->error('AI text generation failed: '.$e->getMessage());
            return 'Error generating text.';

        }
        
    }

    public function getPromptRole(string $role): string
    {
        return $role === 'user'
                   ? $this->userPrompt($data['product_name'], $data['product_details'])
                   : $this->systemPrompt();
    }

    /**
     * Build the Gemini "generateContent" endpoint for the given model,
     * relative to the configured base URL. Call it like:
     *
     *   $response = $this->client()
     *       ->post($this->endpoint($model), [
     *           'contents' => [['parts' => [['text' => $prompt]]]],
     *       ])
     *       ->throw();
     *   $text = $response->json('candidates.0.content.parts.0.text');
     */
    public function userPrompt(string $productName, string $productDetails): string
    {
        return 'Product Name: '.$productName."\n".'Product Details: '.$productDetails."\n\n".'Please generate a 3 compelling product description based on the above information.';
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
