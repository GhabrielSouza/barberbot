<?php

namespace App\Http\Requests;

use App\Models\Barber;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
{
    public User $user;
    public Barber $barber;
    public Service $service;
    public Carbon $date;
    public string $time;

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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'barber_id' => ['required', 'integer', 'exists:barbers,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Load relationships for use in controller
        $this->user = User::findOrFail($this->user_id);
        $this->barber = Barber::findOrFail($this->barber_id);
        $this->service = Service::findOrFail($this->service_id);
        $this->date = Carbon::parse($this->date);
        $this->time = $this->time;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'O cliente é obrigatório',
            'user_id.exists' => 'O cliente selecionado não existe',
            'barber_id.required' => 'O barbeiro é obrigatório',
            'barber_id.exists' => 'O barbeiro selecionado não existe',
            'service_id.required' => 'O serviço é obrigatório',
            'service_id.exists' => 'O serviço selecionado não existe',
            'date.required' => 'A data é obrigatória',
            'date.date_format' => 'A data deve estar no formato AAAA-MM-DD',
            'date.after_or_equal' => 'A data deve ser hoje ou uma data futura',
            'time.required' => 'O horário é obrigatório',
            'time.date_format' => 'O horário deve estar no formato HH:MM',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'cliente',
            'barber_id' => 'barbeiro',
            'service_id' => 'serviço',
            'date' => 'data',
            'time' => 'horário',
        ];
    }
}
