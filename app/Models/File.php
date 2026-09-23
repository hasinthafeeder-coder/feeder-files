<?php

namespace App\Models;

use Feeder\Core\Enums\FileCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (File $file): bool {
            if ((string) $file->category === FileCategory::PAYMENT_PROOF->value) {
                return false;
            }

            return true;
        });
    }
}
