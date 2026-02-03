<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['doctors', 'appointments'])
            ->orderBy('name')
            ->paginate(15);

        return response()->json($branches);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:branches',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'opens_at' => 'required|date_format:H:i',
            'closes_at' => 'required|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $branch = Branch::create($validated);

        return response()->json($branch, 201);
    }

    public function show(string $id)
    {
        $branch = Branch::withCount(['doctors', 'appointments'])
            ->findOrFail($id);

        return response()->json($branch);
    }

    public function update(Request $request, string $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:10|unique:branches,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'opens_at' => 'sometimes|required|date_format:H:i',
            'closes_at' => 'sometimes|required|date_format:H:i',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return response()->json($branch);
    }

    public function destroy(string $id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();

        return response()->json(['message' => 'Sucursal eliminada exitosamente'], 200);
    }
}
