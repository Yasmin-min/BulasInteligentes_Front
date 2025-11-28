<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreMedicationCourseRequest;
use App\Http\Requests\Profile\UpdateMedicationCourseRequest;
use App\Models\Medication;
use App\Models\UserMedicationCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MedicationCourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $courses = $request->user()
            ->medicationCourses()
            ->orderByDesc('is_active')
            ->orderByDesc('start_at')
            ->get();

        return response()->json([
            'data' => $courses,
        ]);
    }

    public function store(StoreMedicationCourseRequest $request): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validated();
        if (empty($payload['medication_name']) && ! empty($payload['medication_id'])) {
            try {
                $payload['medication_name'] = optional(Medication::find($payload['medication_id']))?->name;
            } catch (Throwable $exception) {
                Log::warning('Falha ao buscar medicamento associado ao tratamento.', [
                    'medication_id' => $payload['medication_id'],
                    'exception' => $exception->getMessage(),
                ]);
            }

            $payload['medication_name'] = $payload['medication_name'] ?? 'Medicamento não identificado';
        }

        $course = $user->medicationCourses()->create($payload);

        return response()->json([
            'message' => 'Tratamento atual registrado.',
            'data' => $course,
        ], 201);
    }

    public function update(UpdateMedicationCourseRequest $request, UserMedicationCourse $course): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $course->user_id);

        $payload = $request->validated();

        if (isset($payload['medication_name'])) {
            $payload['medication_name'] = trim((string) $payload['medication_name']);
        }

        $course->fill($payload)->save();

        return response()->json([
            'message' => 'Tratamento atualizado.',
            'data' => $course->fresh(),
        ]);
    }

    public function destroy(Request $request, UserMedicationCourse $course): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $course->user_id);

        $course->delete();

        return response()->noContent();
    }

    protected function authorizeResourceOwnership(int $authUserId, int $ownerId): void
    {
        abort_if($authUserId !== $ownerId, 403, 'Você não tem permissão para modificar este recurso.');
    }
}
