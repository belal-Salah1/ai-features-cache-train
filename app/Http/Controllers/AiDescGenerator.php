<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AiDescGenerator extends Controller
{

    public function index()
    {
        return inertia('AiTest');
    }
    public function generateAiDesc(Request $request)
    {

    }
}
