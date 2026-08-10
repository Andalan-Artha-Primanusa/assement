<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $path = str_replace('\\', '/', $path);

        abort_if(
            str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\'),
            404
        );

        $fullPath = $this->resolvePublicFilePath($path);

        if ($fullPath === null) {
            abort(404);
        }

        if ($request->boolean('download')) {
            return response()->download($fullPath, basename($path));
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function resolvePublicFilePath(string $path): ?string
    {
        $disk = Storage::disk('public');
        $normalized = ltrim($path, '/');
        $withoutStoragePrefix = str_starts_with($normalized, 'storage/')
            ? substr($normalized, strlen('storage/'))
            : $normalized;

        foreach (array_unique([$normalized, $withoutStoragePrefix]) as $candidate) {
            if ($disk->exists($candidate)) {
                return $disk->path($candidate);
            }
        }

        $publicCandidates = [
            public_path($normalized),
            public_path('storage/'.$withoutStoragePrefix),
            storage_path('app/public/'.$withoutStoragePrefix),
        ];

        foreach ($publicCandidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
