<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadFileRequest;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;

class FileController extends Controller
{
    public function __construct(private readonly FileUploadService $fileUploadService) {}

    public function upload(UploadFileRequest $request): JsonResponse
    {
        $file = $this->fileUploadService->upload($request->validated());

        return response()->json([
            'message' => 'File uploaded successfully.',
            'file' => [
                'uuid' => $file->uuid,
                'category' => $file->category,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
            ],
        ], 201);
    }
}
