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
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'The selected user does not exist',
            'barber_id.required' => 'Barber ID is required',
            'barber_id.exists' => 'The selected barber does not exist',
            'service_id.required' => 'Service ID is required',
            'service_id.exists' => 'The selected service does not exist',
            'date.required' => 'Date is required',
            'date.date_format' => 'Date must be in format Y-m-d',
            'date.after_or_equal' => 'Date must be today or in the future',
            'time.required' => 'Time is required',
            'time.date_format' => 'Time must be in format H:i',
        ];
    }
}
