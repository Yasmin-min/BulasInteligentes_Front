<?php

namespace App\Services;

use App\Models\Medication;
use App\Models\MedicationQuery;
use App\Models\MissingMedication;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MedicationInfoService
{
    private array $config;

    public function __construct(
        private readonly HttpFactory $http,
        array $config = [],
    ) {
        $this->config = $config ?: config('services.openai');

        if (empty($this->config['api_key'])) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }
    }

    /**
     * Retrieve medication details, using cached data when possible.
     *
     * @return array{status:string,from_cache:bool,medication:Medication|null,response?:array,message?:string,missing?:MissingMedication}
     */
    public function fetch(string $query, ?int $userId = null, bool $forceRefresh = false): array
    {
        $normalized = $this->normalizeQuery($query);

        $cached = null;

        try {
            /** @var Medication|null $cached */
            $cached = Medication::where('slug', $normalized['slug'])->first();
        } catch (Throwable $lookupException) {
            Log::warning('Não foi possível verificar cache de medicação.', [
                'exception' => $lookupException->getMessage(),
            ]);
        }

        if (! $forceRefresh && $cached) {
            $this->recordQuery($query, $normalized, $userId, $cached, [
                'status' => 'cache-hit',
                'from_cache' => true,
            ]);

            return [
                'status' => 'cache-hit',
                'from_cache' => true,
                'medication' => $cached,
            ];
        }

        $startedAt = microtime(true);

        try {
            $response = $this->callModel($query);
        } catch (Throwable $exception) {
            Log::error('Medication AI request failed', [
                'query' => $query,
                'exception' => $exception,
            ]);

            $this->recordQuery($query, $normalized, $userId, null, [
                'status' => 'error',
                'from_cache' => false,
            ]);

            return [
                'status' => 'error',
                'from_cache' => false,
                'medication' => null,
                'message' => 'Não foi possível consultar a IA no momento.',
            ];
        }

        $latency = (int) round((microtime(true) - $startedAt) * 1000);
        $parsed = $response['parsed'];

        if (! Arr::get($parsed, 'found', false)) {
            $missing = $this->recordMissingMedication($normalized, $parsed);
            $this->recordQuery($query, $normalized, $userId, null, [
                'status' => 'missing',
                'from_cache' => false,
                'latency_ms' => $latency,
            ]);

            return [
                'status' => 'missing',
                'from_cache' => false,
                'medication' => null,
                'message' => Arr::get($parsed, 'message', 'Medicamento não encontrado na base de conhecimento.'),
                'missing' => $missing,
            ];
        }

        $medication = $this->storeMedication($normalized, $parsed, $response);

        $this->recordQuery($query, $normalized, $userId, $medication, [
            'status' => 'fulfilled',
            'from_cache' => false,
            'latency_ms' => $latency,
            'usage' => $response['usage'],
        ]);

        return [
            'status' => 'fulfilled',
            'from_cache' => false,
            'medication' => $medication,
            'response' => $response,
        ];
    }

    /**
     * Normalize textual query to support caching and lookups.
     *
     * @return array{original:string,normalized:string,slug:string}
     */
    protected function normalizeQuery(string $query): array
    {
        $original = trim($query);
        $normalized = Str::of($original)
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->value();

        $slug = Str::slug($normalized);

        if ($slug === '') {
            $slug = Str::slug($original);
        }

        return [
            'original' => $original,
            'normalized' => $normalized,
            'slug' => $slug,
        ];
    }

    /**
     * Build the payload sent to OpenAI.
     *
     * @return array<string, mixed>
     */
    protected function buildPayload(string $query): array
    {
        $schema = [
            'name' => 'medication_information',
            'schema' => [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['found', 'disclaimer'],
                'properties' => [
                    'found' => [
                        'type' => 'boolean',
                        'description' => 'Indica se a medicação foi encontrada com informações confiáveis.',
                    ],
                    'medication_name' => [
                        'type' => 'string',
                        'description' => 'Nome completo do medicamento, conforme bula oficial.',
                    ],
                    'alternate_names' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Outros nomes, sinônimos ou marcas comerciais relevantes.',
                    ],
                    'posology' => [
                        'type' => 'string',
                        'description' => 'Orientações gerais de posologia conforme bula.',
                    ],
                    'indications' => [
                        'type' => 'string',
                        'description' => 'Principais indicações terapêuticas do medicamento.',
                    ],
                    'contraindications' => [
                        'type' => 'string',
                        'description' => 'Contraindicações e principais alertas.',
                    ],
                    'interaction_alerts' => [
                        'type' => 'string',
                        'description' => 'Interações medicamentosas e cuidados importantes.',
                    ],
                    'composition' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['component', 'dosage_form', 'strength', 'half_life_hours', 'notes'],
                            'additionalProperties' => false,
                            'properties' => [
                                'component' => ['type' => 'string'],
                                'dosage_form' => ['type' => 'string'],
                                'strength' => ['type' => 'string'],
                                'half_life_hours' => ['type' => 'string', 'description' => 'Meia-vida estimada ou tempo no organismo.'],
                                'notes' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'human_summary' => [
                        'type' => 'string',
                        'description' => 'Resumo compreensível para leigos com linguagem clara.',
                    ],
                    'storage_guidance' => [
                        'type' => 'string',
                        'description' => 'Recomendações de armazenamento quando disponíveis.',
                    ],
                    'disclaimer' => [
                        'type' => 'string',
                        'description' => 'Aviso deixando claro que não substitui avaliação médica.',
                    ],
                    'sources' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'description' => 'Fontes consultadas ou sugeridas (bula oficial, agências regulatórias etc.).',
                    ],
                    'message' => [
                        'type' => 'string',
                        'description' => 'Mensagem explicativa quando found=false.',
                    ],
                ],
            ],
        ];

        $instructions = <<<PROMPT
Você é um assistente farmacêutico. Forneça apenas informações verificáveis baseadas em bulas oficiais,
evite especulações e deixe claro que as informações não substituem avaliação médica.
Responda exclusivamente no formato JSON solicitado. Se não tiver confiança, responda com found=false.
Inclua sempre um campo "disclaimer" enfatizando que o usuário deve consultar um profissional de saúde.
Quando uma informação obrigatória não estiver disponível, utilize o texto "Não informado" sem inventar dados.
PROMPT;

        return [
            'model' => $this->config['default_model'] ?? 'gpt-4.1',
            'temperature' => (float) ($this->config['temperature'] ?? 0),
            'max_tokens' => (int) ($this->config['max_tokens'] ?? 2048),
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => $schema,
            ],
            'messages' => [
                ['role' => 'system', 'content' => $instructions],
                ['role' => 'user', 'content' => "Medicamento: {$query}"],
            ],
        ];
    }

    /**
     * Issue a request to the OpenAI API.
     *
     * @return array{parsed:array<string,mixed>,usage:array<string,mixed>,raw:array<string,mixed>,latency_ms:int}
     */
    protected function callModel(string $query): array
    {
        $payload = $this->buildPayload($query);
        $start = hrtime(true);

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

        $latencyMs = (int) round((hrtime(true) - $start) / 1_000_000);

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
        }

        $parsed = json_decode($content, true);
        if (! is_array($parsed)) {
            throw new RuntimeException('OpenAI response is not valid JSON: '.$content);
        }

        return [
            'parsed' => $parsed,
            'usage' => $body['usage'] ?? [],
            'raw' => $body,
            'latency_ms' => $latencyMs,
        ];
    }

    /**
     * Persist medication details returned from the AI.
     */
    protected function storeMedication(array $normalized, array $parsed, array $response): Medication
    {
        $name = Arr::get($parsed, 'medication_name') ?: $normalized['original'];
        $slug = Str::slug($name) ?: $normalized['slug'];

        $attributes = [
            'name' => $name,
            'human_summary' => Arr::get($parsed, 'human_summary'),
            'posology' => Arr::get($parsed, 'posology'),
            'indications' => Arr::get($parsed, 'indications'),
            'contraindications' => Arr::get($parsed, 'contraindications'),
            'interaction_alerts' => Arr::get($parsed, 'interaction_alerts'),
            'composition' => Arr::get($parsed, 'composition'),
            'half_life_notes' => $this->extractHalfLifeNotes($parsed),
            'storage_guidance' => Arr::get($parsed, 'storage_guidance'),
            'disclaimer' => Arr::get($parsed, 'disclaimer'),
            'sources' => Arr::get($parsed, 'sources'),
            'source' => 'openai',
            'fetched_at' => now(),
            'raw_payload' => $response['raw'],
        ];

        try {
            /** @var Medication $medication */
            $medication = Medication::updateOrCreate(
                ['slug' => $slug],
                $attributes
            );
        } catch (Throwable $exception) {
            Log::warning('Falha ao persistir medicamento, retornando dados em memória.', [
                'slug' => $slug,
                'exception' => $exception->getMessage(),
            ]);

            /** @var Medication $medication */
            $medication = (new Medication())->forceFill(array_merge(['slug' => $slug], $attributes));
        }

        return $medication;
    }

    protected function extractHalfLifeNotes(array $parsed): ?string
    {
        $parts = [];
        $composition = Arr::get($parsed, 'composition', []);
        foreach ($composition as $item) {
            if (! empty($item['component']) && ! empty($item['half_life_hours'])) {
                $parts[] = "{$item['component']}: {$item['half_life_hours']}";
            }
        }

        return empty($parts) ? null : implode("\n", $parts);
    }

    protected function recordQuery(string $query, array $normalized, ?int $userId, ?Medication $medication, array $context): void
    {
        try {
            MedicationQuery::create([
                'user_id' => $userId,
                'medication_id' => $medication?->id,
                'query' => $query,
                'normalized_query' => $normalized['normalized'],
                'status' => $context['status'] ?? 'fulfilled',
                'from_cache' => $context['from_cache'] ?? false,
                'completion_tokens' => $context['usage']['completion_tokens'] ?? null,
                'prompt_tokens' => $context['usage']['prompt_tokens'] ?? null,
                'total_tokens' => $context['usage']['total_tokens'] ?? null,
                'latency_ms' => $context['latency_ms'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Falha ao registrar consulta de medicação.', [
                'query' => $query,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function recordMissingMedication(array $normalized, array $parsed): MissingMedication
    {
        $slug = $normalized['slug'];
        try {
            $missing = MissingMedication::where('slug', $slug)->first();

            if ($missing) {
                $missing->incrementOccurrences();

                return $missing;
            }

            return MissingMedication::create([
                'name' => $normalized['original'],
                'slug' => $slug,
                'occurrences' => 1,
                'notes' => Arr::get($parsed, 'message'),
                'last_requested_at' => now(),
                'context' => [
                    'alternate_names' => Arr::get($parsed, 'alternate_names', []),
                ],
            ]);
        } catch (Throwable $exception) {
            Log::warning('Falha ao registrar medicamento ausente.', [
                'slug' => $slug,
                'exception' => $exception->getMessage(),
            ]);

            /** @var MissingMedication $missing */
            $missing = (new MissingMedication())->forceFill([
                'name' => $normalized['original'],
                'slug' => $slug,
                'occurrences' => 1,
                'notes' => Arr::get($parsed, 'message'),
                'last_requested_at' => now(),
                'context' => [
                    'alternate_names' => Arr::get($parsed, 'alternate_names', []),
                ],
            ]);

            return $missing;
        }
    }
}
