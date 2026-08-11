<?php

namespace App\Services;

use Feeder\Core\Enums\FileCategory;
use Feeder\Core\Models\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ThumbnailService
{
    private ImageManager $imageManager;

    public function __construct()
    {
        $driver = config('thumbnails.driver', 'gd');

        $this->imageManager = new ImageManager(
            $driver === 'imagick'
                ? new \Intervention\Image\Drivers\Imagick\Driver()
                : new Driver()
        );
    }

    public function get(File $file, string $size = 'md'): array
    {
        if (!$this->supportsThumbnail($file)) {
            throw new BadRequestHttpException("Thumbnails are not supported for this file category: {$file->category}");
        }

        $dimensions = $this->dimensions($size);
        $thumbnailPath = $this->thumbnailPath($file, $size);

        if (!Storage::disk($file->disk)->exists($thumbnailPath)) {
            $this->generate($file, $thumbnailPath, $dimensions);
        }

        return [
            'disk' => $file->disk,
            'path' => $thumbnailPath,
            'mime_type' => $file->mime_type,
            'original_name' => $file->original_name,
        ];
    }

    private function generate(File $file, string $thumbnailPath, array $dimensions): void
    {
        $disk = Storage::disk($file->disk);

        $image = $this->imageManager->decode($disk->get($file->path));

        $image->scaleDown(
            width: $dimensions['width'],
            height: $dimensions['height'],
        );

        $disk->put($thumbnailPath, (string) $image->encodeUsingMediaType($file->mime_type));
    }

    private function thumbnailPath(File $file, string $size): string
    {
        return 'thumbnails/' . dirname($file->path) . '/' . $size . '/' . basename($file->path);
    }

    private function dimensions(string $size): array
    {
        $sizes = config('thumbnails.sizes');

        if (!isset($sizes[$size])) {
            throw new BadRequestHttpException("Invalid thumbnail size: $size");
        }

        return $sizes[$size];
    }

    private function supportsThumbnail(File $file): bool
    {
        return FileCategory::from($file->category)->supportsThumbnail();
    }
}
