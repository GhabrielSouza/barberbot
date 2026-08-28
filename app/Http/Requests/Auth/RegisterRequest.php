<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'tenant_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'name.string' => 'O nome deve ser um texto',
            'name.max' => 'O nome não pode exceder 255 caracteres',
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser um endereço válido',
            'email.max' => 'O e-mail não pode exceder 255 caracteres',
            'email.unique' => 'Este e-mail já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.confirmed' => 'A confirmação de senha não corresponde',
            'tenant_name.required' => 'O nome da empresa é obrigatório',
            'tenant_name.string' => 'O nome da empresa deve ser um texto',
            'tenant_name.max' => 'O nome da empresa não pode exceder 255 caracteres',
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
            'password_confirmation' => 'confirmação de senha',
            'tenant_name' => 'nome da empresa',
        ];
    }
}
