<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barber;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BotService
{
    protected WhatsAppService $whatsAppService;
    protected AvailabilityService $availabilityService;
    protected AppointmentService $appointmentService;
    protected AIService $aiService;

    public function __construct(
        WhatsAppService $whatsAppService,
        AvailabilityService $availabilityService,
        AppointmentService $appointmentService,
        AIService $aiService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->availabilityService = $availabilityService;
        $this->appointmentService = $appointmentService;
        $this->aiService = $aiService;
    }

    /**
     * Process incoming message from WhatsApp
     *
     * @param Company $company
     * @param string $phone
     * @param string $message
     * @return void
     */
    public function processMessage(Company $company, string $phone, string $message): void
    {
        try {
            // Find or create user
            $user = User::firstOrCreate(
                ['phone' => $phone, 'company_id' => $company->id],
                ['name' => $phone] // Default name as phone
            );

            // Save incoming message
            Message::create([
                'user_id' => $user->id,
                'direction' => 'in',
                'message' => $message,
            ]);

            // Get or create conversation
            $conversation = Conversation::firstOrCreate(
                ['user_id' => $user->id],
                ['step' => 'start', 'data' => []]
            );

            // Handle the message based on current step
            $this->handleConversationStep($user, $company, $conversation, $message);
        } catch (\Exception $e) {
            Log::error('Bot Service Error', [
                'company_id' => $company->id,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle conversation flow based on current step
     *
     * @param User $user
     * @param Company $company
     * @param Conversation $conversation
     * @param string $message
     * @return void
     */
    protected function handleConversationStep(User $user, Company $company, Conversation $conversation, string $message): void
    {
        $lowerMessage = strtolower(trim($message));

        // Check for appointment trigger
        if (in_array($lowerMessage, ['agendar', 'agendamento', 'marcar', 'schedule', 'appointment'])) {
            $this->startAppointmentFlow($user, $company, $conversation);
            return;
        }

        // Check for cancel keyword
        if (in_array($lowerMessage, ['cancelar', 'sair', 'cancel', 'exit'])) {
            $conversation->update(['step' => 'start', 'data' => []]);
            $this->whatsAppService->sendMessage($company, $user, '❌ Agendamento cancelado. Digite AGENDAR quando quiser agendar!');
            return;
        }

        // Route based on current step
        match ($conversation->step) {
            'start' => $this->handleStartStep($user, $company, $conversation, $message),
            'service_selection' => $this->handleServiceSelection($user, $company, $conversation, $message),
            'barber_selection' => $this->handleBarberSelection($user, $company, $conversation, $message),
            'date_selection' => $this->handleDateSelection($user, $company, $conversation, $message),
            'time_selection' => $this->handleTimeSelection($user, $company, $conversation, $message),
            'confirmation' => $this->handleConfirmation($user, $company, $conversation, $message),
            default => $this->handleStartStep($user, $company, $conversation, $message),
        };
    }

    /**
     * Handle start step
     */
    protected function handleStartStep(User $user, Company $company, Conversation $conversation, string $message): void
    {
        if ($this->aiService->isGreeting($message)) {
            $greeting = $this->aiService->getGreetingResponse($user->name);
            $this->whatsAppService->sendMessage($company, $user, $greeting);
        } else {
            $response = $this->aiService->generateResponse($message, "Você é um assistente de barbearia. Sempre direcione para agendamentos.");
            $this->whatsAppService->sendMessage($company, $user, $response);
        }

        $this->whatsAppService->sendMessage($company, $user, "\n\n💈 Digite *AGENDAR* para marcar seu corte!");
    }

    /**
     * Start appointment flow
     */
    protected function startAppointmentFlow(User $user, Company $company, Conversation $conversation): void
    {
        $services = Service::where('company_id', $company->id)
            ->where('active', true)
            ->get();

        if ($services->isEmpty()) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Desculpe, não há serviços disponíveis no momento.');
            return;
        }

        $conversation->update([
            'step' => 'service_selection',
            'data' => [],
        ]);

        $serviceList = $services->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'price' => $s->price,
            'duration_minutes' => $s->duration_minutes,
        ])->toArray();

        $message = $this->whatsAppService->formatServiceList($serviceList);
        $this->whatsAppService->sendMessage($company, $user, $message);
        $this->whatsAppService->sendMessage($company, $user, "Digite o número do serviço desejado. Ex: *1*");
    }

    /**
     * Handle service selection
     */
    protected function handleServiceSelection(User $user, Company $company, Conversation $conversation, string $message): void
    {
        $services = Service::where('company_id', $company->id)
            ->where('active', true)
            ->get();

        $index = (int)$message - 1;

        if ($index < 0 || $index >= $services->count()) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Serviço inválido. Tente novamente (ex: *1*)');
            return;
        }

        $selectedService = $services->get($index);

        $conversation->update([
            'step' => 'date_selection',
            'data' => ['service_id' => $selectedService->id],
        ]);

        $this->whatsAppService->sendMessage(
            $company,
            $user,
            "✂️ Serviço selecionado: *{$selectedService->name}*\n\nDigite a data desejada (formato: DD/MM/YYYY). Ex: *15/05/2026*"
        );
    }

    /**
     * Handle date selection
     */
    protected function handleDateSelection(User $user, Company $company, Conversation $conversation, string $message): void
    {
        try {
            $date = Carbon::createFromFormat('d/m/Y', $message);
        } catch (\Exception $e) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Data inválida. Use o formato DD/MM/YYYY (ex: *15/05/2026*)');
            return;
        }

        if ($date->isPast()) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Data não pode ser no passado. Escolha uma data futura.');
            return;
        }

        $serviceId = $conversation->data['service_id'];
        $service = Service::find($serviceId);

        $availableBarbers = $this->availabilityService->getAvailableBarbersForService($service, $date);

        if (empty($availableBarbers)) {
            $this->whatsAppService->sendMessage($company, $user, "❌ Nenhum barbeiro disponível para {$date->format('d/m/Y')}. Escolha outra data.");
            return;
        }

        $conversation->update([
            'step' => 'barber_selection',
            'data' => [
                'service_id' => $serviceId,
                'date' => $date->format('Y-m-d'),
            ],
        ]);

        $barberList = array_map(fn ($b) => ['id' => $b['id'], 'name' => $b['name']], $availableBarbers);
        $message = $this->whatsAppService->formatBarberList($barberList);
        $this->whatsAppService->sendMessage($company, $user, $message);
        $this->whatsAppService->sendMessage($company, $user, "Digite o número do barbeiro. Ex: *1*");
    }

    /**
     * Handle barber selection
     */
    protected function handleBarberSelection(User $user, Company $company, Conversation $conversation, string $message): void
    {
        $data = $conversation->data;
        $serviceId = $data['service_id'];
        $date = Carbon::parse($data['date']);

        $service = Service::find($serviceId);
        $availableBarbers = $this->availabilityService->getAvailableBarbersForService($service, $date);

        $index = (int)$message - 1;

        if ($index < 0 || $index >= count($availableBarbers)) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Barbeiro inválido. Tente novamente.');
            return;
        }

        $selectedBarber = $availableBarbers[$index];
        $barber = Barber::find($selectedBarber['id']);

        $conversation->update([
            'step' => 'time_selection',
            'data' => [
                'service_id' => $serviceId,
                'date' => $data['date'],
                'barber_id' => $barber->id,
            ],
        ]);

        $slots = $this->availabilityService->getAvailableSlotsForBarber($barber, $date, $service);
        $message = $this->whatsAppService->formatTimeSlotsList($slots);
        $this->whatsAppService->sendMessage($company, $user, $message);
        $this->whatsAppService->sendMessage($company, $user, "Digite o número do horário. Ex: *1*");
    }

    /**
     * Handle time selection
     */
    protected function handleTimeSelection(User $user, Company $company, Conversation $conversation, string $message): void
    {
        $data = $conversation->data;
        $serviceId = $data['service_id'];
        $date = Carbon::parse($data['date']);
        $barberId = $data['barber_id'];

        $service = Service::find($serviceId);
        $barber = Barber::find($barberId);

        $slots = $this->availabilityService->getAvailableSlotsForBarber($barber, $date, $service);

        $index = (int)$message - 1;

        if ($index < 0 || $index >= count($slots)) {
            $this->whatsAppService->sendMessage($company, $user, '❌ Horário inválido. Tente novamente.');
            return;
        }

        $selectedTime = $slots[$index]['time'];

        $conversation->update([
            'step' => 'confirmation',
            'data' => [
                'service_id' => $serviceId,
                'date' => $data['date'],
                'barber_id' => $barberId,
                'time' => $selectedTime,
            ],
        ]);

        $confirmationMessage = "✅ *Resumo do seu agendamento:*\n\n" .
            "📅 Data: {$date->format('d/m/Y')}\n" .
            "⏰ Horário: {$selectedTime}\n" .
            "👨‍💼 Barbeiro: {$barber->name}\n" .
            "✂️ Serviço: {$service->name}\n" .
            "💰 Valor: R$ {$service->price}\n\n" .
            "Deseja confirmar? Digite *SIM* para confirmar ou *NÃO* para cancelar.";

        $this->whatsAppService->sendMessage($company, $user, $confirmationMessage);
    }

    /**
     * Handle confirmation
     */
    protected function handleConfirmation(User $user, Company $company, Conversation $conversation, string $message): void
    {
        $lowerMessage = strtolower(trim($message));

        if (!in_array($lowerMessage, ['sim', 'yes', 'confirmar', 'confirm'])) {
            $conversation->update(['step' => 'start', 'data' => []]);
            $this->whatsAppService->sendMessage($company, $user, '❌ Agendamento cancelado. Digite AGENDAR quando quiser agendar novamente!');
            return;
        }

        $data = $conversation->data;
        $service = Service::find($data['service_id']);
        $barber = Barber::find($data['barber_id']);
        $date = Carbon::parse($data['date']);

        try {
            $appointment = $this->appointmentService->createAppointment(
                $user,
                $barber,
                $service,
                $date,
                $data['time']
            );

            $this->appointmentService->confirmAppointment($appointment);

            $confirmationMessage = $this->whatsAppService->formatAppointmentConfirmation($user, $appointment);
            $this->whatsAppService->sendMessage($company, $user, $confirmationMessage);

            $conversation->update(['step' => 'start', 'data' => []]);
        } catch (\Exception $e) {
            $this->whatsAppService->sendMessage($company, $user, "❌ Erro ao confirmar agendamento: " . $e->getMessage());
            $conversation->update(['step' => 'start', 'data' => []]);
        }
    }
}
