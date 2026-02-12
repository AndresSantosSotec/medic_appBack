<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with(['appointments', 'payments']);

        $user = $request->user();
        if (!$user->hasRole('admin') && $user->doctor) {
            $doctor = $user->doctor;
            $query->whereHas('doctors', function($q) use ($doctor) {
                $q->where('doctors.id', $doctor->id);
            });
        }

        $patients = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|max:20',
            'phone' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $patient = Patient::create($validated);

        // Si el usuario es un doctor, relacionar automáticamente el paciente
        $user = $request->user();
        if ($user->doctor) {
            $patient->doctors()->attach($user->doctor->id);
        }

        return response()->json($patient, 201);
    }

    public function show(string $id)
    {
        $patient = Patient::with(['appointments.doctor', 'payments'])
            ->findOrFail($id);

        return response()->json($patient);
    }

    public function update(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'date_of_birth' => 'sometimes|required|date',
            'gender' => 'sometimes|required|string|max:20',
            'phone' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $patient->update($validated);

        return response()->json($patient);
    }

    public function destroy(string $id)
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(['message' => 'Paciente eliminado exitosamente'], 200);
    }
}
