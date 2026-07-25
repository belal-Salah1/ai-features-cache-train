<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Jobs\GenerateDescText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiDescGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $jobId = $request->query('job');

        return inertia('AiTest', [
            'jobId' => $jobId,
            'state' => $jobId ? Cache::get(GenerateDescText::cacheKey($jobId)) : null,
        ]);
    }

    public function generateAiDesc(ProductRequest $request)
    {
        $validatedData = $request->validated();

        $jobId = Str::uuid()->toString();
        $cacheKey = GenerateDescText::cacheKey($jobId);

        Cache::put($cacheKey, [
            'status' => 'pending',
            'progress' => 0,
            'step' => 'Queued for processing',
            'generated_text' => null,
        ], now()->addMinutes(10));

        GenerateDescText::dispatch([
            ...$validatedData,
            'job_id' => $jobId,
            'user_id' => auth()->id(),
        ]);

        Log::channel('products')->info('ai enhancement job queued', [
            'job_id' => $jobId,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('ai.view', ['job' => $jobId]);
    }

    public function getJobStatus(string $jobId)
    {
        try {
            $state = Cache::get(GenerateDescText::cacheKey($jobId));

            if (! $state) {
                Log::channel('products')->warning('Job status requested for non-existent job', [
                    'job_id' => $jobId,
                    'user_id' => auth()->id(),
                ]);

                return response()->json(['error' => 'Job not found'], 404);
            }

            return response()->json($state);
        } catch (\Throwable $e) {
            Log::channel('products')->error('Error fetching job status', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to fetch job status'], 500);
        }
    }
}
