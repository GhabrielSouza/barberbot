<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateScheduleRequest extends FormRequest
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
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'day_of_week.required' => 'Day of week is required',
            'day_of_week.integer' => 'Day of week must be an integer',
            'day_of_week.min' => 'Day of week must be between 0-6',
            'day_of_week.max' => 'Day of week must be between 0-6',
            'start_time.required' => 'Start time is required',
            'start_time.date_format' => 'Start time must be in format H:i',
            'end_time.required' => 'End time is required',
            'end_time.date_format' => 'End time must be in format H:i',
            'end_time.after' => 'End time must be after start time',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'day_of_week' => 'day of week',
            'start_time' => 'start time',
            'end_time' => 'end time',
        ];
    }
}
