<?php

namespace App\Http\Controllers;
use App\Http\Requests\productRequest;
use Illuminate\Http\Request;
use App\jobs\GenerateDescText;

class AiDescGenerator extends Controller
{

    public function index()
    {
        return inertia('AiTest');
    }
    public function generateAiDesc(productRequest $request)
    {
        $validatedData = request()->validated();
        // Process the validated data
        Log::channel('ai')->info('start generating description',[
            
        ]);
        $generatedDesc = $this->dispatch(new GenerateDescText($validatedData));
        return response()->json(['message' => 'Text generation job dispatched successfully.' , 'data' => $generatedDesc]);
    }
}
