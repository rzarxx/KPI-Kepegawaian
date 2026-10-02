<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'footer_text' => ['nullable', 'string', 'max:200'],
            'app_logo' => ['nullable', 'image', 'mimes:png,svg,webp', 'max:512'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }
}
