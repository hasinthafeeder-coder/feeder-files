<?php

namespace Tests\Unit;

use App\Services\FileUploadService;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    public function test_product_guideline_storage_directory_mapping(): void
    {
        $service = new FileUploadService;

        $this->assertSame(
            'product-guidelines',
            $service->directory('PRODUCT_GUIDELINE')
        );
    }

    public function test_business_registration_directory_remains_unchanged(): void
    {
        $service = new FileUploadService;

        $this->assertSame(
            'business-registrations',
            $service->directory('BUSINESS_REGISTRATION')
        );
    }
}
