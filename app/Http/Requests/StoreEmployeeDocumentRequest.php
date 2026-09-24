<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employee_document.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:64'],
            'document' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('5mb')],
        ];
    }
}
