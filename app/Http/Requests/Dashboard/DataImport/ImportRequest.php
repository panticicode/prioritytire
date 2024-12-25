<?php

namespace App\Http\Requests\Dashboard\DataImport;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ImportRequest class for data import validation.
 *
 * This request handles validation for the data import functionality in the dashboard.
 * It ensures that the necessary fields are present and meet the specified requirements.
 *
 * Validation Rules:
 * - `type`: Required field of type string, specifying the type of import.
 * - `files.*`: Required file input supporting multiple files, limited to `csv` or `xlsx` formats.
 *
 * @package App\Http\Requests\Dashboard\DataImport
 */

class ImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Always returns true, allowing any authenticated user to submit the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * Defines the structure and validation rules for the incoming data in the request.
     * The validation ensures that:
     * - `type`: Must be a required string.
     * - `files`: Must be a required array, expected to contain the files for import.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'  => 'required|string',
            'files' => 'required|array'
        ];
    }
}
