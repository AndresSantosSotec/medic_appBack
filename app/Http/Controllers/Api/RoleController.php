<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with(['permissions'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'nullable|string|max:255|unique:roles',
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permission_ids'])) {
            $role->permissions()->attach($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json($role, 201);
    }

    public function show(string $id)
    {
        $role = Role::with(['permissions', 'users'])
            ->findOrFail($id);

        return response()->json($role);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $id,
            'slug' => 'sometimes|nullable|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role->update(array_filter($validated, fn($key) => $key !== 'permission_ids', ARRAY_FILTER_USE_KEY));

        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json($role);
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Rol eliminado exitosamente'], 200);
    }

    public function assignPermission(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $role->permissions()->syncWithoutDetaching($validated['permission_id']);
        $role->load('permissions');

        return response()->json($role);
    }

    public function removePermission(Request $request, string $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
        ]);

        $role->permissions()->detach($validated['permission_id']);
        $role->load('permissions');

        return response()->json($role);
    }
}
