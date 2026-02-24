<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PacienteDocumento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'paciente_id',
        'doctor_id',
        'consulta_id',
        'cita_id',
        'tipo_documento',
        'categoria',
        'nombre_archivo',
        'nombre_original',
        'ruta_storage',
        'mime_type',
        'tamanio_bytes',
        'extension',
        'descripcion',
        'fecha_documento',
        'es_dicom',
        'metadata',
        'subido_por',
        'visible_para_paciente',
        'es_confidencial',
        'hash_sha256',
        'ultimo_acceso_at',
        'total_accesos',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'es_dicom' => 'boolean',
        'visible_para_paciente' => 'boolean',
        'es_confidencial' => 'boolean',
        'metadata' => 'array',
        'ultimo_acceso_at' => 'datetime',
        'total_accesos' => 'integer',
        'tamanio_bytes' => 'integer',
    ];

    protected $hidden = [
        'ruta_storage', // Nunca exponer rutas reales
        'hash_sha256',
    ];

    protected $appends = [
        'tamanio_legible',
        'icono_tipo',
    ];

    /**
     * Relaciones
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'paciente_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consultation::class, 'consulta_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'cita_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function accesos(): HasMany
    {
        return $this->hasMany(PacienteDocumentoAcceso::class, 'documento_id');
    }

    /**
     * Accessors
     */
    public function getTamanioLegibleAttribute(): string
    {
        $bytes = $this->tamanio_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIconoTipoAttribute(): string
    {
        return match($this->extension) {
            'pdf' => '📄',
            'dcm' => '🏥',
            'jpg', 'jpeg', 'png' => '🖼️',
            'mp4', 'mov', 'avi' => '🎥',
            'xlsx', 'xls', 'csv' => '📊',
            'doc', 'docx' => '📝',
            default => '📎',
        };
    }

    /**
     * Scopes para filtrado
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_documento', $tipo);
    }

    public function scopePorDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeVisiblesParaPaciente($query)
    {
        return $query->where('visible_para_paciente', true);
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_documento', [$desde, $hasta]);
    }

    /**
     * Métodos de utilidad
     */
    public function existeArchivo(): bool
    {
        $disk = config('documentos_medicos.storage_disk');
        return Storage::disk($disk)->exists($this->ruta_storage);
    }

    public function obtenerTamanioReal(): int|false
    {
        $disk = config('documentos_medicos.storage_disk');
        return Storage::disk($disk)->size($this->ruta_storage);
    }

    public function esImagen(): bool
    {
        return in_array($this->mime_type, [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ]);
    }

    public function esPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function esVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }
}
