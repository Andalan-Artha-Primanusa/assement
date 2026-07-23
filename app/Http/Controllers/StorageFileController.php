<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $fullPath = Storage::disk('public')->path($path);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
