<?php

namespace App\Http\Controllers;

use App\Http\Requests\Medication\QueryMedicationRequest;
use App\Models\Medication;
use App\Models\MissingMedication;
use App\Services\MedicationInfoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class MedicationController extends Controller
{
    public function __construct(private readonly MedicationInfoService $service)
    {
    }

    /**
     * Query the AI (with caching) for medication information.
     */
    public function query(QueryMedicationRequest $request): JsonResponse
    {
        $forceRefresh = $request->boolean('force_refresh', false);

        $result = $this->service->fetch(
            $request->input('query'),
            $request->user()?->id,
            $forceRefresh
        );

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Falha ao consultar o serviço de IA.',
            ], 503);
        }

        if ($result['status'] === 'missing') {
            return response()->json([
                'status' => 'missing',
                'message' => $result['message'] ?? 'Medicamento não encontrado.',
                'missing' => $this->transformMissing($result['missing'] ?? null),
            ], 404);
        }

        $medication = $result['medication'];

        return response()->json([
            'status' => 'fulfilled',
            'from_cache' => $result['from_cache'],
            'medication' => $this->transformMedication($medication),
            'usage' => Arr::get($result, 'response.usage'),
        ]);
    }

    /**
     * Retrieve stored medication information by slug.
     */
    public function show(string $slug): JsonResponse
    {
        try {
            /** @var Medication|null $medication */
            $medication = Medication::where('slug', $slug)->first();
        } catch (Throwable $exception) {
            Log::warning('Falha ao buscar medicação por slug.', [
                'slug' => $slug,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Serviço temporariamente indisponível.',
            ], 503);
        }

        if (! $medication) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Medicamento não encontrado no cache.',
            ], 404);
        }

        return response()->json([
            'status' => 'fulfilled',
            'from_cache' => true,
            'medication' => $this->transformMedication($medication),
        ]);
    }

    /**
     * Transform medication into API-friendly representation.
     *
     * @return array<string, mixed>
     */
    protected function transformMedication(Medication $medication): array
    {
        return [
            'id' => $medication->id,
            'name' => $medication->name,
            'slug' => $medication->slug,
            'human_summary' => $medication->human_summary,
            'posology' => $medication->posology,
            'indications' => $medication->indications,
            'contraindications' => $medication->contraindications,
            'interaction_alerts' => $medication->interaction_alerts,
            'composition' => $medication->composition,
            'half_life_notes' => $medication->half_life_notes,
            'storage_guidance' => $medication->storage_guidance,
            'disclaimer' => $medication->disclaimer,
            'sources' => $medication->sources,
            'fetched_at' => optional($medication->fetched_at)->toIso8601String(),
        ];
    }

    /**
     * Transform missing medication data when the AI cannot find info.
     *
     * @param  \App\Models\MissingMedication|null  $missing
     * @return array<string, mixed>|null
     */
    protected function transformMissing(?MissingMedication $missing): ?array
    {
        if (! $missing) {
            return null;
        }

        return [
            'name' => $missing->name,
            'slug' => $missing->slug,
            'occurrences' => $missing->occurrences,
            'notes' => $missing->notes,
        ];
    }
}
