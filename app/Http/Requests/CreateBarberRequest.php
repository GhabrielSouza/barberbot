<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBarberRequest extends FormRequest
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
            'name.required' => 'O nome do barbeiro é obrigatório',
            'name.string' => 'O nome do barbeiro deve ser um texto',
            'name.max' => 'O nome do barbeiro não pode exceder 255 caracteres',
            'role.string' => 'A função deve ser um texto',
            'role.max' => 'A função não pode exceder 100 caracteres',
            'color.string' => 'A cor deve ser um texto',
            'color.max' => 'A cor não pode exceder 50 caracteres',
            'is_admin.boolean' => 'O campo administrador deve ser um booleano',
            'user_id.uuid' => 'O ID do usuário deve ser um UUID válido',
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
            'role' => 'função',
            'color' => 'cor',
            'is_admin' => 'administrador',
            'user_id' => 'usuário',
            'active' => 'status ativo',
        ];
    }
}
