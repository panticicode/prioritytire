<?php

namespace App\Http\Requests\Dashboard\Permissions;

use Illuminate\Foundation\Http\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
    * Get the validation rules that apply to the request.
    *
    * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    */
    public function rules(): array
    {
        return [
            'name'        => 'required|min:3|max:30|unique:permissions,name|regex:/^[A-Z][\p{L}\p{N}_\s,-]+$/u',
            'description' => 'required|max:500',
        ];
    }

    /**
     * Returns custom validation messages.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'The Permission name field is required.',
            'name.min'          => 'The Permission name field must be at least :min characters.',
            'name.max'          => 'The Permission name field must not be greater than :max characters',
            'name.regex'        => 'The Permission name field must start with a capital letter and may only contain letters, numbers, spaces, and underscores.',
        ];
    }
}
