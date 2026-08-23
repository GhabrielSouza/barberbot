<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
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
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required_without:duration_min', 'nullable', 'integer', 'min:1', 'max:480'],
            'duration_min' => ['required_without:duration_minutes', 'nullable', 'integer', 'min:1', 'max:480'],
            'category' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Service name is required',
            'name.string' => 'Service name must be a string',
            'name.max' => 'Service name cannot exceed 255 characters',
            'price.required' => 'Price is required',
            'price.numeric' => 'Price must be a number',
            'price.min' => 'Price must be greater than or equal to 0',
            'duration_minutes.required_without' => 'Duration is required',
            'duration_minutes.integer' => 'Duration must be an integer',
            'duration_minutes.min' => 'Duration must be at least 1 minute',
            'duration_minutes.max' => 'Duration cannot exceed 480 minutes',
            'duration_min.required_without' => 'Duration is required',
            'duration_min.integer' => 'Duration must be an integer',
            'duration_min.min' => 'Duration must be at least 1 minute',
            'duration_min.max' => 'Duration cannot exceed 480 minutes',
            'category.string' => 'Category must be a string',
            'category.max' => 'Category cannot exceed 100 characters',
            'active.boolean' => 'Active field must be a boolean',
        ];
    }
}
