<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTenantRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'segment' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,suspended,cancelled'],
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
            'status.in' => 'Status must be one of: active, suspended, cancelled',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'segment' => 'segment',
            'city' => 'city',
            'phone' => 'phone',
            'status' => 'status',
        ];
    }
}
