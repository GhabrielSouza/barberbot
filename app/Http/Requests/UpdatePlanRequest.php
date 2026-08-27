<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
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
            'price_month' => ['sometimes', 'required', 'numeric', 'min:0'],
            'included_members' => ['sometimes', 'required', 'integer', 'min:0'],
            'price_per_extra_member' => ['sometimes', 'required', 'numeric', 'min:0'],
            'limits' => ['sometimes', 'nullable', 'array'],
            'active' => ['sometimes', 'required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required',
            'name.string' => 'Plan name must be a string',
            'name.max' => 'Plan name cannot exceed 255 characters',
            'price_month.required' => 'Monthly price is required',
            'price_month.numeric' => 'Monthly price must be a number',
            'price_month.min' => 'Monthly price cannot be negative',
            'included_members.required' => 'Included members is required',
            'included_members.integer' => 'Included members must be an integer',
            'included_members.min' => 'Included members cannot be negative',
            'price_per_extra_member.required' => 'Price per extra member is required',
            'price_per_extra_member.numeric' => 'Price per extra member must be a number',
            'price_per_extra_member.min' => 'Price per extra member cannot be negative',
            'limits.array' => 'Limits must be an object',
            'active.required' => 'Active field is required',
            'active.boolean' => 'Active field must be a boolean',
        ];
    }
}
