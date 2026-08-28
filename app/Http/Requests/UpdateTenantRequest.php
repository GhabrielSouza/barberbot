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
            'name.required' => 'O nome do tenant é obrigatório',
            'name.string' => 'O nome do tenant deve ser um texto',
            'name.max' => 'O nome do tenant não pode exceder 255 caracteres',
            'segment.string' => 'O segmento deve ser um texto',
            'city.string' => 'A cidade deve ser um texto',
            'phone.string' => 'O telefone deve ser um texto',
            'status.required' => 'O status é obrigatório',
            'status.in' => 'O status deve ser um dos seguintes: active, suspended, cancelled',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'segment' => 'segmento',
            'city' => 'cidade',
            'phone' => 'telefone',
            'status' => 'status',
        ];
    }
}
