<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PrescriptionUploadController;
use App\Http\Controllers\Profile\AllergyController;
use App\Http\Controllers\Profile\MedicationCourseController;
use App\Http\Controllers\TreatmentPlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Bulas Inteligentes API',
        'version' => 'v1',
    ]);
});

Route::get('/health', HealthCheckController::class)->name('health');

Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::post('/medications/query', [MedicationController::class, 'query']);
Route::get('/medications/{slug}', [MedicationController::class, 'show']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
    Route::patch('/auth/me', [AuthController::class, 'update'])->name('auth.update');

    Route::get('/profile/allergies', [AllergyController::class, 'index']);
    Route::post('/profile/allergies', [AllergyController::class, 'store']);
    Route::put('/profile/allergies/{allergy}', [AllergyController::class, 'update']);
    Route::delete('/profile/allergies/{allergy}', [AllergyController::class, 'destroy']);

    Route::get('/profile/medications', [MedicationCourseController::class, 'index']);
    Route::post('/profile/medications', [MedicationCourseController::class, 'store']);
    Route::put('/profile/medications/{course}', [MedicationCourseController::class, 'update']);
    Route::delete('/profile/medications/{course}', [MedicationCourseController::class, 'destroy']);

    Route::get('/treatment-plans', [TreatmentPlanController::class, 'index']);
    Route::post('/treatment-plans', [TreatmentPlanController::class, 'store']);
    Route::post('/treatment-plans/ai/suggest', [TreatmentPlanController::class, 'suggest']);
    Route::get('/treatment-plans/{treatmentPlan}', [TreatmentPlanController::class, 'show']);
    Route::patch('/treatment-plans/{treatmentPlan}', [TreatmentPlanController::class, 'update']);
    Route::delete('/treatment-plans/{treatmentPlan}', [TreatmentPlanController::class, 'destroy']);
    Route::patch('/treatment-plans/{treatmentPlan}/schedules/{schedule}', [TreatmentPlanController::class, 'recordDose']);

    Route::get('/prescriptions/uploads', [PrescriptionUploadController::class, 'index']);
    Route::post('/prescriptions/uploads', [PrescriptionUploadController::class, 'store']);
    Route::get('/prescriptions/uploads/{prescriptionUpload}', [PrescriptionUploadController::class, 'show']);
    Route::delete('/prescriptions/uploads/{prescriptionUpload}', [PrescriptionUploadController::class, 'destroy']);
    Route::post('/prescriptions/uploads/{prescriptionUpload}/plan', [PrescriptionUploadController::class, 'createPlan']);

    Route::post('/assistant/query', [AssistantController::class, 'query']);
});
