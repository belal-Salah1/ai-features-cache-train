<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Jobs\GenerateDescText;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiDescGeneratorController extends Controller
{
    public function index()
    {
        return inertia('AiTest');
    }

    public function generateAiDesc(ProductRequest $request)
    {
        $validatedData = $request->validated();

        $jobId = Str::uuid()->toString();
        $cacheKey = 'ai_desc_'.$jobId;

        Cache::put($cacheKey, [
            'status' => 'pending',
            'progress' => 0,
            'step' => 'Queued for processing',
            'generated_text' => null,
        ], now()->addMinutes(10));

        GenerateDescText::dispatch($validatedData, app()->make(textGeneratorService::class));

        Log::channel('products')->info('ai enhancement job queued', [
            'job_id' => $jobId,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'job_id' => $jobId]);
    }
}
