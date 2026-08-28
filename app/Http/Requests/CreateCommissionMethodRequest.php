<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCommissionMethodRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', Rule::unique('commission_methods', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'O código é obrigatório',
            'code.string' => 'O código deve ser um texto',
            'code.max' => 'O código não pode exceder 255 caracteres',
            'code.unique' => 'Este código já está em uso',
            'name.required' => 'O nome é obrigatório',
            'name.string' => 'O nome deve ser um texto',
            'name.max' => 'O nome não pode exceder 255 caracteres',
            'description.string' => 'A descrição deve ser um texto',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nome',
            'description' => 'descrição',
        ];
    }
}
