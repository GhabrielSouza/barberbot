<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'segment' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:active,suspended,cancelled'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required',
            'name.string' => 'Tenant name must be a string',
            'name.max' => 'Tenant name cannot exceed 255 characters',
            'segment.string' => 'Segment must be a string',
            'city.string' => 'City must be a string',
            'phone.string' => 'Phone must be a string',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of: active, suspended, cancelled',
        ];
    }
}
