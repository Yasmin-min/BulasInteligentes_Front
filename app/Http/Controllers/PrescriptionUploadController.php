<?php

namespace App\Http\Controllers;

use App\Http\Requests\Prescription\CreatePrescriptionPlanRequest;
use App\Http\Requests\Prescription\UploadPrescriptionRequest;
use App\Jobs\ProcessPrescriptionUpload;
use App\Models\PrescriptionUpload;
use App\Services\TreatmentPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PrescriptionUploadController extends Controller
{
    public function __construct(private readonly TreatmentPlanService $planService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $uploads = $request->user()
            ->prescriptionUploads()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $uploads,
        ]);
    }

    public function store(UploadPrescriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $filePath = $this->storeFile($user->id, $request);

        $upload = $user->prescriptionUploads()->create([
            'original_name' => $request->file('file')?->getClientOriginalName(),
            'file_path' => $filePath,
            'status' => 'pending',
            'failure_reason' => null,
        ]);

        ProcessPrescriptionUpload::dispatch($upload->id);

        return response()->json([
            'message' => 'Receita recebida. Iniciaremos a interpretação em breve.',
            'data' => $upload,
        ], 201);
    }

    public function show(Request $request, PrescriptionUpload $prescriptionUpload): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $prescriptionUpload->user_id);

        return response()->json([
            'data' => $prescriptionUpload,
        ]);
    }

    public function destroy(Request $request, PrescriptionUpload $prescriptionUpload): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $prescriptionUpload->user_id);

        $this->removeStoredFile($prescriptionUpload);
        $prescriptionUpload->delete();

        return response()->json([
            'message' => 'Receita removida.',
        ]);
    }

    public function createPlan(CreatePrescriptionPlanRequest $request, PrescriptionUpload $prescriptionUpload): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $prescriptionUpload->user_id);

        if (! in_array($prescriptionUpload->status, ['parsed', 'text_extracted'], true)) {
            abort(422, 'A receita ainda não foi processada.');
        }

        $items = $prescriptionUpload->parsed_payload ?? [];

        if (empty($items) && empty($prescriptionUpload->extracted_text)) {
            abort(422, 'Não há dados suficientes para gerar o plano automaticamente.');
        }

        if (empty($items) && $prescriptionUpload->extracted_text) {
            $items = [
                [
                    'medication_name' => 'Receita sem estrutura',
                    'instructions' => $prescriptionUpload->extracted_text,
                ],
            ];
        }

        $defaultTitle = 'Plano da receita';
        if ($prescriptionUpload->created_at) {
            $defaultTitle .= ' '.$prescriptionUpload->created_at->format('d/m');
        }

        $plan = $this->planService->createPlanFromPrescription($request->user(), [
            'items' => $items,
            'raw_text' => $prescriptionUpload->extracted_text,
            'start_at' => $request->input('start_at'),
            'title' => $request->input('title') ?? $defaultTitle,
        ]);

        // remove arquivo e registro após gerar o plano para liberar espaço
        $this->removeStoredFile($prescriptionUpload);
        $prescriptionUpload->delete();

        return response()->json([
            'message' => 'Plano criado a partir da receita.',
            'data' => $plan,
        ], 201);
    }

    protected function storeFile(int $userId, UploadPrescriptionRequest $request): string
    {
        $disk = Storage::disk('local');
        $directory = "prescriptions/{$userId}";

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            return $file->store($directory, 'local');
        }

        $filename = $directory.'/'.Str::uuid().'.png';
        $encoded = preg_replace('/^data:.*;base64,/', '', (string) $request->input('image_base64'));
        $binary = base64_decode($encoded, true);

        if ($binary === false) {
            throw new RuntimeException('Imagem em base64 inválida.');
        }

        $disk->put($filename, $binary);

        return $filename;
    }

    protected function authorizeResourceOwnership(int $authUserId, int $ownerId): void
    {
        abort_if($authUserId !== $ownerId, 403, 'Você não tem permissão para acessar este recurso.');
    }

    protected function removeStoredFile(PrescriptionUpload $upload): void
    {
        $disks = array_filter([
            'local',
            config('filesystems.default'),
            'private',
        ]);

        foreach (array_unique($disks) as $disk) {
            $storage = Storage::disk($disk);
            foreach ($this->candidatePaths($upload->file_path) as $candidate) {
                if ($storage->exists($candidate)) {
                    $storage->delete($candidate);
                }
            }
        }

        foreach ($this->candidatePaths($upload->file_path) as $candidate) {
            $absolute = storage_path('app/'.$candidate);
            if (file_exists($absolute)) {
                @unlink($absolute);
            }
        }
    }

    protected function candidatePaths(string $path): array
    {
        if (str_starts_with($path, 'private/')) {
            return [$path, preg_replace('/^private\\//', '', $path)];
        }

        return [$path, 'private/'.$path];
    }
}
