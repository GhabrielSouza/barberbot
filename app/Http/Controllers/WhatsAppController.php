<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\BotService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected BotService $botService;

    public function __construct(BotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Webhook endpoint to receive messages from Z-API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature from Z-API
            $companyId = $request->header('X-Company-ID');
            $company = Company::findOrFail($companyId);

            // Extract message data from Z-API webhook
            $data = $request->json()->all();

            Log::info('WhatsApp Webhook Received', [
                'company_id' => $companyId,
                'data' => $data,
            ]);

            // Validate incoming data
            if (!isset($data['phone']) || !isset($data['message'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields',
                ], 400);
            }

            // Process message through bot service
            $this->botService->processMessage(
                $company,
                $data['phone'],
                $data['message']
            );

            return response()->json([
                'success' => true,
                'message' => 'Message processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing message',
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     *
     * @return JsonResponse
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Webhook is working correctly',
            'timestamp' => now(),
        ]);
    }
}
