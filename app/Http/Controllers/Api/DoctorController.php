<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with(['branch', 'appointments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'specialty' => 'required|string|max:255',
            'license_number' => 'required|string|unique:doctors,license_number',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|email|unique:doctors,email',
            'branch_id' => 'nullable|exists:branches,id',
            'is_active' => 'boolean',
        ]);

        $doctor = Doctor::create($validated);

        return response()->json($doctor, 201);
    }

    public function show(string $id)
    {
        $doctor = Doctor::with(['branch', 'appointments.patient'])
            ->findOrFail($id);

        return response()->json($doctor);
    }

    public function update(Request $request, string $id)
    {
        $doctor = Doctor::findOrFail($id);

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

        return response()->json($doctor);
    }

    public function destroy(string $id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json(['message' => 'Doctor eliminado exitosamente'], 200);
    }
}
