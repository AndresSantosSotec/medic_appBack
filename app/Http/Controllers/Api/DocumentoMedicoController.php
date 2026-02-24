<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubirDocumentoRequest;
use App\Http\Requests\SubirDocumentoChunkRequest;
use App\Models\PacienteDocumento;
use App\Services\DocumentoMedicoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentoMedicoController extends Controller
{
    private DocumentoMedicoService $service;

    public function __construct(DocumentoMedicoService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos
     * Lista todos los documentos de un paciente
     */
    public function index(Request $request, int $pacienteId): JsonResponse
    {
        try {
            // Verificar permisos usando Policy
            Gate::authorize('viewAny', [PacienteDocumento::class, $pacienteId]);

            $filtros = [
                'categoria' => $request->query('categoria'),
                'tipo_documento' => $request->query('tipo'),
                'doctor_id' => $request->query('doctor_id'),
                'fecha_desde' => $request->query('fecha_desde'),
                'fecha_hasta' => $request->query('fecha_hasta'),
                'solo_visibles_paciente' => $request->query('solo_visibles_paciente', false),
            ];

            $resultado = $this->service->obtenerDocumentosPaciente($pacienteId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener documentos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los documentos',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/pacientes/{pacienteId}/documentos
     * Sube uno o múltiples documentos
     */
    public function store(SubirDocumentoRequest $request, int $pacienteId): JsonResponse
    {
        try {
            // Verificar permisos
            Gate::authorize('create', [PacienteDocumento::class, $pacienteId]);

            $resultado = $this->service->subirDocumento($request, $pacienteId);

            return response()->json([
                'success' => true,
                'message' => 'Documentos procesados correctamente',
                'data' => $resultado,
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para agregar documentos a este paciente',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Error al subir documentos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar los documentos',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos/{documentoId}
     * Obtiene detalles de un documento específico
     */
    public function show(int $pacienteId, int $documentoId): JsonResponse
    {
        try {
            $documento = PacienteDocumento::with(['doctor', 'subidoPor', 'consulta', 'cita'])
                ->where('paciente_id', $pacienteId)
                ->findOrFail($documentoId);

            Gate::authorize('view', $documento);

            return response()->json([
                'success' => true,
                'data' => $documento,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Documento no encontrado',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el documento',
            ], 500);
        }
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos/{documentoId}/url
     * Genera URL temporal para visualizar/descargar
     */
    public function obtenerUrl(int $pacienteId, int $documentoId): JsonResponse
    {
        try {
            $documento = PacienteDocumento::where('paciente_id', $pacienteId)
                ->findOrFail($documentoId);

            Gate::authorize('view', $documento);

            $resultado = $this->service->generarUrlTemporal($documentoId, 30);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $resultado['url'],
                    'expira_en' => $resultado['expira_en'],
                    'documento' => [
                        'id' => $documento->id,
                        'nombre' => $documento->nombre_original,
                        'tipo' => $documento->mime_type,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al generar URL temporal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al generar la URL',
            ], 500);
        }
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos/{documentoId}/descargar
     * Descarga el documento directamente
     */
    public function descargar(int $pacienteId, int $documentoId)
    {
        try {
            $documento = PacienteDocumento::where('paciente_id', $pacienteId)
                ->findOrFail($documentoId);

            Gate::authorize('download', $documento);

            $disk = config('documentos_medicos.storage_disk');

            if (!Storage::disk($disk)->exists($documento->ruta_storage)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no existe',
                ], 404);
            }

            // Registrar descarga
            $this->service->generarUrlDescarga($documentoId, 1);

            $rutaCompleta = Storage::disk($disk)->path($documento->ruta_storage);

            return response()->download(
                $rutaCompleta,
                $documento->nombre_original
            );

        } catch (\Exception $e) {
            Log::error('Error al descargar documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el documento',
            ], 500);
        }
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos/{documentoId}/preview
     * Genera preview/thumbnail del documento
     */
    public function preview(int $pacienteId, int $documentoId): JsonResponse
    {
        try {
            $documento = PacienteDocumento::where('paciente_id', $pacienteId)
                ->findOrFail($documentoId);

            Gate::authorize('view', $documento);

            // Por ahora retornar metadata básica
            // En producción, aquí se generaría un thumbnail real
            return response()->json([
                'success' => true,
                'data' => [
                    'tipo' => $documento->mime_type,
                    'es_imagen' => $documento->esImagen(),
                    'es_pdf' => $documento->esPdf(),
                    'icono' => $documento->icono_tipo,
                    'metadata' => $documento->metadata,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar preview',
            ], 500);
        }
    }

    /**
     * DELETE /api/pacientes/{pacienteId}/documentos/{documentoId}
     * Elimina un documento (soft delete)
     */
    public function destroy(int $pacienteId, int $documentoId): JsonResponse
    {
        try {
            $documento = PacienteDocumento::where('paciente_id', $pacienteId)
                ->findOrFail($documentoId);

            Gate::authorize('delete', $documento);

            $this->service->eliminarDocumento($documentoId);

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente',
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos para eliminar este documento',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Error al eliminar documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el documento',
            ], 500);
        }
    }

    /**
     * GET /api/pacientes/{pacienteId}/documentos/exportar
     * Exporta todos los documentos del expediente en un ZIP
     */
    public function exportar(int $pacienteId)
    {
        try {
            Gate::authorize('viewAny', [PacienteDocumento::class, $pacienteId]);

            $rutaZip = $this->service->exportarExpediente($pacienteId);

            return response()->download($rutaZip)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error al exportar expediente: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar el expediente',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/documentos/upload-chunk
     * Sube un fragmento de archivo grande (para archivos >10MB)
     */
    public function uploadChunk(SubirDocumentoChunkRequest $request): JsonResponse
    {
        try {
            $uploadId = $request->input('upload_id');
            $chunkNumber = $request->input('chunk_number');
            $totalChunks = $request->input('total_chunks');
            $fileName = $request->input('file_name');

            $chunkPath = "temp/chunks/{$uploadId}/chunk_{$chunkNumber}";

            // Guardar chunk
            $request->file('chunk')->storeAs(
                dirname($chunkPath),
                basename($chunkPath),
                'local'
            );

            // Verificar si todos los chunks están completos
            $chunksCompletos = $this->verificarChunksCompletos($uploadId, $totalChunks);

            if ($chunksCompletos) {
                // Ensamblar archivo completo
                $archivoEnsamblado = $this->ensamblarChunks($uploadId, $totalChunks, $fileName);

                return response()->json([
                    'success' => true,
                    'completed' => true,
                    'message' => 'Archivo completo recibido',
                    'file_path' => $archivoEnsamblado,
                ]);
            }

            return response()->json([
                'success' => true,
                'completed' => false,
                'message' => "Chunk {$chunkNumber} recibido",
                'progress' => round(($chunkNumber / $totalChunks) * 100, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('Error al subir chunk: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el fragmento',
            ], 500);
        }
    }

    /**
     * Métodos auxiliares privados
     */
    private function verificarChunksCompletos(string $uploadId, int $totalChunks): bool
    {
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "temp/chunks/{$uploadId}/chunk_{$i}";
            if (!Storage::disk('local')->exists($chunkPath)) {
                return false;
            }
        }
        return true;
    }

    private function ensamblarChunks(string $uploadId, int $totalChunks, string $fileName): string
    {
        $archivoFinal = "temp/assembled/{$uploadId}_{$fileName}";
        $handle = fopen(storage_path("app/{$archivoFinal}"), 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = "temp/chunks/{$uploadId}/chunk_{$i}";
            $chunkContent = Storage::disk('local')->get($chunkPath);
            fwrite($handle, $chunkContent);

            // Eliminar chunk después de añadirlo
            Storage::disk('local')->delete($chunkPath);
        }

        fclose($handle);

        // Eliminar directorio de chunks
        Storage::disk('local')->deleteDirectory("temp/chunks/{$uploadId}");

        return $archivoFinal;
    }
}
