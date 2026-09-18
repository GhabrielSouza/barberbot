<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected const Z_API_BASE_URL = 'https://api.z-api.io/instances';

    /**
     * Send a message via Z-API
     */
    public function sendMessage(Company $company, User $user, string $message): bool
    {
        try {

            $url = self::Z_API_BASE_URL."/{$company->whatsapp_instance}/token/{$company->whatsapp_token}/send-message";

            $response = Http::timeout(10)
                ->post(
                    $url,
                    [
                        'phone' => $user->phone,
                        'message' => $message,
                    ]
                );

            if ($response->successful()) {
                // Save message to database
                Message::create([
                    'user_id' => $user->id,
                    'direction' => 'out',
                    'message' => $message,
                ]);

                return true;
            }

            Log::error('Z-API Error', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send message with buttons/options
     */
    public function sendMessageWithButtons(Company $company, User $user, string $message, array $buttons): bool
    {
        try {
            $url = self::Z_API_BASE_URL."/{$company->whatsapp_instance}/token/{$company->whatsapp_token}/send-message";
            $payload = [
                'phone' => $user->phone,
                'message' => $message,
                'buttons' => $buttons,
            ];

            $response = Http::timeout(10)
                ->post(
                    $url,
                    $payload
                );

            if ($response->successful()) {
                Message::create([
                    'user_id' => $user->id,
                    'direction' => 'out',
                    'message' => $message."\n\nButtons: ".json_encode($buttons),
                ]);

                return true;
            }

            Log::error('Z-API Error', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'response' => $response->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error', [
                'company_id' => $company->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format appointment confirmation message
     *
     * @param  User  $user
     */
    public function formatAppointmentConfirmation($user, $appointment): string
    {
        return "✅ Seu agendamento foi confirmado!\n\n".
            "📅 Data: {$appointment->date->format('d/m/Y')}\n".
            "⏰ Horário: {$appointment->time}\n".
            "💈 Barbeiro: {$appointment->barber->name}\n".
            "✂️ Serviço: {$appointment->service->name}\n".
            "💰 Valor: R$ {$appointment->service->price}";
    }

    /**
     * Format service list message
     */
    public function formatServiceList(array $services): string
    {
        $message = "🎯 Selecione um serviço:\n\n";

        foreach ($services as $index => $service) {
            $message .= ($index + 1).". {$service['name']} - R$ {$service['price']} ({$service['duration_minutes']}min)\n";
        }

        return $message;
    }

    /**
     * Format barber list message
     */
    public function formatBarberList(array $barbers): string
    {
        $message = "👨‍💼 Selecione o barbeiro:\n\n";

        foreach ($barbers as $index => $barber) {
            $message .= ($index + 1).". {$barber['name']}\n";
        }

        return $message;
    }

    /**
     * Format available times message
     */
    public function formatTimeSlotsList(array $slots): string
    {
        $message = "🕐 Horários disponíveis:\n\n";

        foreach ($slots as $index => $slot) {
            $message .= ($index + 1).". {$slot['time']}\n";
        }

        return $message;
    }
}
