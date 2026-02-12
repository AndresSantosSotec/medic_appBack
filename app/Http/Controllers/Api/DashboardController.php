<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $user = $request->user();
        $isDoctor = !$user->hasRole('admin') && $user->doctor;
        $doctorId = $isDoctor ? $user->doctor->id : null;

        // 1. Citas de hoy por estado
        $appointmentsQuery = Appointment::whereDate('appointment_date', $today);
        if ($doctorId) $appointmentsQuery->where('doctor_id', $doctorId);
        $appointmentsToday = (clone $appointmentsQuery)->count();
        $confirmedToday = (clone $appointmentsQuery)->where('status', 'confirmed')->count();
        $completedToday = (clone $appointmentsQuery)->where('status', 'completed')->count();
        $noShowToday = (clone $appointmentsQuery)->where('status', 'no_show')->count();
        $pendingToday = (clone $appointmentsQuery)->where('status', 'scheduled')->count();

        // 2. Ingresos hoy y mes
        $paymentsQuery = Payment::query();
        if ($doctorId) {
            $paymentsQuery->whereHas('appointment', fn($q) => $q->where('doctor_id', $doctorId));
        }

        $revenueToday = (clone $paymentsQuery)->whereDate('payment_date', $today)->where('status', 'completed')->sum('amount');
        $revenueMonth = (clone $paymentsQuery)->whereMonth('payment_date', Carbon::now()->month)->where('status', 'completed')->sum('amount');
        
        $revenueLastMonth = (clone $paymentsQuery)->whereBetween('payment_date', [$startOfLastMonth, $endOfLastMonth])
            ->where('status', 'completed')
            ->sum('amount');

        // Calcular tendencia de ingresos
        $revenueTrend = 0;
        if ($revenueLastMonth > 0) {
            $revenueTrend = (($revenueMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        }

        // 3. Pacientes nuevos este mes
        $patientsQuery = Patient::query();
        if ($doctorId) {
            $patientsQuery->whereHas('doctors', fn($q) => $q->where('doctors.id', $doctorId));
        }

        $newPatientsMonth = (clone $patientsQuery)->whereMonth('created_at', Carbon::now()->month)->count();
        $newPatientsLastMonth = (clone $patientsQuery)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        
        $patientsTrend = 0;
        if ($newPatientsLastMonth > 0) {
            $patientsTrend = (($newPatientsMonth - $newPatientsLastMonth) / $newPatientsLastMonth) * 100;
        }

        // 4. Próximas citas (las siguientes 5 de hoy)
        $nextQuery = Appointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', $today)
            ->where('appointment_date', '>=', Carbon::now())
            ->orderBy('appointment_date', 'asc');
        if ($doctorId) $nextQuery->where('doctor_id', $doctorId);
        $nextAppointments = $nextQuery->take(5)->get();

        // 5. Datos para el gráfico de ingresos (últimos 7 días)
        $revenueChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayQuery = Payment::whereDate('payment_date', $date)->where('status', 'completed');
            if ($doctorId) {
                $dayQuery->whereHas('appointment', fn($q) => $q->where('doctor_id', $doctorId));
            }
            $dayRevenue = $dayQuery->sum('amount');
            
            $revenueChart[] = [
                'date' => $this->getDayLabel($date),
                'revenue' => (float)$dayRevenue,
            ];
        }

        // 6. Total de pacientes del doctor (o todos)
        $totalPatients = Patient::query();
        if ($doctorId) {
            $totalPatients->whereHas('doctors', fn($q) => $q->where('doctors.id', $doctorId));
        }

        return response()->json([
            'stats' => [
                'appointments_today' => $appointmentsToday,
                'confirmed_today' => $confirmedToday,
                'completed_today' => $completedToday,
                'no_show_today' => $noShowToday,
                'pending_today' => $pendingToday,
                'revenue_today' => $revenueToday,
                'revenue_month' => $revenueMonth,
                'revenue_trend' => round($revenueTrend, 1),
                'new_patients_month' => $newPatientsMonth,
                'patients_trend' => round($patientsTrend, 1),
                'total_patients' => $totalPatients->count(),
            ],
            'next_appointments' => $nextAppointments,
            'revenue_chart' => $revenueChart,
            'is_doctor_view' => $isDoctor,
        ]);
    }

    private function getDayLabel(Carbon $date)
    {
        $dayMap = [
            'Monday' => 'Lun',
            'Tuesday' => 'Mar',
            'Wednesday' => 'Mié',
            'Thursday' => 'Jue',
            'Friday' => 'Vie',
            'Saturday' => 'Sáb',
            'Sunday' => 'Dom',
        ];
        return $dayMap[$date->format('l')];
    }
}
