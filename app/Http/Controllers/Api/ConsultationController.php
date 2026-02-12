<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Appointment;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $query = Consultation::with(['appointment', 'patient', 'doctor'])
            ->orderBy('created_at', 'desc');

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $consultations = $query->paginate($request->input('per_page', 15));

        return response()->json($consultations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id|unique:consultations,appointment_id',
            'reason' => 'nullable|string',
            'diagnosis' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $appointment = Appointment::findOrFail($validated['appointment_id']);
        
        // Auto-fill related fields
        $validated['doctor_id'] = $appointment->doctor_id;
        $validated['patient_id'] = $appointment->patient_id;

        $consultation = Consultation::create($validated);
        
        // Update appointment status to completed if not already
        if ($appointment->status !== 'completed') {
            $appointment->update(['status' => 'completed']);
        }

        return response()->json($consultation, 201);
    }

    public function show($id)
    {
        $consultation = Consultation::with(['appointment', 'patient', 'doctor'])->findOrFail($id);
        return response()->json($consultation);
    }

    public function update(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string',
            'diagnosis' => 'sometimes|required|string',
            'notes' => 'nullable|string',
        ]);

        $consultation->update($validated);

        return response()->json($consultation);
    }

    public function destroy($id)
    {
        $consultation = Consultation::findOrFail($id);
        $consultation->delete();
        return response()->json(['message' => 'Consulta eliminada']);
    }
}
