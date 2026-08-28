<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'string', Rule::in(['owner', 'supervisor', 'atendente'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do usuário é obrigatório',
            'name.string' => 'O nome do usuário deve ser um texto',
            'name.max' => 'O nome do usuário não pode exceder 255 caracteres',
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser um endereço válido',
            'email.unique' => 'Este e-mail já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            'role.in' => 'A função deve ser uma das seguintes: owner, supervisor, atendente',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'password' => 'senha',
            'role' => 'função',
        ];
    }
}
