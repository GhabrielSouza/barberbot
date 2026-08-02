<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBarberRequest extends FormRequest
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
            'role' => ['sometimes', 'nullable', 'string', 'max:100'],
            'color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'is_admin' => ['sometimes', 'boolean'],
            'user_id' => ['sometimes', 'uuid'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Barber name is required',
            'name.string' => 'Barber name must be a string',
            'name.max' => 'Barber name cannot exceed 255 characters',
            'role.string' => 'Role must be a string',
            'role.max' => 'Role cannot exceed 100 characters',
            'color.string' => 'Color must be a string',
            'color.max' => 'Color cannot exceed 50 characters',
            'is_admin.boolean' => 'is_admin must be a boolean',
            'user_id.uuid' => 'User ID must be a valid UUID',
            'active.boolean' => 'Active field must be a boolean',
        ];
    }
}
