<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreatmentPlan\RecordDoseRequest;
use App\Http\Requests\TreatmentPlan\StoreTreatmentPlanRequest;
use App\Http\Requests\TreatmentPlan\UpdateTreatmentPlanRequest;
use App\Http\Requests\TreatmentPlan\SuggestTreatmentPlanRequest;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanSchedule;
use App\Services\TreatmentPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreatmentPlanController extends Controller
{
    public function __construct(private readonly TreatmentPlanService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $plans = $request->user()
            ->treatmentPlans()
            ->with(['items.schedules' => fn ($query) => $query->orderBy('scheduled_at')->limit(5)])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $plans->map(fn (TreatmentPlan $plan) => $this->transformPlan($plan)),
        ]);
    }

    public function store(StoreTreatmentPlanRequest $request): JsonResponse
    {
        $plan = $this->service->createPlan($request->user(), $request->validated());

        return response()->json([
            'message' => 'Plano de tratamento criado com sucesso.',
            'data' => $this->transformPlan($plan->load('items.schedules')),
        ], 201);
    }

    public function suggest(SuggestTreatmentPlanRequest $request): JsonResponse
    {
        $draft = $this->service->suggestPlan($request->user(), $request->validated());

        return response()->json([
            'message' => 'Plano sugerido pela IA.',
            'data' => $draft,
        ]);
    }

    public function show(Request $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $treatmentPlan->user_id);

        $treatmentPlan->load(['items.schedules' => fn ($query) => $query->orderBy('scheduled_at')]);

        return response()->json([
            'data' => $this->transformPlan($treatmentPlan),
        ]);
    }

    public function recordDose(RecordDoseRequest $request, TreatmentPlan $treatmentPlan, TreatmentPlanSchedule $schedule): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $treatmentPlan->user_id);
        abort_if($schedule->item->treatment_plan_id !== $treatmentPlan->id, 404, 'Dose não pertence a este plano.');

        $updated = $this->service->recordDose($schedule, $request->validated());

        return response()->json([
            'message' => 'Registro atualizado.',
            'data' => $updated,
        ]);
    }

    public function update(UpdateTreatmentPlanRequest $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $treatmentPlan->user_id);

        $treatmentPlan->fill($request->validated());
        $treatmentPlan->save();

        $treatmentPlan->load(['items.schedules' => fn ($query) => $query->orderBy('scheduled_at')->limit(10)]);

        return response()->json([
            'message' => 'Plano atualizado.',
            'data' => $this->transformPlan($treatmentPlan),
        ]);
    }

    public function destroy(Request $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $treatmentPlan->user_id);

        $treatmentPlan->delete();

        return response()->json([
            'message' => 'Plano removido.',
        ]);
    }

    protected function transformPlan(TreatmentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'status' => $plan->status,
            'instructions' => $plan->instructions,
            'start_at' => optional($plan->start_at)->toIso8601String(),
            'end_at' => optional($plan->end_at)->toIso8601String(),
            'is_active' => (bool) $plan->is_active,
            'items' => $plan->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'medication_name' => $item->medication_name,
                    'dosage' => $item->dosage,
                    'route' => $item->route,
                    'interval_minutes' => $item->interval_minutes,
                    'total_doses' => $item->total_doses,
                    'first_dose_at' => optional($item->first_dose_at)->toIso8601String(),
                    'next_schedules' => $item->schedules
                        ->sortBy('scheduled_at')
                        ->take(10)
                        ->map(fn ($schedule) => [
                            'id' => $schedule->id,
                            'scheduled_at' => optional($schedule->scheduled_at)->toIso8601String(),
                            'status' => $schedule->status,
                            'taken_at' => optional($schedule->taken_at)->toIso8601String(),
                            'was_skipped' => (bool) $schedule->was_skipped,
                            'deviation_minutes' => $schedule->deviation_minutes,
                        ]),
                ];
            }),
        ];
    }

    protected function authorizeResourceOwnership(int $authUserId, int $ownerId): void
    {
        abort_if($authUserId !== $ownerId, 403, 'Você não tem permissão para acessar este recurso.');
    }
}
