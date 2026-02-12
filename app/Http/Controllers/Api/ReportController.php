<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Obtener el doctor_id efectivo según el usuario logueado.
     * Si es doctor, fuerza su propio ID. Si es admin, permite filtrar por doctor_id.
     */
    private function getEffectiveDoctorId(Request $request): ?int
    {
        $user = $request->user();
        
        // Si es doctor, SIEMPRE forzar su propio ID (no puede ver datos de otros)
        if (!$user->hasRole('admin') && !$user->hasRole('receptionist') && $user->doctor) {
            return $user->doctor->id;
        }

        // Si es admin/recepcionista, permite filtrar por doctor_id (opcional)
        return $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;
    }

    public function dashboard(Request $request) 
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth());
        $end = $request->input('end_date', Carbon::now()->endOfMonth());
        $doctorId = $this->getEffectiveDoctorId($request);

        $patientsQuery = Patient::whereBetween('created_at', [$start, $end]);
        if ($doctorId) {
            $patientsQuery->whereHas('doctors', fn($q) => $q->where('doctors.id', $doctorId));
        }
        $totalPatients = $patientsQuery->count();

        $aptsQuery = Appointment::whereBetween('appointment_date', [$start, $end]);
        if ($doctorId) $aptsQuery->where('doctor_id', $doctorId);
        $totalAppointments = (clone $aptsQuery)->count();
        
        $appointmentsByStatus = (clone $aptsQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'total_new_patients' => $totalPatients,
            'total_appointments' => $totalAppointments,
            'appointments_by_status' => $appointmentsByStatus
        ]);
    }

    public function appointments(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth());
        $end = $request->input('end_date', Carbon::now()->endOfMonth());
        $doctorId = $this->getEffectiveDoctorId($request);

        $query = Appointment::with(['doctor', 'patient'])
            ->whereBetween('appointment_date', [$start, $end]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        return response()->json($query->get());
    }

    public function doctors(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth());
        $end = $request->input('end_date', Carbon::now()->endOfMonth());
        $doctorId = $this->getEffectiveDoctorId($request);

        $query = Doctor::withCount(['appointments' => function($q) use ($start, $end) {
            $q->whereBetween('appointment_date', [$start, $end])->where('status', 'completed');
        }]);

        // Si es doctor, solo muestra su propia info
        if ($doctorId) {
            $query->where('id', $doctorId);
        }

        $doctors = $query->orderBy('appointments_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($doctors);
    }

    public function exportExcel(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth());
        $end = $request->input('end_date', Carbon::now()->endOfMonth());
        $doctorId = $this->getEffectiveDoctorId($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AppointmentsExport($start, $end, $doctorId), 
            'reporte_citas.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth());
        $end = $request->input('end_date', Carbon::now()->endOfMonth());
        $doctorId = $this->getEffectiveDoctorId($request);

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $query = Appointment::with(['doctor', 'patient'])
            ->whereBetween('appointment_date', [$start, $end]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $appointments = $query->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.appointments', compact('appointments', 'start', 'end'));
        
        return $pdf->download('reporte_citas.pdf');
    }
}
