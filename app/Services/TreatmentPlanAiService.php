<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TreatmentPlanAiService
{
    private array $config;

    public function __construct(private readonly HttpFactory $http)
    {
        $this->config = config('services.openai');

        if (empty($this->config['api_key'])) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }
    }

    /**
     * Create a draft treatment plan from a short summary.
     *
     * @return array{title:string,instructions:string,items:array<int,array<string,mixed>>,usage?:array<string,mixed>}
     */
    public function draft(User $user, string $summary, ?string $startAt = null, ?string $title = null): array
    {
        $context = $this->buildContext($user, $startAt);

        $payload = [
            'model' => $this->config['default_model'] ?? 'gpt-4.1',
            'temperature' => (float) ($this->config['temperature'] ?? 0.1),
            'max_tokens' => min(1024, (int) ($this->config['max_tokens'] ?? 2048)),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($context),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt($summary, $startAt, $title),
                ],
            ],
        ];

        $response = $this->sendOpenAiRequest($payload);

        $message = trim((string) Arr::get($response, 'choices.0.message.content'));

        $decoded = json_decode($message, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('IA retornou um formato inesperado para o plano.');
        }

        $items = collect($decoded['items'] ?? [])
            ->take(5)
            ->map(function (array $item) use ($startAt) {
                $firstDose = Arr::get($item, 'first_dose_at') ?? $startAt;

                $normalized = [
                    'medication_name' => Arr::get($item, 'medication_name', ''),
                    'dosage' => Arr::get($item, 'dosage', ''),
                    'route' => Arr::get($item, 'route', ''),
                    'instructions' => Arr::get($item, 'instructions', ''),
                    'interval_minutes' => $this->toNullableInt(Arr::get($item, 'interval_minutes')),
                    'total_doses' => $this->toNullableInt(Arr::get($item, 'total_doses')),
                    'duration_days' => $this->toNullableInt(Arr::get($item, 'duration_days')),
                    'first_dose_at' => $firstDose ? Carbon::parse($firstDose)->toIso8601String() : null,
                    'specific_times' => $this->normalizeTimes(Arr::get($item, 'specific_times', [])),
                ];

                if (! $normalized['interval_minutes'] && empty($normalized['specific_times'])) {
                    $normalized['interval_minutes'] = 480;

                    if (! $normalized['total_doses'] && $normalized['duration_days']) {
                        $normalized['total_doses'] = max(1, (int) $normalized['duration_days'] * 3);
                    }
                }

                return $normalized;
            })
            ->values()
            ->all();

        if (empty($items)) {
            throw new RuntimeException('A IA não conseguiu sugerir horários com as informações fornecidas.');
        }

        return [
            'title' => $decoded['title'] ?? ($title ?: 'Plano sugerido'),
            'instructions' => $decoded['instructions'] ?? 'Revise e ajuste se necessário.',
            'items' => $items,
            'usage' => $response['usage'] ?? [],
        ];
    }

    /**
     * Draft a treatment plan from OCR-extracted prescription items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{title:string,instructions:string,items:array<int,array<string,mixed>>,usage?:array<string,mixed>}
     */
    public function draftFromPrescription(User $user, array $items, ?string $rawText = null, ?string $startAt = null): array
    {
        $context = $this->buildContext($user, $startAt);
        $context['prescription_text'] = $rawText;

        $payload = [
            'model' => $this->config['default_model'] ?? 'gpt-4.1',
            'temperature' => (float) ($this->config['temperature'] ?? 0.1),
            'max_tokens' => min(1024, (int) ($this->config['max_tokens'] ?? 2048)),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildPrescriptionSystemPrompt($context, $items),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrescriptionUserPrompt($items, $startAt),
                ],
            ],
        ];

        $response = $this->sendOpenAiRequest($payload);
        $message = trim((string) Arr::get($response, 'choices.0.message.content'));
        $decoded = json_decode($message, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('IA não retornou um plano estruturado da receita.');
        }

        $normalizedItems = collect($decoded['items'] ?? [])
            ->take(8)
            ->map(function (array $item) use ($startAt) {
                $firstDose = Arr::get($item, 'first_dose_at') ?? $startAt;

                $normalized = [
                    'medication_name' => Arr::get($item, 'medication_name', ''),
                    'dosage' => Arr::get($item, 'dosage', ''),
                    'route' => Arr::get($item, 'route', ''),
                    'instructions' => Arr::get($item, 'instructions', ''),
                    'interval_minutes' => $this->toNullableInt(Arr::get($item, 'interval_minutes')),
                    'total_doses' => $this->toNullableInt(Arr::get($item, 'total_doses')),
                    'duration_days' => $this->toNullableInt(Arr::get($item, 'duration_days')),
                    'first_dose_at' => $firstDose ? Carbon::parse($firstDose)->toIso8601String() : null,
                    'specific_times' => $this->normalizeTimes(Arr::get($item, 'specific_times', [])),
                ];

                if (! $normalized['interval_minutes'] && empty($normalized['specific_times'])) {
                    $normalized['interval_minutes'] = 480;

                    if (! $normalized['total_doses'] && $normalized['duration_days']) {
                        $normalized['total_doses'] = max(1, (int) $normalized['duration_days'] * 3);
                    }
                }

                return $normalized;
            })
            ->values()
            ->all();

        if (empty($normalizedItems)) {
            throw new RuntimeException('A IA não conseguiu propor horários com os medicamentos lidos.');
        }

        return [
            'title' => $decoded['title'] ?? 'Plano da receita',
            'instructions' => $decoded['instructions'] ?? 'Revise as posologias sugeridas antes de salvar.',
            'items' => $normalizedItems,
            'usage' => $response['usage'] ?? [],
        ];
    }

    protected function buildContext(User $user, ?string $startAt): array
    {
        return [
            'patient' => [
                'allergies_count' => $user->allergies()->count(),
                'active_medications' => $user->medicationCourses()->where('is_active', true)->count(),
            ],
            'start_at' => $startAt,
        ];
    }

    protected function buildSystemPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Você monta um plano de tratamento farmacológico a partir de poucas pistas.
Responda **apenas** um JSON com os campos: title, instructions, items (array).
Cada item deve conter: medication_name, dosage, route, instructions, interval_minutes (minutos), total_doses, duration_days, first_dose_at (ISO8601), specific_times (lista de horários HH:MM para uso diário quando fizer sentido).
Regras:
- Prefira 3 a 4 horários diários se não for informado intervalo.
- Se duration_days estiver presente, calcule total_doses coerente; caso contrário, informe total_doses básico.
- Utilize start_at do contexto como referência para a primeira dose quando fizer sentido.
- Seja conservador e prático, nunca invente posologias incomuns.
- Mantenha respostas curtas para economizar tokens.

Contexto auxiliar:
{$contextJson}
PROMPT;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function buildPrescriptionSystemPrompt(array $context, array $items): string
    {
        $context['items'] = $items;
        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Você é um farmacêutico digital que converte receitas em um plano de doses.
Responda apenas um JSON com campos: title, instructions, items (array).
Cada item: medication_name, dosage, route, instructions, interval_minutes, total_doses, duration_days, first_dose_at (ISO8601), specific_times (HH:MM list when needed).
Regras:
- Evite conflitos/competição de absorção: espaçe medicamentos que possam interagir em pelo menos 2h quando não souber detalhes.
- Mantenha intervalos regulares e horários realistas (manhã, almoço, tarde, noite) para maximizar adesão.
- Nunca sugira combinações perigosas; se dúvida, sinalize na instrução do item (ex.: "verificar interação com médico").
- Respeite dose/duração da receita; se ausente, proponha um esquema conservador.
- Seja conciso para economizar tokens.

Contexto do paciente e receita:
{$contextJson}
PROMPT;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function buildPrescriptionUserPrompt(array $items, ?string $startAt): string
    {
        $startText = $startAt ? "Iniciar em: {$startAt}" : 'Sem data/hora de início informada';
        $list = collect($items)
            ->map(fn ($item) => sprintf('- %s %s %s', $item['medication_name'] ?? 'Medicamento', $item['dosage'] ?? '', $item['instructions'] ?? ''))
            ->implode("\n");

        return "Itens identificados na receita:\n{$list}\n{$startText}";
    }

    protected function buildUserPrompt(string $summary, ?string $startAt, ?string $title): string
    {
        $startText = $startAt ? "Data/hora inicial sugerida: {$startAt}" : 'Sem data inicial informada';
        $titleText = $title ? "Título sugerido: {$title}" : 'Sem título sugerido';

        return "Resumo do tratamento: {$summary}\n{$startText}\n{$titleText}";
    }

    protected function sendOpenAiRequest(array $payload): array
    {
        try {
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
        } catch (Throwable $exception) {
            Log::warning('Falha ao consultar IA para plano de tratamento.', [
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Não foi possível gerar o plano automaticamente agora.');
        }
    }

    protected function normalizeTimes(mixed $times): array
    {
        if (! is_array($times)) {
            return [];
        }

        return collect($times)
            ->map(fn ($time) => is_string($time) ? trim($time) : null)
            ->filter(fn ($time) => $time && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time))
            ->values()
            ->all();
    }

    protected function toNullableInt(mixed $value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
