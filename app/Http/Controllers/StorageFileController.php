<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageFileController extends Controller
{
    public function show(Request $request, string $path): Response
    {
        $path = str_replace('\\', '/', $path);

        abort_if(
            str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\'),
            404
        );

        $fullPath = $this->resolvePublicFilePath($path);

        if ($fullPath === null || ! is_file($fullPath) || ! is_readable($fullPath)) {
            abort(404);
        }

        if ($request->boolean('download')) {
            return response()->download($fullPath, basename($path));
        }

        $mimeType = function_exists('mime_content_type')
            ? (mime_content_type($fullPath) ?: 'application/octet-stream')
            : 'application/octet-stream';

        return response()->stream(function () use ($fullPath): void {
            $handle = fopen($fullPath, 'rb');

            if ($handle === false) {
                return;
            }

            fpassthru($handle);
            fclose($handle);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) filesize($fullPath),
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
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
