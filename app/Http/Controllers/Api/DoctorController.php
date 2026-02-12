<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::with(['branch', 'appointments', 'user'])
            ->orderBy('created_at', 'desc');

        // Si es doctor, solo se ve a sí mismo
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $query->where('id', $user->doctor->id);
        }

        $doctors = $query->paginate(15);

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        // Solo admin puede crear doctores
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Solo los administradores pueden crear doctores.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialty' => 'required|string|max:255',
            'license_number' => 'required|string|unique:doctors,license_number',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|email|unique:doctors,email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        // Crear usuario asociado
        $newUser = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make('password123'),
        ]);
        
        $doctorRole = Role::where('slug', 'doctor')->first();
        if ($doctorRole) {
            $newUser->roles()->attach($doctorRole);
        }

        $validated['user_id'] = $newUser->id;
        $doctor = Doctor::create($validated);

        return response()->json($doctor->load('user'), 201);
    }

    public function show(Request $request, string $id)
    {
        $doctor = Doctor::with(['branch', 'appointments.patient', 'user', 'patients'])
            ->findOrFail($id);

        // Si es doctor, solo puede verse a sí mismo
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            if ($doctor->id !== $user->doctor->id) {
                abort(403, 'No tienes permiso para ver este doctor.');
            }
        }

        return response()->json($doctor);
    }

    public function update(Request $request, string $id)
    {
        $doctor = Doctor::findOrFail($id);

        // Solo admin puede editar otros doctores; doctor puede editar su propio perfil
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            if (!$user->doctor || $user->doctor->id !== $doctor->id) {
                abort(403, 'No tienes permiso para editar este doctor.');
            }
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'specialty' => 'sometimes|required|string|max:255',
            'license_number' => 'sometimes|required|string|unique:doctors,license_number,' . $id,
            'phone' => 'nullable|string|max:255',
            'email' => 'sometimes|required|email|unique:doctors,email,' . $id,
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        $doctor->update($validated);

        // Sync user name if linked
        if ($doctor->user_id && $doctor->user) {
            $updateData = ['name' => ($validated['first_name'] ?? $doctor->first_name) . ' ' . ($validated['last_name'] ?? $doctor->last_name)];
            $doctor->user->update($updateData);
        }

        return response()->json($doctor->load('user'));
    }

    public function destroy(Request $request, string $id)
    {
        // Solo admin puede eliminar doctores
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Solo los administradores pueden eliminar doctores.');
        }

        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Doctor eliminado exitosamente'], 200);
    }
}
