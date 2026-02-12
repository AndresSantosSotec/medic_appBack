<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $query = Payment::with(['appointment.doctor', 'patient'])
            ->orderBy('payment_date', 'desc');

        // Filtrar pagos por doctor logueado
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            $query->whereHas('appointment', function($q) use ($user) {
                $q->where('doctor_id', $user->doctor->id);
            });
        }

        $payments = $query->paginate($perPage);

        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => 'required|exists:patients,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,credit_card,debit_card,transfer,insurance',
            'status' => 'nullable|in:pending,completed,cancelled,refunded',
            'payment_date' => 'required|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create($validated);
        $payment->load(['appointment', 'patient']);

        return response()->json($payment, 201);
    }

    public function show(string $id)
    {
        $payment = Payment::with(['appointment.doctor', 'patient'])
            ->findOrFail($id);

        return response()->json($payment);
    }

    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $validated = $request->validate([
            'appointment_id' => 'sometimes|required|exists:appointments,id',
            'patient_id' => 'sometimes|required|exists:patients,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|in:cash,credit_card,debit_card,transfer,insurance',
            'status' => 'nullable|in:pending,completed,cancelled,refunded',
            'payment_date' => 'sometimes|required|date',
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);
        $payment->load(['appointment', 'patient']);

        return response()->json($payment);
    }

    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Pago eliminado exitosamente'], 200);
    }
}
