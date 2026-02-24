<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirDocumentoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta petición
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el Policy
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        $categoria = $this->input('categoria', 'otro');

        // Definir extensiones permitidas por categoría
        $extensionesPorCategoria = [
            'laboratorio' => 'pdf,xlsx,xls,csv',
            'radiologia' => 'jpg,jpeg,png,dcm,dicom',
            'consulta' => 'pdf,jpg,jpeg,png,doc,docx',
            'prescripcion' => 'pdf,jpg,jpeg,png',
            'consentimiento' => 'pdf',
            'referencia' => 'pdf,doc,docx',
            'otro' => 'pdf,jpg,jpeg,png,xlsx,xls,csv,doc,docx,mp4,mov,dcm',
        ];

        // Tamaños máximos en KB por categoría
        $tamaniosMaximos = [
            'laboratorio' => 5120,      // 5MB
            'radiologia' => 51200,      // 50MB
            'consulta' => 10240,        // 10MB
            'prescripcion' => 10240,    // 10MB
            'consentimiento' => 10240,  // 10MB
            'referencia' => 10240,      // 10MB
            'otro' => 512000,           // 500MB (para videos)
        ];

        $extensiones = $extensionesPorCategoria[$categoria] ?? $extensionesPorCategoria['otro'];
        $tamanioMaximo = $tamaniosMaximos[$categoria] ?? $tamaniosMaximos['otro'];

        return [
            'archivos' => 'required',
            'archivos.*' => [
                'required',
                'file',
                "mimes:{$extensiones}",
                "max:{$tamanioMaximo}",
            ],
            'categoria' => 'required|in:laboratorio,radiologia,consulta,prescripcion,consentimiento,referencia,otro',
            'tipo_documento' => 'nullable|in:analisis,receta,radiografia,laboratorio,imagen,video,dicom,pdf,otro',
            'descripcion' => 'nullable|string|max:1000',
            'fecha_documento' => 'nullable|date',
            'consulta_id' => 'nullable|exists:consultations,id',
            'cita_id' => 'nullable|exists:appointments,id',
            'visible_para_paciente' => 'nullable|boolean',
            'es_confidencial' => 'nullable|boolean',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'archivos.required' => 'Debe seleccionar al menos un archivo para subir',
            'archivos.*.required' => 'Uno de los archivos está vacío',
            'archivos.*.file' => 'Uno de los archivos no es válido',
            'archivos.*.mimes' => 'El formato del archivo no está permitido para esta categoría',
            'archivos.*.max' => 'Uno de los archivos excede el tamaño máximo permitido',
            'categoria.required' => 'Debe especificar la categoría del documento',
            'categoria.in' => 'La categoría especificada no es válida',
            'consulta_id.exists' => 'La consulta especificada no existe',
            'cita_id.exists' => 'La cita especificada no existe',
        ];
    }

    /**
     * Atributos personalizados para los mensajes de validación
     */
    public function attributes(): array
    {
        return [
            'archivos' => 'archivos',
            'categoria' => 'categoría',
            'descripcion' => 'descripción',
            'fecha_documento' => 'fecha del documento',
        ];
    }
}
