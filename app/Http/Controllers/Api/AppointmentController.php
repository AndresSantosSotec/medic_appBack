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
    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor', 'branch'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(15);

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

        $this->checkPatientConflict($validated['patient_id'], $validated['appointment_date']);

        $appointment = Appointment::create($validated);
        
        // Relacionar automáticamente paciente con doctor si no lo están
        $patient = Patient::find($validated['patient_id']);
        $patient->doctors()->syncWithoutDetaching([$validated['doctor_id']]);

        $appointment->load(['patient', 'doctor', 'branch']);

        return response()->json($appointment, 201);
    }

    public function show(string $id)
    {
        $appointment = Appointment::with(['patient', 'doctor', 'branch', 'payment', 'reminders'])
            ->findOrFail($id);

        return response()->json($appointment);
    }

    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

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

        // 2. Advertencia/Error mismo día (opcional, lo pondré como advertencia en el mensaje si se prefiere, pero el usuario pidió que avise)
        $dayConflict = Appointment::where('patient_id', $patientId)
            ->whereDate('appointment_date', $appointmentDate->toDateString())
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->when($excludeId, function($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->first();

        if ($dayConflict) {
             // Podríamos lanzar una excepción diferente o simplemente permitirlo. 
             // El usuario pidió expresamente que avisara si tiene cita el mismo día.
             $doctorName = $dayConflict->doctor->first_name . ' ' . $dayConflict->doctor->last_name;
             $time = Carbon::parse($dayConflict->appointment_date)->format('H:i');
             throw ValidationException::withMessages([
                'appointment_date' => ["Aviso: El paciente ya tiene otra cita programada para este mismo día a las {$time} con el Dr. {$doctorName}."],
            ]);
        }
    }

    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada exitosamente'], 200);
    }
}
