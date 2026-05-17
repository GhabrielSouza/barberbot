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
            'time' => $this->time,
            'status' => $this->status,
            'user' => new UserResource($this->whenLoaded('user')),
            'barber' => new BarberResource($this->whenLoaded('barber')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'total_value' => $this->whenLoaded('service', fn () => $this->service->price),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
