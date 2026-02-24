<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PacienteDocumento;
use App\Models\Patient;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentoMedicoPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede ver cualquier documento del paciente
     */
    public function viewAny(User $user, int $pacienteId): bool
    {
        // Admin puede ver todo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Verificar si el doctor tiene acceso al paciente
        if ($user->doctor) {
            $tieneAcceso = $user->doctor->patients()
                ->where('patients.id', $pacienteId)
                ->exists();

            return $tieneAcceso && $user->hasPermission('view-patients');
        }

        // Recepcionistas con permiso pueden ver
        if ($user->hasRole('receptionist') && $user->hasPermission('view-patients')) {
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede ver un documento específico
     */
    public function view(User $user, PacienteDocumento $documento): bool
    {
        // Admin puede ver todo
        if ($user->hasRole('admin')) {
            return true;
        }

        // El doctor que subió el documento puede verlo
        if ($user->doctor && $user->doctor->id === $documento->doctor_id) {
            return true;
        }

        // Verificar si el doctor tiene acceso al paciente del documento
        if ($user->doctor) {
            $tieneAcceso = $user->doctor->patients()
                ->where('patients.id', $documento->paciente_id)
                ->exists();

            return $tieneAcceso && $user->hasPermission('view-patients');
        }

        // Recepcionistas con permiso
        if ($user->hasRole('receptionist') && $user->hasPermission('view-patients')) {
            // Si el documento es confidencial, solo puede verlo el doctor que lo subió o admin
            if ($documento->es_confidencial) {
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede crear documentos para un paciente
     */
    public function create(User $user, int $pacienteId): bool
    {
        // Admin puede crear para cualquier paciente
        if ($user->hasRole('admin')) {
            return true;
        }

        // Solo doctores pueden subir documentos
        if ($user->doctor) {
            // Verificar que el doctor tenga acceso al paciente
            $tieneAcceso = $user->doctor->patients()
                ->where('patients.id', $pacienteId)
                ->exists();

            return $tieneAcceso && $user->hasPermission('create-patients');
        }

        // Recepcionistas con permisos especiales pueden subir ciertos documentos
        if ($user->hasRole('receptionist') && $user->hasPermission('create-patients')) {
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede actualizar un documento
     */
    public function update(User $user, PacienteDocumento $documento): bool
    {
        // Admin puede actualizar todo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Solo el doctor que subió el documento puede actualizarlo
        if ($user->doctor && $user->doctor->id === $documento->doctor_id) {
            return $user->hasPermission('edit-patients');
        }

        return false;
    }

    /**
     * Determina si el usuario puede eliminar un documento
     */
    public function delete(User $user, PacienteDocumento $documento): bool
    {
        // Admin puede eliminar todo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Solo el doctor que subió el documento puede eliminarlo
        if ($user->doctor && $user->doctor->id === $documento->doctor_id) {
            return $user->hasPermission('edit-patients');
        }

        return false;
    }

    /**
     * Determina si el usuario puede descargar un documento
     */
    public function download(User $user, PacienteDocumento $documento): bool
    {
        // Mismo criterio que view, pero registrando en auditoría
        return $this->view($user, $documento);
    }

    /**
     * Determina si el usuario puede compartir un documento
     */
    public function share(User $user, PacienteDocumento $documento): bool
    {
        // Solo admin y el doctor que lo subió pueden compartir
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->doctor && $user->doctor->id === $documento->doctor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede ver documentos confidenciales
     */
    public function viewConfidential(User $user, PacienteDocumento $documento): bool
    {
        // Solo admin y el doctor que lo subió
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->doctor && $user->doctor->id === $documento->doctor_id) {
            return true;
        }

        return false;
    }

    /**
     * Determina si el usuario puede exportar el expediente completo
     */
    public function exportExpediente(User $user, int $pacienteId): bool
    {
        // Admin siempre puede
        if ($user->hasRole('admin')) {
            return true;
        }

        // Doctores que tienen al paciente asignado
        if ($user->doctor) {
            $tieneAcceso = $user->doctor->patients()
                ->where('patients.id', $pacienteId)
                ->exists();

            return $tieneAcceso;
        }

        return false;
    }
}
