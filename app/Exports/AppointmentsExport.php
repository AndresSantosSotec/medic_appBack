<?php

namespace App\Exports;

use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppointmentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $start;
    protected $end;
    protected $doctorId;

    public function __construct($start, $end, $doctorId = null)
    {
        $this->start = $start;
        $this->end = $end;
        $this->doctorId = $doctorId;
    }

    public function collection()
    {
        $query = Appointment::with(['doctor', 'patient'])
            ->whereBetween('appointment_date', [$this->start, $this->end]);

        if ($this->doctorId) {
            $query->where('doctor_id', $this->doctorId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha',
            'Paciente',
            'Doctor',
            'Estado',
            'Motivo',
        ];
    }

    public function map($appointment): array
    {
        return [
            $appointment->id,
            $appointment->appointment_date,
            $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
            $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name,
            $appointment->status,
            $appointment->reason,
        ];
    }
}
