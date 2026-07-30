<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        abort_if(
            str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\'),
            404
        );

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $fullPath = $disk->path($path);

        if ($request->boolean('download')) {
            return response()->download($fullPath, basename($path));
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
