<?php

namespace App\Services;

use Feeder\Core\Services\UuidService;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    public function upload(array $data): File
    {
        $file = $data['file'];
        $directory = $this->directory($data['category']);
        $storedName = $this->filename($file);
        $path = Storage::disk('feeder')->putFileAs($directory, $file, $storedName);
        $checksum = hash_file('sha256', Storage::disk('feeder')->path($path));

        return File::create([
            'uuid' => UuidService::generate(),
            'application' => $data['application'],
            'entity_type' => $data['entity_type'],
            'entity_uuid' => $data['entity_uuid'],
            'category' => $data['category'],
            'disk' => 'feeder',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'checksum' => $checksum,
            'visibility' => 'PRIVATE',
            'status' => 'ACTIVE',
            'metadata' => $data['metadata'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? null,
        ]);
    }

    public function directory(string $category): string
    {
        return match ($category) {
            'PROFILE_PHOTO' => 'profiles',

            'COMPANY_LOGO' => 'company-logos',

            'BUSINESS_REGISTRATION' => 'business-registrations',

            'PRODUCT_GUIDELINE' => 'product-guidelines',

            'PRODUCT_IMAGE' => 'product-images',

            'PAYMENT_PROOF' => 'payment-proofs',

            'INVOICE' => 'invoices',

            default => 'temp',
        };
    }

    private function filename(UploadedFile $file): string
    {
        return Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
    }
}
