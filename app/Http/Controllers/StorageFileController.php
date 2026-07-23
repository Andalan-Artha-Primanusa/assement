<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class StorageFileController extends Controller
{
    public function show(string $path): Response
    {
        $fullPath = Storage::disk('public')->path($path);

        if (! file_exists($fullPath)) {
            abort(404);
        }

        $mime = mime_content_type($fullPath);
        $size = filesize($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Length' => $size,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
