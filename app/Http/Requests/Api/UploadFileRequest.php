<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'application' => [
                'required',
                'string',
                'max:20',
            ],

            'entity_type' => [
                'required',
                'string',
                'max:30',
            ],

            'entity_uuid' => [
                'required',
                'string',
                'max:10',
            ],

            'category' => [
                'required',
                'string',
                'max:30',
            ],

            'uploaded_by' => [
                'nullable',
                'string',
                'max:10',
            ],

            'metadata' => [
                'nullable',
                'array',
            ],

            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
            ],

        ];
    }
}
