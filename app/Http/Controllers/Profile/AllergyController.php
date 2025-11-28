<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreAllergyRequest;
use App\Http\Requests\Profile\UpdateAllergyRequest;
use App\Models\UserAllergy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AllergyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $allergies = $request->user()
            ->allergies()
            ->orderBy('allergen')
            ->get();

        return response()->json([
            'data' => $allergies,
        ]);
    }

    public function store(StoreAllergyRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validated();

        $allergy = $user->allergies()->create([
            'allergen' => $data['allergen'],
            'allergen_slug' => Str::slug($data['allergen']),
            'reaction' => $data['reaction'] ?? null,
            'severity' => $data['severity'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => [],
        ]);

        return response()->json([
            'message' => 'Alergia cadastrada com sucesso.',
            'data' => $allergy,
        ], 201);
    }

    public function update(UpdateAllergyRequest $request, UserAllergy $allergy): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $allergy->user_id);

        $payload = $request->validated();

        if (isset($payload['allergen'])) {
            $payload['allergen_slug'] = Str::slug($payload['allergen']);
        }

        $allergy->fill($payload)->save();

        return response()->json([
            'message' => 'Alergia atualizada.',
            'data' => $allergy->fresh(),
        ]);
    }

    public function destroy(Request $request, UserAllergy $allergy): JsonResponse
    {
        $this->authorizeResourceOwnership($request->user()->id, $allergy->user_id);

        $allergy->delete();

        return response()->noContent();
    }

    protected function authorizeResourceOwnership(int $authUserId, int $ownerId): void
    {
        abort_if($authUserId !== $ownerId, 403, 'Você não tem permissão para modificar este recurso.');
    }
}
