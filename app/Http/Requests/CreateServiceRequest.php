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
            'name.required' => 'O nome do serviço é obrigatório',
            'name.string' => 'O nome do serviço deve ser um texto',
            'name.max' => 'O nome do serviço não pode exceder 255 caracteres',
            'price.required' => 'O preço é obrigatório',
            'price.numeric' => 'O preço deve ser um número',
            'price.min' => 'O preço deve ser maior ou igual a 0',
            'duration_minutes.required_without' => 'A duração é obrigatória',
            'duration_minutes.integer' => 'A duração deve ser um número inteiro',
            'duration_minutes.min' => 'A duração deve ser de no mínimo 1 minuto',
            'duration_minutes.max' => 'A duração não pode exceder 480 minutos',
            'duration_min.required_without' => 'A duração é obrigatória',
            'duration_min.integer' => 'A duração deve ser um número inteiro',
            'duration_min.min' => 'A duração deve ser de no mínimo 1 minuto',
            'duration_min.max' => 'A duração não pode exceder 480 minutos',
            'category.string' => 'A categoria deve ser um texto',
            'category.max' => 'A categoria não pode exceder 100 caracteres',
            'active.boolean' => 'O campo ativo deve ser um booleano',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'price' => 'preço',
            'duration_minutes' => 'duração (minutos)',
            'duration_min' => 'duração (minutos)',
            'category' => 'categoria',
            'active' => 'status ativo',
        ];
    }
}
