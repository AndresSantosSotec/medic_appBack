<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index()
    {
        $reminders = Reminder::with(['appointment', 'patient'])
            ->orderBy('scheduled_at', 'desc')
            ->paginate(15);

        return response()->json($reminders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|in:sms,email,whatsapp',
            'scheduled_at' => 'required|date',
            'status' => 'nullable|in:pending,sent,failed',
            'message' => 'nullable|string',
            'sent_at' => 'nullable|date',
        ]);

        $reminder = Reminder::create($validated);
        $reminder->load(['appointment', 'patient']);

        return response()->json($reminder, 201);
    }

    public function show(string $id)
    {
        $reminder = Reminder::with(['appointment', 'patient'])
            ->findOrFail($id);

        return response()->json($reminder);
    }

    public function update(Request $request, string $id)
    {
        $reminder = Reminder::findOrFail($id);

        $validated = $request->validate([
            'appointment_id' => 'sometimes|required|exists:appointments,id',
            'patient_id' => 'sometimes|required|exists:patients,id',
            'type' => 'sometimes|required|in:sms,email,whatsapp',
            'scheduled_at' => 'sometimes|required|date',
            'status' => 'nullable|in:pending,sent,failed',
            'message' => 'nullable|string',
            'sent_at' => 'nullable|date',
        ]);

        $reminder->update($validated);
        $reminder->load(['appointment', 'patient']);

        return response()->json($reminder);
    }

    public function destroy(string $id)
    {
        $reminder = Reminder::findOrFail($id);
        $reminder->delete();

        return response()->json(['message' => 'Recordatorio eliminado exitosamente'], 200);
    }
}
