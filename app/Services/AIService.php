<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Generate intelligent response using Claude API
     *
     * @param string $message
     * @param string $context
     * @return string
     */
    public function generateResponse(string $message, string $context = ''): string
    {
        try {
            $system = "Você é um assistente inteligente para uma barbearia. Responda de forma amigável e concisa. " .
                "Se a mensagem for sobre agendamento, sempre responda: 'Digite AGENDAR para agendar seu corte!'";

            if ($context) {
                $system .= "\n\nContexto adicional: $context";
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.anthropic.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-haiku-20240307',
                    'max_tokens' => 150,
                    'system' => $system,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $message,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['content'][0]['text'])) {
                    return $data['content'][0]['text'];
                }
            }

            Log::error('Claude API Error', [
                'response' => $response->json(),
            ]);

            return 'Desculpe, não consegui processar sua mensagem. Digite AGENDAR para agendar!';
        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'error' => $e->getMessage(),
            ]);

            return 'Desculpe, não consegui processar sua mensagem. Digite AGENDAR para agendar!';
        }
    }

    /**
     * Check if message contains appointment keywords
     *
     * @param string $message
     * @return bool
     */
    public function containsAppointmentKeywords(string $message): bool
    {
        $keywords = ['agendar', 'agendamento', 'marcar', 'horário', 'appointment', 'schedule'];
        $lowerMessage = strtolower($message);

        foreach ($keywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message is a greeting
     *
     * @param string $message
     * @return bool
     */
    public function isGreeting(string $message): bool
    {
        $greetings = ['oi', 'olá', 'opa', 'e aí', 'tudo bem', 'hi', 'hello'];
        $lowerMessage = strtolower(trim($message));

        foreach ($greetings as $greeting) {
            if (strpos($lowerMessage, $greeting) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message is a farewell
     *
     * @param string $message
     * @return bool
     */
    public function isFarewell(string $message): bool
    {
        $farewells = ['tchau', 'até logo', 'adeus', 'falou', 'bye', 'goodbye'];
        $lowerMessage = strtolower(trim($message));

        foreach ($farewells as $farewell) {
            if (strpos($lowerMessage, $farewell) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get greeting response
     *
     * @param string $name
     * @return string
     */
    public function getGreetingResponse(string $name): string
    {
        $greetings = [
            "Olá {$name}! 👋 Bem-vindo! Como posso ajudá-lo?",
            "E aí {$name}! 😊 Tudo certo por aqui?",
            "Opa {$name}! Que bom te ver! 💈",
        ];

        return $greetings[array_rand($greetings)];
    }
}
