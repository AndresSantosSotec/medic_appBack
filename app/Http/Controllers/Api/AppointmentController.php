<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'doctor', 'branch'])
            ->orderBy('appointment_date', 'desc');

        // Filtrar por doctor si el usuario es médico
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $query->where('doctor_id', $user->doctor->id);
        }

        if ($request->has('unpaid_only') && $request->unpaid_only) {
            $query->doesntHave('payment');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('doctor_id') && $user->hasRole('admin')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $perPage = $request->input('per_page', 15);
        $appointments = $query->paginate($perPage);

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'appointment_date' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:240',
            'status' => 'nullable|in:scheduled,confirmed,in_progress,completed,cancelled',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Si es doctor, forzar que la cita sea de él mismo
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $validated['doctor_id'] = $user->doctor->id;
        }

        $this->checkPatientConflict($validated['patient_id'], $validated['appointment_date']);

        $appointment = Appointment::create($validated);
        
        // Relacionar automáticamente paciente con doctor si no lo están
        $patient = Patient::find($validated['patient_id']);
        $patient->doctors()->syncWithoutDetaching([$validated['doctor_id']]);

        $appointment->load(['patient', 'doctor', 'branch']);

        return response()->json($appointment, 201);
    }

    public function show(Request $request, string $id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'branch', 'payment', 'reminders'])
            ->findOrFail($id);

        // Verificar que el doctor solo vea sus citas
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No tienes permiso para ver esta cita.');
            }
        }

        return response()->json($appointment);
    }

    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Verificar propiedad
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No tienes permiso para editar esta cita.');
            }
        }

        $validated = $request->validate([
            'patient_id' => 'sometimes|required|exists:patients,id',
            'doctor_id' => 'sometimes|required|exists:doctors,id',
            'branch_id' => 'nullable|exists:branches,id',
            'appointment_date' => 'sometimes|required|date',
            'duration' => 'nullable|integer|min:15|max:240',
            'status' => 'nullable|in:scheduled,confirmed,in_progress,completed,cancelled',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['appointment_date']) || isset($validated['patient_id'])) {
            $patientId = $validated['patient_id'] ?? $appointment->patient_id;
            $date = $validated['appointment_date'] ?? $appointment->appointment_date;
            $this->checkPatientConflict($patientId, $date, $id);
        }

        $appointment->update($validated);

        if (isset($validated['patient_id']) || isset($validated['doctor_id'])) {
            $patientId = $validated['patient_id'] ?? $appointment->patient_id;
            $doctorId = $validated['doctor_id'] ?? $appointment->doctor_id;
            $patient = Patient::find($patientId);
            $patient->doctors()->syncWithoutDetaching([$doctorId]);
        }

        $appointment->load(['patient', 'doctor', 'branch']);

        return response()->json($appointment);
    }

    protected function checkPatientConflict($patientId, $date, $excludeId = null)
    {
        $appointmentDate = Carbon::parse($date);
        
        // 1. Conflicto exacto (misma hora)
        $exactConflict = Appointment::where('patient_id', $patientId)
            ->where('appointment_date', $appointmentDate)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->when($excludeId, function($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->first();

        if ($exactConflict) {
            $doctorName = $exactConflict->doctor->first_name . ' ' . $exactConflict->doctor->last_name;
            throw ValidationException::withMessages([
                'appointment_date' => ["El paciente ya tiene una cita a esta misma hora con el Dr. {$doctorName}."],
            ]);
        }

        // 2. Advertencia mismo día
        $dayConflict = Appointment::where('patient_id', $patientId)
            ->whereDate('appointment_date', $appointmentDate->toDateString())
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->when($excludeId, function($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->first();

        if ($dayConflict) {
             $doctorName = $dayConflict->doctor->first_name . ' ' . $dayConflict->doctor->last_name;
             $time = Carbon::parse($dayConflict->appointment_date)->format('H:i');
             throw ValidationException::withMessages([
                'appointment_date' => ["Aviso: El paciente ya tiene otra cita programada para este mismo día a las {$time} con el Dr. {$doctorName}."],
            ]);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        // Verificar propiedad
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            if ($appointment->doctor_id !== $user->doctor->id) {
                abort(403, 'No tienes permiso para eliminar esta cita.');
            }
        }

        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada exitosamente'], 200);
    }
}
