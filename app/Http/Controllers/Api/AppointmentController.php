<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

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

        $appointment = Appointment::create($validated);
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

        $appointment->update($validated);
        $appointment->load(['patient', 'doctor', 'branch']);

        return response()->json($appointment);
    }

    public function destroy(string $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada exitosamente'], 200);
    }
}
