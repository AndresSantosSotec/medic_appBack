<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirDocumentoChunkRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para subida por chunks
     */
    public function rules(): array
    {
        return [
            'chunk' => 'required|file|max:10240', // 10MB por chunk
            'chunk_number' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'upload_id' => 'required|string|uuid',
            'categoria' => 'required|in:laboratorio,radiologia,consulta,prescripcion,consentimiento,referencia,otro',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'chunk.required' => 'El fragmento del archivo es requerido',
            'chunk.file' => 'El fragmento debe ser un archivo válido',
            'chunk.max' => 'El fragmento excede el tamaño máximo de 10MB',
            'chunk_number.required' => 'El número de fragmento es requerido',
            'total_chunks.required' => 'El total de fragmentos es requerido',
            'file_name.required' => 'El nombre del archivo es requerido',
            'upload_id.required' => 'El ID de subida es requerido',
            'upload_id.uuid' => 'El ID de subida debe ser un UUID válido',
        ];
    }
}
