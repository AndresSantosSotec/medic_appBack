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
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // 1. Citas de hoy por estado
        $appointmentsToday = Appointment::whereDate('appointment_date', $today)->count();
        $confirmedToday = Appointment::whereDate('appointment_date', $today)->where('status', 'confirmed')->count();
        $completedToday = Appointment::whereDate('appointment_date', $today)->where('status', 'completed')->count();
        $noShowToday = Appointment::whereDate('appointment_date', $today)->where('status', 'no_show')->count();
        $pendingToday = Appointment::whereDate('appointment_date', $today)->where('status', 'scheduled')->count();

        // 2. Ingresos hoy y mes
        $revenueToday = Payment::whereDate('payment_date', $today)->where('status', 'completed')->sum('amount');
        $revenueMonth = Payment::whereMonth('payment_date', Carbon::now()->month)->where('status', 'completed')->sum('amount');
        
        $revenueLastMonth = Payment::whereBetween('payment_date', [$startOfLastMonth, $endOfLastMonth])
            ->where('status', 'completed')
            ->sum('amount');

        // Calcular tendencia de ingresos
        $revenueTrend = 0;
        if ($revenueLastMonth > 0) {
            $revenueTrend = (($revenueMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        }

        // 3. Pacientes nuevos este mes
        $newPatientsMonth = Patient::whereMonth('created_at', Carbon::now()->month)->count();
        $newPatientsLastMonth = Patient::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        
        $patientsTrend = 0;
        if ($newPatientsLastMonth > 0) {
            $patientsTrend = (($newPatientsMonth - $newPatientsLastMonth) / $newPatientsLastMonth) * 100;
        }

        // 4. Próximas citas (las siguientes 5 de hoy)
        $nextAppointments = Appointment::with(['patient', 'doctor'])
            ->whereDate('appointment_date', $today)
            ->where('appointment_date', '>=', Carbon::now())
            ->orderBy('appointment_date', 'asc')
            ->take(5)
            ->get();

        // 5. Datos para el gráfico de ingresos (últimos 7 días)
        $revenueChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayRevenue = Payment::whereDate('payment_date', $date)
                ->where('status', 'completed')
                ->sum('amount');
            
            $revenueChart[] = [
                'date' => $this->getDayLabel($date),
                'revenue' => (float)$dayRevenue,
            ];
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
            ],
            'next_appointments' => $nextAppointments,
            'revenue_chart' => $revenueChart
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
