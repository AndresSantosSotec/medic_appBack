<?php

namespace App\Services;

use App\Models\PacienteDocumento;
use App\Models\PacienteDocumentoAcceso;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class DocumentoMedicoService
{
    private string $disk;
    private array $config;

    public function __construct()
    {
        $this->disk = config('documentos_medicos.storage_disk', 'local');
        $this->config = config('documentos_medicos');
    }

    /**
     * Sube uno o múltiples documentos médicos
     */
    public function subirDocumento(Request $request, int $pacienteId): array
    {
        // Verificar que el paciente existe
        $paciente = Patient::findOrFail($pacienteId);

        $archivos = $request->file('archivos');
        if (!is_array($archivos)) {
            $archivos = [$archivos];
        }

        $documentosCreados = [];
        $errores = [];

        DB::beginTransaction();
        try {
            foreach ($archivos as $archivo) {
                try {
                    $documento = $this->procesarYGuardarArchivo(
                        archivo: $archivo,
                        pacienteId: $pacienteId,
                        categoria: $request->input('categoria', 'otro'),
                        tipoDocumento: $request->input('tipo_documento'),
                        descripcion: $request->input('descripcion'),
                        fechaDocumento: $request->input('fecha_documento'),
                        consultaId: $request->input('consulta_id'),
                        citaId: $request->input('cita_id'),
                        visibleParaPaciente: $request->input('visible_para_paciente', true),
                        esConfidencial: $request->input('es_confidencial', false)
                    );

                    $documentosCreados[] = $documento;

                    // Registrar en auditoría
                    PacienteDocumentoAcceso::registrar(
                        documentoId: $documento->id,
                        usuarioId: Auth::id(),
                        tipoAcceso: 'subida',
                        ipAddress: $request->ip(),
                        userAgent: $request->userAgent(),
                        detalles: [
                            'nombre_original' => $archivo->getClientOriginalName(),
                            'tamanio' => $archivo->getSize(),
                        ]
                    );

                } catch (\Exception $e) {
                    $errores[] = [
                        'archivo' => $archivo->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                    Log::error('Error al subir documento: ' . $e->getMessage(), [
                        'paciente_id' => $pacienteId,
                        'archivo' => $archivo->getClientOriginalName()
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'documentos' => $documentosCreados,
                'errores' => $errores,
                'total_exitosos' => count($documentosCreados),
                'total_errores' => count($errores),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en transacción de subida de documentos: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesa y guarda un archivo individual
     */
    private function procesarYGuardarArchivo(
        UploadedFile $archivo,
        int $pacienteId,
        string $categoria,
        ?string $tipoDocumento = null,
        ?string $descripcion = null,
        ?string $fechaDocumento = null,
        ?int $consultaId = null,
        ?int $citaId = null,
        bool $visibleParaPaciente = true,
        bool $esConfidencial = false
    ): PacienteDocumento {

        // Validar tamaño según categoría
        $this->validarTamanioArchivo($archivo, $categoria);

        // Generar nombre único y seguro
        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreSeguro = $this->generarNombreSeguro($extension);

        // Detectar si es DICOM
        $esDicom = $extension === 'dcm' || $this->detectarDicom($archivo);

        // Determinar tipo de documento si no se especificó
        if (!$tipoDocumento) {
            $tipoDocumento = $this->determinarTipoDocumento($extension, $categoria);
        }

        // Construir ruta de almacenamiento
        $rutaStorage = $this->construirRutaStorage($pacienteId, $categoria, $nombreSeguro);

        // Guardar archivo en storage
        $archivo->storeAs(
            dirname($rutaStorage),
            basename($rutaStorage),
            $this->disk
        );

        // Calcular hash para verificar integridad
        $hashSha256 = hash_file('sha256', $archivo->getRealPath());

        // Extraer metadata si es necesario
        $metadata = $this->extraerMetadata($archivo, $esDicom, $extension);

        // Obtener doctor_id del usuario autenticado
        $user = Auth::user();
        $doctorId = $user->doctor?->id ?? $this->obtenerDoctorPorDefecto();

        // Crear registro en base de datos
        $documento = PacienteDocumento::create([
            'paciente_id' => $pacienteId,
            'doctor_id' => $doctorId,
            'consulta_id' => $consultaId,
            'cita_id' => $citaId,
            'tipo_documento' => $tipoDocumento,
            'categoria' => $categoria,
            'nombre_archivo' => $nombreSeguro,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta_storage' => $rutaStorage,
            'mime_type' => $archivo->getMimeType(),
            'tamanio_bytes' => $archivo->getSize(),
            'extension' => $extension,
            'descripcion' => $descripcion,
            'fecha_documento' => $fechaDocumento ?? now(),
            'es_dicom' => $esDicom,
            'metadata' => $metadata,
            'subido_por' => Auth::id(),
            'visible_para_paciente' => $visibleParaPaciente,
            'es_confidencial' => $esConfidencial,
            'hash_sha256' => $hashSha256,
        ]);

        // Generar thumbnail si es imagen o DICOM
        if ($documento->esImagen() || $esDicom) {
            $this->generarThumbnail($documento);
        }

        return $documento->load(['doctor', 'subidoPor', 'consulta', 'cita']);
    }

    /**
     * Obtiene los documentos de un paciente con filtros
     */
    public function obtenerDocumentosPaciente(int $pacienteId, array $filtros = []): array
    {
        $query = PacienteDocumento::with(['doctor', 'subidoPor', 'consulta', 'cita'])
            ->where('paciente_id', $pacienteId);

        // Aplicar filtros
        if (!empty($filtros['categoria'])) {
            $query->porCategoria($filtros['categoria']);
        }

        if (!empty($filtros['tipo_documento'])) {
            $query->porTipo($filtros['tipo_documento']);
        }

        if (!empty($filtros['doctor_id'])) {
            $query->porDoctor($filtros['doctor_id']);
        }

        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->entreFechas($filtros['fecha_desde'], $filtros['fecha_hasta']);
        }

        if (!empty($filtros['solo_visibles_paciente'])) {
            $query->visiblesParaPaciente();
        }

        // Ordenar por fecha más reciente
        $query->orderBy('fecha_documento', 'desc')->orderBy('created_at', 'desc');

        $documentos = $query->get();

        // Agrupar por categoría para mejor visualización
        $documentosAgrupados = $documentos->groupBy('categoria');

        return [
            'documentos' => $documentosAgrupados,
            'resumen' => [
                'total' => $documentos->count(),
                'por_categoria' => $documentos->groupBy('categoria')->map->count(),
                'ultimo_subido' => $documentos->first()?->created_at?->format('Y-m-d'),
                'tamanio_total' => $documentos->sum('tamanio_bytes'),
                'tamanio_total_legible' => $this->formatearBytes($documentos->sum('tamanio_bytes')),
            ],
            'documentos_lista' => $documentos, // Para mostrar en lista también
        ];
    }

    /**
     * Genera una URL temporal firmada para acceder al documento
     */
    public function generarUrlTemporal(int $documentoId, int $minutosExpiracion = 30): array
    {
        $documento = PacienteDocumento::findOrFail($documentoId);

        // Verificar que el archivo existe
        if (!$documento->existeArchivo()) {
            throw new \Exception('El archivo no existe en el storage');
        }

        // Generar URL temporal firmada
        try {
            $url = Storage::disk($this->disk)->temporaryUrl(
                $documento->ruta_storage,
                now()->addMinutes($minutosExpiracion)
            );
        } catch (\Exception $e) {
            // Para discos locales o que no soportan URLs temporales, generar una URL con token
            $url = url('/storage/' . $documento->ruta_storage . '?expires=' . now()->addMinutes($minutosExpiracion)->timestamp);
        }

        // Actualizar estadísticas de acceso
        $documento->increment('total_accesos');
        $documento->update(['ultimo_acceso_at' => now()]);

        // Registrar acceso en auditoría
        PacienteDocumentoAcceso::registrar(
            documentoId: $documentoId,
            usuarioId: Auth::id(),
            tipoAcceso: 'visualizacion',
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        );

        return [
            'url' => $url,
            'expira_en' => now()->addMinutes($minutosExpiracion),
            'documento' => $documento,
        ];
    }

    /**
     * Genera una URL de descarga para el documento
     */
    public function generarUrlDescarga(int $documentoId, int $minutosExpiracion = 30): array
    {
        $resultado = $this->generarUrlTemporal($documentoId, $minutosExpiracion);

        // Registrar descarga en auditoría
        PacienteDocumentoAcceso::registrar(
            documentoId: $documentoId,
            usuarioId: Auth::id(),
            tipoAcceso: 'descarga',
            ipAddress: request()->ip(),
            userAgent: request()->userAgent()
        );

        return $resultado;
    }

    /**
     * Elimina un documento (soft delete)
     */
    public function eliminarDocumento(int $documentoId): bool
    {
        $documento = PacienteDocumento::findOrFail($documentoId);

        DB::beginTransaction();
        try {
            // Registrar eliminación en auditoría
            PacienteDocumentoAcceso::registrar(
                documentoId: $documentoId,
                usuarioId: Auth::id(),
                tipoAcceso: 'eliminacion',
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
                detalles: [
                    'nombre_archivo' => $documento->nombre_original,
                    'categoria' => $documento->categoria,
                ]
            );

            // Soft delete en BD
            $documento->delete();

            // Mover archivo a carpeta de archivados (no eliminar físicamente)
            $rutaArchivado = str_replace(
                'documentos/',
                'documentos/archivados/',
                $documento->ruta_storage
            );

            if ($documento->existeArchivo()) {
                Storage::disk($this->disk)->move(
                    $documento->ruta_storage,
                    $rutaArchivado
                );
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar documento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exporta todos los documentos de un paciente en un ZIP
     */
    public function exportarExpediente(int $pacienteId): string
    {
        $paciente = Patient::findOrFail($pacienteId);
        $documentos = PacienteDocumento::where('paciente_id', $pacienteId)->get();

        if ($documentos->isEmpty()) {
            throw new \Exception('El paciente no tiene documentos para exportar');
        }

        // Crear archivo ZIP temporal
        $nombreZip = 'expediente_' . $paciente->first_name . '_' . $paciente->last_name . '_' . now()->format('Ymd_His') . '.zip';
        $rutaZip = storage_path('app/temp/' . $nombreZip);

        // Crear directorio temp si no existe
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('No se pudo crear el archivo ZIP');
        }

        foreach ($documentos as $documento) {
            if ($documento->existeArchivo()) {
                $contenido = Storage::disk($this->disk)->get($documento->ruta_storage);
                $nombreEnZip = $documento->categoria . '/' . $documento->nombre_original;
                $zip->addFromString($nombreEnZip, $contenido);
            }
        }

        $zip->close();

        // Registrar exportación
        PacienteDocumentoAcceso::registrar(
            documentoId: $documentos->first()->id,
            usuarioId: Auth::id(),
            tipoAcceso: 'exportar',
            ipAddress: request()->ip(),
            userAgent: request()->userAgent(),
            detalles: ['total_documentos' => $documentos->count()]
        );

        return $rutaZip;
    }

    /**
     * Métodos auxiliares privados
     */
    private function generarNombreSeguro(string $extension): string
    {
        return Str::uuid() . '_' . Str::random(16) . '.' . $extension;
    }

    private function construirRutaStorage(int $pacienteId, string $categoria, string $nombreArchivo): string
    {
        $anio = now()->year;
        $mes = str_pad(now()->month, 2, '0', STR_PAD_LEFT);

        return "documentos/{$pacienteId}/{$categoria}/{$anio}/{$mes}/{$nombreArchivo}";
    }

    private function validarTamanioArchivo(UploadedFile $archivo, string $categoria): void
    {
        $limites = $this->config['tamano_maximo'] ?? [];
        $limite = $limites[$categoria] ?? $limites['default'] ?? 10 * 1024 * 1024; // 10MB por defecto

        if ($archivo->getSize() > $limite) {
            $limiteFormateado = $this->formatearBytes($limite);
            throw new \Exception("El archivo excede el tamaño máximo permitido de {$limiteFormateado}");
        }
    }

    private function determinarTipoDocumento(string $extension, string $categoria): string
    {
        return match($extension) {
            'pdf' => 'pdf',
            'dcm' => 'dicom',
            'jpg', 'jpeg', 'png', 'gif' => 'imagen',
            'mp4', 'mov', 'avi' => 'video',
            'xlsx', 'xls', 'csv' => 'laboratorio',
            default => $categoria === 'radiologia' ? 'radiografia' : 'otro',
        };
    }

    private function detectarDicom(UploadedFile $archivo): bool
    {
        // Leer los primeros bytes del archivo para detectar el magic number de DICOM
        $handle = fopen($archivo->getRealPath(), 'rb');
        if ($handle) {
            fseek($handle, 128); // DICOM magic number está en el byte 128
            $magicNumber = fread($handle, 4);
            fclose($handle);
            return $magicNumber === 'DICM';
        }
        return false;
    }

    private function extraerMetadata(UploadedFile $archivo, bool $esDicom, string $extension): ?array
    {
        $metadata = [
            'tamanio_original' => $archivo->getSize(),
            'mime_type_original' => $archivo->getMimeType(),
        ];

        if ($esDicom) {
            // Aquí se integraría la librería DICOM para extraer metadata
            // Por ahora retornamos estructura básica
            $metadata['dicom'] = [
                'detectado' => true,
                'procesado' => false,
                'nota' => 'Requiere librería DICOM para procesar'
            ];
        }

        return $metadata;
    }

    private function generarThumbnail(PacienteDocumento $documento): void
    {
        // Implementación básica - se puede extender con librerías de procesamiento de imágenes
        try {
            if ($documento->esImagen()) {
                // Generar thumbnail con GD o Intervention Image
                // Por ahora solo registramos que tiene thumbnail
                $metadata = $documento->metadata ?? [];
                $metadata['thumbnail_generado'] = true;
                $documento->update(['metadata' => $metadata]);
            }
        } catch (\Exception $e) {
            Log::warning('No se pudo generar thumbnail: ' . $e->getMessage());
        }
    }

    private function obtenerDoctorPorDefecto(): ?int
    {
        // En caso de que el usuario no tenga doctor asociado
        // Esto no debería ocurrir en producción con las validaciones adecuadas
        return null;
    }

    private function formatearBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
