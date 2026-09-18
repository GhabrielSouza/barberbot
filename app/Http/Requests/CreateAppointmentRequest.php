<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAppointmentRequest extends FormRequest
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
            'client_id' => ['required', 'uuid', 'exists:tenant.clients,id'],
            'team_member_id' => ['required', 'uuid', Rule::exists('tenant.team_members', 'id')->where('active', true)],
            'service_id' => ['required', 'uuid', Rule::exists('tenant.services', 'id')->where('active', true)],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'O cliente é obrigatório',
            'client_id.exists' => 'O cliente selecionado não existe',
            'team_member_id.required' => 'O barbeiro é obrigatório',
            'team_member_id.exists' => 'O barbeiro selecionado não existe',
            'service_id.required' => 'O serviço é obrigatório',
            'service_id.exists' => 'O serviço selecionado não existe',
            'date.required' => 'A data é obrigatória',
            'date.date_format' => 'A data deve estar no formato AAAA-MM-DD',
            'date.after_or_equal' => 'A data deve ser hoje ou uma data futura',
            'start_time.required' => 'O horário é obrigatório',
            'start_time.date_format' => 'O horário deve estar no formato HH:MM',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'team_member_id' => 'barbeiro',
            'service_id' => 'serviço',
            'date' => 'data',
            'start_time' => 'horário',
        ];
    }
}
