<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'string', Rule::in(['owner', 'supervisor', 'atendente'])],
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
            'role.required' => 'A função é obrigatória',
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
