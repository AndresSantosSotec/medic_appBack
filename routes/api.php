<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
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
