<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteDocumentoAcceso extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'documento_id',
        'usuario_id',
        'tipo_acceso',
        'ip_address',
        'user_agent',
        'detalles',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'detalles' => 'array',
    ];

    /**
     * Relaciones
     */
    public function documento(): BelongsTo
    {
        return $this->belongsTo(PacienteDocumento::class, 'documento_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Método estático para registrar acceso
     */
    public static function registrar(
        int $documentoId,
        int $usuarioId,
        string $tipoAcceso,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $detalles = null
    ): self {
        return self::create([
            'documento_id' => $documentoId,
            'usuario_id' => $usuarioId,
            'tipo_acceso' => $tipoAcceso,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'detalles' => $detalles,
            'created_at' => now(),
        ]);
    }

    /**
     * Scopes para reportes
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_acceso', $tipo);
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeUltimosDias($query, $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
}
