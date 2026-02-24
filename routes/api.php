<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DocumentoMedicoController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

// Rutas públicas de autenticación

Route::get('/test', function () {
    return response()->json(['message' => 'API is working']);
});

Route::get('/BD-Check', function () {
    try {
        DB::connection()->getPdo();
        return response()->json(['message' => 'Database connection successful']);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Database connection failed', 'error' => $e->getMessage()], 500);
    }
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['roles.permissions', 'doctor']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users - Gestión de usuarios
    Route::apiResource('users', UserController::class);
    Route::post('/users/{id}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/users/{id}/remove-role', [UserController::class, 'removeRole']);

    // Roles - Gestión de roles
    Route::apiResource('roles', RoleController::class);
    Route::post('/roles/{id}/assign-permission', [RoleController::class, 'assignPermission']);
    Route::post('/roles/{id}/remove-permission', [RoleController::class, 'removePermission']);

    // Permissions - Gestión de permisos
    Route::apiResource('permissions', PermissionController::class);

    // Branches
    Route::apiResource('branches', BranchController::class);

    // Doctors
    Route::apiResource('doctors', DoctorController::class);

    // Patients
    Route::apiResource('patients', PatientController::class);

    // Appointments
    Route::apiResource('appointments', AppointmentController::class);

    // Payments
    Route::apiResource('payments', PaymentController::class);

    // Reminders
    Route::apiResource('reminders', ReminderController::class);

    // Consultations
    Route::apiResource('consultations', \App\Http\Controllers\Api\ConsultationController::class);

    // Documentos Médicos - Gestión documental de pacientes
    Route::prefix('pacientes/{pacienteId}/documentos')->group(function () {
        Route::get('/', [DocumentoMedicoController::class, 'index']);
        Route::post('/', [DocumentoMedicoController::class, 'store'])->middleware('throttle:20,1'); // Máximo 20 subidas por minuto
        Route::get('/exportar', [DocumentoMedicoController::class, 'exportar']);
        Route::get('/{documentoId}', [DocumentoMedicoController::class, 'show']);
        Route::get('/{documentoId}/url', [DocumentoMedicoController::class, 'obtenerUrl']);
        Route::get('/{documentoId}/preview', [DocumentoMedicoController::class, 'preview']);
        Route::get('/{documentoId}/descargar', [DocumentoMedicoController::class, 'descargar']);
        Route::delete('/{documentoId}', [DocumentoMedicoController::class, 'destroy']);
    });

    // Subida por chunks para archivos grandes
    Route::post('documentos/upload-chunk', [DocumentoMedicoController::class, 'uploadChunk'])->middleware('throttle:60,1');

    // Reports
    Route::get('reports/dashboard', [\App\Http\Controllers\Api\ReportController::class, 'dashboard']);
    Route::get('reports/appointments', [\App\Http\Controllers\Api\ReportController::class, 'appointments']);
    Route::get('reports/doctors', [\App\Http\Controllers\Api\ReportController::class, 'doctors']);
    Route::get('reports/export/excel', [\App\Http\Controllers\Api\ReportController::class, 'exportExcel']);
    Route::get('reports/export/pdf', [\App\Http\Controllers\Api\ReportController::class, 'exportPdf']);

    // Preferences
    Route::get('user/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'show']);
    Route::put('user/preferences', [\App\Http\Controllers\Api\UserPreferenceController::class, 'update']);
});
