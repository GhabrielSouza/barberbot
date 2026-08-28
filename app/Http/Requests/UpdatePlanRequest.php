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
            'name.required' => 'O nome do plano é obrigatório',
            'name.string' => 'O nome do plano deve ser um texto',
            'name.max' => 'O nome do plano não pode exceder 255 caracteres',
            'price_month.required' => 'O preço mensal é obrigatório',
            'price_month.numeric' => 'O preço mensal deve ser um número',
            'price_month.min' => 'O preço mensal não pode ser negativo',
            'included_members.required' => 'A quantidade de membros inclusos é obrigatória',
            'included_members.integer' => 'A quantidade de membros inclusos deve ser um número inteiro',
            'included_members.min' => 'A quantidade de membros inclusos não pode ser negativa',
            'price_per_extra_member.required' => 'O preço por membro extra é obrigatório',
            'price_per_extra_member.numeric' => 'O preço por membro extra deve ser um número',
            'price_per_extra_member.min' => 'O preço por membro extra não pode ser negativo',
            'limits.array' => 'Os limites devem ser um objeto',
            'active.required' => 'O campo ativo é obrigatório',
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
            'price_month' => 'preço mensal',
            'included_members' => 'membros inclusos',
            'price_per_extra_member' => 'preço por membro extra',
            'limits' => 'limites',
            'active' => 'status ativo',
        ];
    }
}
