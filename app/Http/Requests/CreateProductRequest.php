<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'stock' => ['nullable', 'integer', 'min:0'],
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
            'name.required' => 'O nome do produto é obrigatório',
            'name.string' => 'O nome do produto deve ser um texto',
            'name.max' => 'O nome do produto não pode exceder 255 caracteres',
            'price.required' => 'O preço é obrigatório',
            'price.numeric' => 'O preço deve ser um número',
            'price.min' => 'O preço deve ser maior ou igual a 0',
            'stock.integer' => 'O estoque deve ser um número inteiro',
            'stock.min' => 'O estoque não pode ser negativo',
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
            'stock' => 'estoque',
            'category' => 'categoria',
            'active' => 'status ativo',
        ];
    }
}
