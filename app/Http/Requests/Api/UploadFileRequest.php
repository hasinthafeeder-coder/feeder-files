<?php

namespace App\Http\Requests\Api;

use Feeder\Core\Enums\ApplicationType;
use Feeder\Core\Enums\FileCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $metadata = $this->input('metadata');

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            $this->merge([
                'metadata' => json_last_error() === JSON_ERROR_NONE ? $decoded : null,
            ]);
        }

        $this->merge([
            'application' => strtoupper(trim((string) $this->input('application'))),
            'entity_type' => strtoupper(trim((string) $this->input('entity_type'))),
            'entity_uuid' => strtoupper(trim((string) $this->input('entity_uuid'))),
            'category' => strtoupper(trim((string) $this->input('category'))),
            'uploaded_by' => filled($this->input('uploaded_by'))
                ? strtoupper(trim((string) $this->input('uploaded_by')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'application' => [
                'required',
                'string',
                'max:20',
                Rule::enum(ApplicationType::class),
            ],
            'entity_type' => [
                'required',
                'string',
                'max:30',
            ],
            'entity_uuid' => [
                'required',
                'string',
                'size:10',
            ],
            'category' => [
                'required',
                'string',
                'max:30',
                Rule::enum(FileCategory::class),
            ],
            'uploaded_by' => [
                'nullable',
                'string',
                'size:10',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
            'file' => [
                'required',
                'file',
                'max:10240',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->hasFile('file')) {
                return;
            }

            $category = FileCategory::tryFrom((string) $this->input('category'));
            $file = $this->file('file');

            if ($category === null || $file === null) {
                return;
            }

            $rules = $this->fileRulesForCategory($category);

            if ($rules === null) {
                return;
            }

            [$mimes, $maxKilobytes] = $rules;

            if (! in_array($file->getMimeType(), $mimes, true)
                && ! in_array(strtolower((string) $file->getClientOriginalExtension()), $this->extensionsFromMimes($mimes), true)
            ) {
                $validator->errors()->add('file', $this->invalidTypeMessage($category));
            }

            if ($file->getSize() > ($maxKilobytes * 1024)) {
                $validator->errors()->add(
                    'file',
                    sprintf('Maximum file size for this category is %dMB.', (int) ceil($maxKilobytes / 1024))
                );
            }
        });
    }

    /**
     * @return array{0: list<string>, 1: int}|null
     */
    private function fileRulesForCategory(FileCategory $category): ?array
    {
        // Match on backed values so category rules keep working when feeder-core
        // is temporarily behind app-level categories (e.g. PRODUCT_GUIDELINE).
        return match ($category->value) {
            FileCategory::PROFILE_PHOTO->value,
            FileCategory::COMPANY_LOGO->value,
            FileCategory::PRODUCT_IMAGE->value => [
                ['image/jpeg', 'image/png', 'image/webp'],
                5120,
            ],
            FileCategory::BUSINESS_REGISTRATION->value,
            'PRODUCT_GUIDELINE' => [
                ['application/pdf'],
                10240,
            ],
            FileCategory::PAYMENT_PROOF->value => [
                ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                10240,
            ],
            FileCategory::INVOICE->value => [
                ['application/pdf'],
                10240,
            ],
            default => null,
        };
    }

    /**
     * @param  list<string>  $mimes
     * @return list<string>
     */
    private function extensionsFromMimes(array $mimes): array
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $extensions = [];

        foreach ($mimes as $mime) {
            if (isset($map[$mime])) {
                $extensions[] = $map[$mime];
            }

            if ($mime === 'image/jpeg') {
                $extensions[] = 'jpeg';
            }
        }

        return array_values(array_unique($extensions));
    }

    private function invalidTypeMessage(FileCategory $category): string
    {
        return match ($category->value) {
            FileCategory::PROFILE_PHOTO->value,
            FileCategory::COMPANY_LOGO->value,
            FileCategory::PRODUCT_IMAGE->value => 'File must be a JPG, PNG, or WebP image.',
            FileCategory::BUSINESS_REGISTRATION->value,
            'PRODUCT_GUIDELINE',
            FileCategory::INVOICE->value => 'File must be a PDF document.',
            FileCategory::PAYMENT_PROOF->value => 'File must be a JPG, PNG, WebP image, or PDF document.',
            default => 'File type is not allowed for this category.',
        };
    }
}
