<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'client_id' => $this->client_id,
            'team_member_id' => $this->team_member_id,
            'service_id' => $this->service_id,
            'service_name' => $this->service_name,
            'price' => $this->price,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'client' => $this->whenLoaded('client'),
            'barber' => new BarberResource($this->whenLoaded('barber')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'total_value' => $this->price,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
