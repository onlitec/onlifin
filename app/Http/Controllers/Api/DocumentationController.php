<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
    public function index()
    {
        return view('api.documentation');
    }

    public function openapi(Request $request)
    {
        $spec = Storage::disk('local')->get('api/openapi.yaml');

        return response($spec)
            ->header('Content-Type', 'application/x-yaml');
    }
}
