<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider used for generating product
    | descriptions and other AI-powered features.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Base configuration for Google's Gemini API (free tier).
    |
    */

    'api_key' => env('GEMINI_API_KEY'),
    'timeout' => env('GEMINI_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Task-Specific AI Models
    |--------------------------------------------------------------------------
    |
    | Different models optimized for different tasks. This allows cost and
    | performance optimization per use case.
    |
    */

    'models' => [
        'generateDescText' => env('AI_MODEL_GENERATE', 'gemini-2.0-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for generated descriptions to improve performance.
    |
    */

    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 86400), // 24 hours
    ],
];
