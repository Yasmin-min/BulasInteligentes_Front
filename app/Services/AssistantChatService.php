<?php

namespace App\Services;

use App\Models\AssistantMessage;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AssistantChatService
{
    private array $config;

    public function __construct(
        private readonly HttpFactory $http,
        private readonly MedicationInfoService $medicationInfoService,
    ) {
        $this->config = config('services.openai');

        if (empty($this->config['api_key'])) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }
    }

    /**
     * Handle a patient assistant interaction.
     *
     * @return array{message:string,medication?:array<string,mixed>,usage?:array<string,mixed>,status:string}
     */
    public function converse(User $user, string $question, ?string $medicationContext = null, bool $allowRecommendations = false): array
    {
        $medicationName = $medicationContext ?: $this->extractMedicationName($question);

        if (! $medicationName) {
            return $this->respondWithoutMedication($user, $question);
        }

        $medicationResult = $this->medicationInfoService->fetch($medicationName, $user->id);

        if ($medicationResult['status'] === 'error') {
            return [
                'status' => 'error',
                'message' => $medicationResult['message'] ?? 'Não foi possível consultar o medicamento.',
            ];
        }

        if ($medicationResult['status'] === 'missing') {
            return [
                'status' => 'missing',
                'message' => $medicationResult['message'] ?? 'Medicamento não localizado. Nossa equipe será avisada.',
            ];
        }

        $context = $this->buildPatientContext($user);
        $context['medication'] = $this->sanitizeMedicationForPrompt($medicationResult['medication']);
        $context['allow_recommendations'] = $allowRecommendations;

        $response = $this->callAssistantModel($question, $medicationName, $context);

        $this->storeConversation($user, $question, $response);

        return [
            'status' => 'fulfilled',
            'message' => $response['message'],
            'medication' => $context['medication'],
            'usage' => $response['usage'],
        ];
    }

    protected function respondWithoutMedication(User $user, string $question): array
    {
        $context = $this->buildPatientContext($user);

        $response = $this->callGeneralAssistantModel($question, $context);

        $this->storeConversation($user, $question, $response);

        return [
            'status' => 'fulfilled',
            'message' => $response['message'],
            'medication' => null,
            'usage' => $response['usage'],
        ];
    }

    protected function buildPatientContext(User $user): array
    {
        $allergies = [];
        $courses = [];
        $plans = [];

        try {
            $allergies = $user->allergies()
                ->get(['allergen', 'reaction', 'severity'])
                ->map(fn ($allergy) => [
                    'allergen' => $allergy->allergen,
                    'reaction' => $allergy->reaction,
                    'severity' => $allergy->severity,
                ])
                ->toArray();
        } catch (Throwable $exception) {
            Log::warning('Falha ao carregar alergias para contexto da IA.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            $courses = $user->medicationCourses()
                ->where('is_active', true)
                ->get(['medication_name', 'dosage', 'frequency', 'interval_minutes', 'start_at', 'end_at'])
                ->map(fn ($course) => [
                    'medication_name' => $course->medication_name,
                    'dosage' => $course->dosage,
                    'frequency' => $course->frequency,
                    'interval_minutes' => $course->interval_minutes,
                    'start_at' => optional($course->start_at)->toIso8601String(),
                    'end_at' => optional($course->end_at)->toIso8601String(),
                ])
                ->toArray();
        } catch (Throwable $exception) {
            Log::warning('Falha ao carregar tratamentos ativos para contexto da IA.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        try {
            $plans = $user->treatmentPlans()
                ->where('is_active', true)
                ->with(['items.schedules' => fn ($query) => $query->orderBy('scheduled_at')->limit(10)])
                ->get()
                ->map(fn (TreatmentPlan $plan) => [
                    'title' => $plan->title,
                    'instructions' => $plan->instructions,
                    'start_at' => optional($plan->start_at)->toIso8601String(),
                    'end_at' => optional($plan->end_at)->toIso8601String(),
                    'items' => $plan->items->map(fn ($item) => [
                        'medication_name' => $item->medication_name,
                        'dosage' => $item->dosage,
                        'interval_minutes' => $item->interval_minutes,
                        'next_doses' => $item->schedules->map(fn ($schedule) => [
                            'scheduled_at' => optional($schedule->scheduled_at)->toIso8601String(),
                            'status' => $schedule->status,
                        ]),
                    ]),
                ])
                ->toArray();
        } catch (Throwable $exception) {
            Log::warning('Falha ao carregar planos de tratamento para contexto da IA.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return [
            'allergies' => $allergies,
            'active_medications' => $courses,
            'treatment_plans' => $plans,
        ];
    }

    protected function sanitizeMedicationForPrompt($medication): array
    {
        return [
            'name' => $medication->name,
            'posology' => $medication->posology,
            'indications' => $medication->indications,
            'contraindications' => $medication->contraindications,
            'interaction_alerts' => $medication->interaction_alerts,
            'composition' => $medication->composition,
            'disclaimer' => $medication->disclaimer,
            'sources' => $medication->sources,
        ];
    }

    protected function callAssistantModel(string $question, string $medicationName, array $context): array
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
Você é um assistente de saúde digital especializado em medicamentos. Utilize apenas dados fornecidos no contexto a seguir.
Regras:
- Nunca substitua a avaliação médica profissional.
- Indique quando a informação não estiver disponível.
- Reforce que alergias e tratamentos vigentes devem ser confirmados com o médico.
- Priorize segurança e clareza para leigos.
- Se identificar alergias ou potenciais interações, destaque em formato de alerta.
- Caso não tenha dados suficientes, sugira que o paciente procure orientação profissional.
- Responda de forma breve (no máximo 3 frases curtas) para economizar tokens.

Contexto do paciente (JSON):
{$contextJson}
PROMPT;

        $payload = [
            'model' => $this->config['default_model'] ?? 'gpt-4.1',
            'temperature' => (float) ($this->config['temperature'] ?? 0.2),
            'max_tokens' => min(1024, (int) ($this->config['max_tokens'] ?? 2048)),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role' => 'user',
                    'content' => "Pergunta do paciente: {$question}\nMedicamento alvo: {$medicationName}",
                ],
            ],
        ];

        $response = $this->sendOpenAiRequest($payload);

        $message = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

        if ($message === '') {
            throw new RuntimeException('IA retornou resposta vazia.');
        }

        return [
            'message' => $message,
            'usage' => $response['usage'] ?? [],
        ];
    }

    protected function sendOpenAiRequest(array $payload): array
    {
        /** @var Response $response */
        $response = $this->http
            ->baseUrl(rtrim($this->config['base_uri'] ?? 'https://api.openai.com/v1', '/'))
            ->asJson()
            ->withToken($this->config['api_key'])
            ->timeout((int) ($this->config['timeout'] ?? 30))
            ->post('chat/completions', $payload);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI request failed: '.$response->body(), $response->status());
        }

        return $response->json();
    }

    protected function storeConversation(User $user, string $question, array $response): void
    {
        try {
            DB::transaction(function () use ($user, $question, $response): void {
                $conversationId = (string) Str::uuid();

                AssistantMessage::create([
                    'user_id' => $user->id,
                    'message_uuid' => $conversationId,
                    'role' => 'user',
                    'content' => $question,
                ]);

                AssistantMessage::create([
                    'user_id' => $user->id,
                    'message_uuid' => (string) Str::uuid(),
                    'role' => 'assistant',
                    'content' => $response['message'],
                    'metadata' => [],
                    'prompt_tokens' => Arr::get($response, 'usage.prompt_tokens'),
            'completion_tokens' => Arr::get($response, 'usage.completion_tokens'),
            'total_tokens' => Arr::get($response, 'usage.total_tokens'),
        ]);

                $user->forceFill([
                    'last_assistant_interaction_at' => Carbon::now(),
                ])->save();
            });
        } catch (Throwable $exception) {
            Log::warning('Falha ao registrar conversa com assistente.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function extractMedicationName(string $question): ?string
    {
        $pattern = '/(?:sobre|do|da|de|tomar|usar)\s+([A-Za-zÀ-ÖØ-öø-ÿ0-9][A-Za-zÀ-ÖØ-öø-ÿ0-9\s\-]+)/u';
        if (preg_match($pattern, Str::lower($question), $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function callGeneralAssistantModel(string $question, array $context): array
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $systemPrompt = <<<PROMPT
Você é um assistente de saúde digital que responde perguntas gerais sobre o próprio assistente ou dúvidas rápidas quando nenhum medicamento é informado.
Seja educado e direto, limitando-se a respostas curtas (até 3 frases curtas) para economizar tokens.
Sempre lembre que a IA não substitui avaliação médica.

Contexto do paciente (JSON):
{$contextJson}
PROMPT;

        $payload = [
            'model' => $this->config['default_model'] ?? 'gpt-4.1',
            'temperature' => (float) ($this->config['temperature'] ?? 0.2),
            'max_tokens' => min(512, (int) ($this->config['max_tokens'] ?? 1024)),
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Pergunta rápida do usuário: {$question}"],
            ],
        ];

        $response = $this->sendOpenAiRequest($payload);

        $message = trim((string) ($response['choices'][0]['message']['content'] ?? ''));

        if ($message === '') {
            throw new RuntimeException('IA retornou resposta vazia.');
        }

        return [
            'message' => $message,
            'usage' => $response['usage'] ?? [],
        ];
    }
}
