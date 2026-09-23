<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadFileRequest;
use Illuminate\Http\Request;

use App\Services\FileUploadService;
use App\Services\FileRetrievalService;
use App\Services\ThumbnailService;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileUploadService,
        private readonly FileRetrievalService $fileRetrievalService,
        private readonly ThumbnailService $thumbnailService
    ) {}

    public function upload(UploadFileRequest $request): JsonResponse
    {
        $file = $this->fileUploadService->upload($request->validated());

        return response()->json([
            'message' => 'File uploaded successfully.',
            'file' => [
                'uuid' => $file->uuid,
                'application' => $file->application,
                'entity_type' => $file->entity_type,
                'entity_uuid' => $file->entity_uuid,
                'category' => $file->category,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
            ],
        ], 201);
    }

    public function view(string $uuid): StreamedResponse
    {
        $file = $this->fileRetrievalService->getForAccess($uuid);

        return Storage::disk($file->disk)->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
            ]
        );
    }

    public function download(string $uuid): StreamedResponse|BinaryFileResponse
    {
        $file = $this->fileRetrievalService->getForAccess($uuid);

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
            ]
        );
    }

    public function thumbnail(Request $request, string $uuid): StreamedResponse
    {
        $file = $this->fileRetrievalService->getForAccess($uuid);

        $size = strtolower(
            $request->query('size', 'md')
        );

        $thumbnail = $this->thumbnailService->get(
            $file,
            $size
        );

        return Storage::disk($thumbnail['disk'])->response(
            $thumbnail['path'],
            $thumbnail['original_name'],
            [
                'Content-Type' => $thumbnail['mime_type'],
                'Content-Disposition' => 'inline; filename="' . $thumbnail['original_name'] . '"',
                'Cache-Control' => 'public, max-age=31536000',
            ]
        );
    }
}
