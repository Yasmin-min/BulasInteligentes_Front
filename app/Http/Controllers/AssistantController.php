<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assistant\AssistantChatRequest;
use App\Services\AssistantChatService;
use Illuminate\Http\JsonResponse;

class AssistantController extends Controller
{
    public function __construct(private readonly AssistantChatService $service)
    {
    }

    public function query(AssistantChatRequest $request): JsonResponse
    {
        $result = $this->service->converse(
            $request->user(),
            $request->input('question'),
            $request->input('medication_context'),
            $request->boolean('allow_recommendations', false)
        );

        $statusCode = match ($result['status']) {
            'missing' => 404,
            'needs_clarification' => 422,
            'error' => 503,
            default => 200,
        };

        return response()->json($result, $statusCode);
    }
}
