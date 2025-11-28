<?php

namespace App\Services;

use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentPlanSchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TreatmentPlanService
{
    public function __construct(private readonly TreatmentPlanAiService $aiService)
    {
    }

    /**
     * Create a treatment plan with schedule generation.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createPlan(User $user, array $payload): TreatmentPlan
    {
        return DB::transaction(function () use ($user, $payload): TreatmentPlan {
            $planData = Arr::only($payload, [
                'title',
                'instructions',
                'start_at',
                'end_at',
                'source',
            ]);

            $planData['status'] = 'active';
            $planData['is_active'] = true;
            $planData['metadata'] = [
                'created_via' => $payload['source'] ?? 'manual',
            ];

            /** @var TreatmentPlan $plan */
            $plan = $user->treatmentPlans()->create($planData);

            $earliestDose = null;

            foreach ($payload['items'] as $itemData) {
                $item = $this->createPlanItem($plan, $itemData);

                $firstDose = $item->first_dose_at ?? $plan->start_at ?? now();
                $earliestDose = $this->earliest($earliestDose, $firstDose);

                $this->generateSchedulesForItem($item);
            }

            if ($earliestDose) {
                $plan->update([
                    'start_at' => $plan->start_at ?? $earliestDose,
                ]);
            }

            return $plan->load(['items', 'schedules']);
        });
    }

    /**
     * Suggest a treatment plan using AI from a short summary.
     *
     * @param  array{summary:string,start_at?:?string,title?:?string}  $payload
     * @return array{title:string,instructions:string,start_at:?string,items:array<int,array<string,mixed>>,usage?:array<string,mixed>}
     */
    public function suggestPlan(User $user, array $payload): array
    {
        $draft = $this->aiService->draft($user, $payload['summary'], $payload['start_at'] ?? null, $payload['title'] ?? null);

        return [
            'title' => $draft['title'],
            'instructions' => $draft['instructions'],
            'start_at' => $payload['start_at'] ?? null,
            'items' => $draft['items'],
            'usage' => $draft['usage'] ?? [],
        ];
    }

    /**
     * Create a plan directly from a prescription OCR payload.
     *
     * @param  array{items:array<int,array<string,mixed>>,raw_text:?string,start_at:?string,title?:?string}  $payload
     */
    public function createPlanFromPrescription(User $user, array $payload): TreatmentPlan
    {
        $draft = $this->aiService->draftFromPrescription(
            $user,
            $payload['items'],
            $payload['raw_text'] ?? null,
            $payload['start_at'] ?? null
        );

        $planPayload = [
            'title' => $payload['title'] ?? $draft['title'] ?? 'Plano da receita',
            'instructions' => $draft['instructions'] ?? null,
            'start_at' => $payload['start_at'] ?? null,
            'items' => $draft['items'],
            'source' => 'ocr',
        ];

        return $this->createPlan($user, $planPayload);
    }

    /**
     * Record a dose intake and optionally reschedule future doses.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordDose(TreatmentPlanSchedule $schedule, array $payload): TreatmentPlanSchedule
    {
        return DB::transaction(function () use ($schedule, $payload): TreatmentPlanSchedule {
            $status = $payload['status'];
            $takenAtCarbon = CarbonImmutable::parse($payload['taken_at'] ?? now());

            if ($status === 'taken') {
                $schedule->taken_at = $takenAtCarbon;
                $schedule->status = 'taken';
                $schedule->was_skipped = false;
                $schedule->deviation_minutes = $schedule->scheduled_at
                    ? $schedule->scheduled_at->diffInMinutes($takenAtCarbon, false)
                    : null;
            } elseif ($status === 'skipped') {
                $schedule->status = 'skipped';
                $schedule->was_skipped = true;
                $schedule->taken_at = null;
                $schedule->deviation_minutes = null;
            } elseif ($status === 'rescheduled') {
                if (empty($payload['reschedule_to'])) {
                    throw new RuntimeException('reschedule_to é obrigatório quando status=rescheduled.');
                }

                $rescheduleTo = CarbonImmutable::parse($payload['reschedule_to']);
                $schedule->scheduled_at = $rescheduleTo;
                $schedule->status = 'scheduled';
                $schedule->taken_at = null;
                $schedule->was_skipped = false;
                $schedule->deviation_minutes = null;

                $this->shiftFutureSchedules($schedule, $rescheduleTo);
            }

            $schedule->notes = $payload['notes'] ?? $schedule->notes;
            $schedule->save();

            if ($status === 'taken') {
                $this->shiftFutureSchedules($schedule, $takenAtCarbon);
            }

            return $schedule->fresh();
        });
    }

    protected function createPlanItem(TreatmentPlan $plan, array $data): TreatmentPlanItem
    {
        $itemData = Arr::only($data, [
            'medication_id',
            'medication_name',
            'dosage',
            'route',
            'instructions',
            'interval_minutes',
            'total_doses',
            'duration_days',
            'first_dose_at',
        ]);

        $itemData['metadata'] = [
            'specific_times' => Arr::get($data, 'specific_times'),
        ];

        /** @var TreatmentPlanItem $item */
        $item = $plan->items()->create($itemData);

        return $item;
    }

    protected function generateSchedulesForItem(TreatmentPlanItem $item): void
    {
        $specificTimes = Arr::get($item->metadata, 'specific_times', []);

        if (! empty($specificTimes)) {
            $this->generateSchedulesUsingSpecificTimes($item, $specificTimes);

            return;
        }

        $interval = $item->interval_minutes;
        if (! $interval) {
            return;
        }

        $firstDose = $item->first_dose_at ?? now();
        $totalDoses = $item->total_doses ?? $this->calculateTotalDoses($item);

        if (! $totalDoses) {
            return;
        }

        $scheduleEntries = [];
        $current = CarbonImmutable::parse($firstDose);

        for ($i = 0; $i < $totalDoses; $i++) {
            $scheduleEntries[] = [
                'scheduled_at' => $current,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $current = $current->addMinutes($interval);
        }

        $this->bulkInsertSchedules($item, $scheduleEntries);
    }

    protected function generateSchedulesUsingSpecificTimes(TreatmentPlanItem $item, array $timesOfDay): void
    {
        $durationDays = $item->duration_days ?? 1;
        $firstDay = CarbonImmutable::parse($item->first_dose_at ?? now())->startOfDay();

        $scheduleEntries = [];

        for ($day = 0; $day < $durationDays; $day++) {
            $base = $firstDay->addDays($day);

            foreach ($timesOfDay as $time) {
                [$hour, $minute] = array_map('intval', explode(':', $time));
                $scheduled = $base->setTime($hour, $minute);

                $scheduleEntries[] = [
                    'scheduled_at' => $scheduled,
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        $this->bulkInsertSchedules($item, $scheduleEntries);
    }

    protected function calculateTotalDoses(TreatmentPlanItem $item): ?int
    {
        if ($item->total_doses) {
            return $item->total_doses;
        }

        if ($item->duration_days && $item->interval_minutes) {
            $dosesPerDay = (int) floor(1440 / $item->interval_minutes);

            return max(1, $dosesPerDay * $item->duration_days);
        }

        return null;
    }

    protected function bulkInsertSchedules(TreatmentPlanItem $item, array $entries): void
    {
        if (empty($entries)) {
            return;
        }

        try {
            $item->schedules()->insert(
                array_map(function (array $entry) use ($item): array {
                    return array_merge($entry, [
                        'treatment_plan_item_id' => $item->id,
                    ]);
                }, $entries)
            );
        } catch (Throwable $exception) {
            Log::warning('Falha ao gerar agenda de tratamento.', [
                'item_id' => $item->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function shiftFutureSchedules(TreatmentPlanSchedule $schedule, CarbonImmutable $reference): void
    {
        $item = $schedule->item;
        $interval = $item->interval_minutes;

        if (! $interval) {
            return;
        }

        $futureSchedules = $item->schedules()
            ->where('id', '!=', $schedule->id)
            ->where('scheduled_at', '>', $schedule->scheduled_at)
            ->orderBy('scheduled_at')
            ->get();

        $current = $reference;

        foreach ($futureSchedules as $future) {
            $current = $current->addMinutes($interval);
            $future->update([
                'scheduled_at' => $current,
                'status' => 'scheduled',
                'was_skipped' => false,
                'deviation_minutes' => null,
            ]);
        }
    }

    protected function earliest(?CarbonImmutable $current, $candidate): ?CarbonImmutable
    {
        $candidate = $candidate ? CarbonImmutable::parse($candidate) : null;

        if (! $current) {
            return $candidate;
        }

        if ($candidate && $candidate->lessThan($current)) {
            return $candidate;
        }

        return $current;
    }
}
