<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarberResource extends JsonResource
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
            'name' => $this->name,
            'role' => $this->role,
            'color' => $this->color,
            'is_admin' => $this->is_admin,
            'user_id' => $this->user_id,
            'active' => $this->active,
            'schedules' => ScheduleResource::collection($this->whenLoaded('schedules')),
            'appointments_count' => $this->whenCounted('appointments'),
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
