<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['role_ids'])) {
            $user->roles()->attach($validated['role_ids']);
        }

        $user->load(['roles.permissions']);

        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::with(['roles.permissions'])
            ->findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update(array_filter($validated, fn($key) => $key !== 'role_ids', ARRAY_FILTER_USE_KEY));

        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        $user->load(['roles.permissions']);

        return response()->json($user);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // No permitir eliminar el propio usuario
        $currentUser = auth()->user();
        if ($currentUser && $user->id === $currentUser->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario'
            ], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado exitosamente'], 200);
    }

    // Métodos adicionales para gestión de roles
    public function assignRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->syncWithoutDetaching($validated['role_id']);
        $user->load(['roles.permissions']);

        return response()->json($user);
    }

    public function removeRole(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->roles()->detach($validated['role_id']);
        $user->load(['roles.permissions']);

        return response()->json($user);
    }
}
