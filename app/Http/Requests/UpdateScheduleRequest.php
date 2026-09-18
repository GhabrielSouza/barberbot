<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleRequest extends FormRequest
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
            'day_of_week' => ['sometimes', 'required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $schedule = $this->route('schedule');
            $start = $this->input('start_time', substr($schedule->start_time, 0, 5));
            $end = $this->input('end_time', substr($schedule->end_time, 0, 5));
            if ($end <= $start) {
                $validator->errors()->add('end_time', 'O horário de término deve ser depois do horário de início.');
            }
        }];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'day_of_week.required' => 'O dia da semana é obrigatório',
            'day_of_week.integer' => 'O dia da semana deve ser um número inteiro',
            'day_of_week.min' => 'O dia da semana deve estar entre 0 e 6',
            'day_of_week.max' => 'O dia da semana deve estar entre 0 e 6',
            'start_time.required' => 'O horário de início é obrigatório',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM',
            'end_time.required' => 'O horário de término é obrigatório',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM',
            'end_time.after' => 'O horário de término deve ser depois do horário de início',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'day_of_week' => 'dia da semana',
            'start_time' => 'horário de início',
            'end_time' => 'horário de término',
        ];
    }
}
