<?php

namespace App\Services;

use Feeder\Core\Models\File;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileRetrievalService
{
    public function getForAccess(string $uuid): File
    {
        $file = File::query()
            ->where('uuid', strtoupper($uuid))
            ->firstOrFail();

        if (!Storage::disk($file->disk)->exists($file->path)) {
            throw new NotFoundHttpException('File not found in storage');
        }

        /*
        |--------------------------------------------------------------------------
        | Future Access Validation
        |--------------------------------------------------------------------------
        |
        | Examples:
        | - Validate application
        | - Validate authenticated user
        | - Validate company ownership
        | - Validate visibility (PUBLIC / PRIVATE)
        | - Audit access
        |
        */

        return $file;
    }
}
