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

        // Filtrar por doctor logueado
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $doctor = $user->doctor;
            $query->whereHas('doctors', function($q) use ($doctor) {
                $q->where('doctors.id', $doctor->id);
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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

        // Relacionar automáticamente el paciente con el doctor logueado
        $user = $request->user();
        if ($user->doctor) {
            $patient->doctors()->attach($user->doctor->id);
        }

        return response()->json($patient, 201);
    }

    public function show(Request $request, string $id)
    {
        $patient = Patient::with(['appointments.doctor', 'payments', 'doctors'])
            ->findOrFail($id);

        // Verificar que el doctor solo vea sus pacientes
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $isMyPatient = $patient->doctors->contains('id', $user->doctor->id);
            if (!$isMyPatient) {
                abort(403, 'No tienes permiso para ver este paciente.');
            }
        }

        return response()->json($patient);
    }

    public function update(Request $request, string $id)
    {
        $patient = Patient::findOrFail($id);

        // Verificar propiedad
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $isMyPatient = $patient->doctors()->where('doctors.id', $user->doctor->id)->exists();
            if (!$isMyPatient) {
                abort(403, 'No tienes permiso para editar este paciente.');
            }
        }

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

    public function destroy(Request $request, string $id)
    {
        // Solo admin puede eliminar pacientes
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Solo los administradores pueden eliminar pacientes.');
        }

        $patient = Patient::findOrFail($id);
        $patient->delete();

        return response()->json(['message' => 'Paciente eliminado exitosamente'], 200);
    }
}
