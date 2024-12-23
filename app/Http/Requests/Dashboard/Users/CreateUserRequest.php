<?php

namespace App\Http\Requests\Dashboard\Users;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'name'     => 'required|min:3|max:30|regex:/^[\p{Lu}][\p{L}\p{N}\s,.-]+$/u',
            'email'    => 'required|email|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/|unique:users,email',
            'password' => 'required|string|min:8',
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
            'name.required'  => 'The name field is required.',
            'name.min'       => 'The name field must be at least :min characters.',
            'name.max'       => 'The name field must not be greater than :max characters',
            'name.regex'     => 'The name field must start with a capital letter and may only contain letters, numbers, spaces, commas, periods, and hyphens.',
            'email.required' => 'The email field is required.',
            'email.email'    => 'The email field must be a valid email address.',
            'email.regex'    => 'The email field must be a valid email address.',
            'email.unique'   => 'The email has already been taken.'
        ];
    }
}
