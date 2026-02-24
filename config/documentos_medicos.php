<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Disk para Documentos Médicos
    |--------------------------------------------------------------------------
    |
    | Define qué disco de storage usar para almacenar documentos médicos.
    | En desarrollo: 'local'
    | En producción: 's3' o cualquier otro disco configurado en filesystems.php
    |
    */

    'storage_disk' => env('DOCUMENTOS_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Tamaños Máximos por Categoría (en bytes)
    |--------------------------------------------------------------------------
    |
    | Define el tamaño máximo permitido para cada categoría de documento.
    | Los valores están en bytes.
    |
    */

    'tamano_maximo' => [
        'laboratorio' => 5 * 1024 * 1024,      // 5MB
        'radiologia' => 50 * 1024 * 1024,      // 50MB
        'consulta' => 10 * 1024 * 1024,        // 10MB
        'prescripcion' => 10 * 1024 * 1024,    // 10MB
        'consentimiento' => 10 * 1024 * 1024,  // 10MB
        'referencia' => 10 * 1024 * 1024,      // 10MB
        'otro' => 500 * 1024 * 1024,           // 500MB (para videos)
        'default' => 10 * 1024 * 1024,         // 10MB por defecto
    ],

    /*
    |--------------------------------------------------------------------------
    | Extensiones Permitidas por Categoría
    |--------------------------------------------------------------------------
    |
    | Define qué extensiones de archivo están permitidas para cada categoría.
    |
    */

    'extensiones_permitidas' => [
        'laboratorio' => ['pdf', 'xlsx', 'xls', 'csv'],
        'radiologia' => ['jpg', 'jpeg', 'png', 'dcm', 'dicom'],
        'consulta' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        'prescripcion' => ['pdf', 'jpg', 'jpeg', 'png'],
        'consentimiento' => ['pdf'],
        'referencia' => ['pdf', 'doc', 'docx'],
        'otro' => ['pdf', 'jpg', 'jpeg', 'png', 'xlsx', 'xls', 'csv', 'doc', 'docx', 'mp4', 'mov', 'dcm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MIME Types Permitidos
    |--------------------------------------------------------------------------
    |
    | Lista de MIME types aceptados para documentos médicos.
    |
    */

    'mime_types_permitidos' => [
        // PDFs
        'application/pdf',

        // Imágenes
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',

        // DICOM
        'application/dicom',

        // Videos
        'video/mp4',
        'video/quicktime',
        'video/x-msvideo',

        // Documentos Office
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/csv',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de URLs Temporales
    |--------------------------------------------------------------------------
    |
    | Tiempo de expiración por defecto para URLs temporales (en minutos).
    |
    */

    'url_temporal_expiracion' => env('DOCUMENTOS_URL_EXPIRACION', 30),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Thumbnails
    |--------------------------------------------------------------------------
    |
    | Configuración para la generación de miniaturas de documentos.
    |
    */

    'thumbnails' => [
        'enabled' => env('DOCUMENTOS_THUMBNAILS_ENABLED', true),
        'width' => 300,
        'height' => 300,
        'quality' => 80,
        'storage_path' => 'documentos/thumbnails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración DICOM
    |--------------------------------------------------------------------------
    |
    | Configuración específica para procesamiento de archivos DICOM.
    |
    */

    'dicom' => [
        'enabled' => env('DOCUMENTOS_DICOM_ENABLED', false),
        'extract_metadata' => true,
        'generate_preview' => true,
        'library' => env('DICOM_LIBRARY', 'imagick'), // 'imagick', 'dicom-tools', etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Chunks para Archivos Grandes
    |--------------------------------------------------------------------------
    |
    | Configuración para la subida de archivos grandes por fragmentos.
    |
    */

    'chunks' => [
        'enabled' => true,
        'tamanio_chunk' => 10 * 1024 * 1024, // 10MB por chunk
        'tiempo_expiracion' => 3600, // 1 hora para completar la subida
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Límites de subida para prevenir abuso del sistema.
    |
    */

    'rate_limiting' => [
        'max_archivos_por_minuto' => 20,
        'max_tamanio_total_por_hora' => 500 * 1024 * 1024, // 500MB por hora
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditoría y Logging
    |--------------------------------------------------------------------------
    |
    | Configuración de auditoría de accesos a documentos.
    |
    */

    'auditoria' => [
        'enabled' => true,
        'registrar_visualizaciones' => true,
        'registrar_descargas' => true,
        'registrar_eliminaciones' => true,
        'registrar_modificaciones' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    |
    | Opciones de seguridad adicionales.
    |
    */

    'seguridad' => [
        'verificar_hash' => true,
        'escanear_malware' => env('DOCUMENTOS_ESCANEAR_MALWARE', false),
        'cifrar_archivos' => env('DOCUMENTOS_CIFRAR', false),
        'watermark' => env('DOCUMENTOS_WATERMARK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retención de Archivos Eliminados
    |--------------------------------------------------------------------------
    |
    | Días que se mantienen los archivos después de soft delete antes de
    | eliminarlos permanentemente del storage.
    |
    */

    'retencion_archivos_eliminados' => env('DOCUMENTOS_RETENCION_DIAS', 90), // 90 días

    /*
    |--------------------------------------------------------------------------
    | Compresión de Archivos
    |--------------------------------------------------------------------------
    |
    | Configuración para comprimir archivos al exportar expedientes.
    |
    */

    'compresion' => [
        'enabled' => true,
        'formato' => 'zip', // 'zip', 'rar', 'tar.gz'
        'nivel' => 6, // 1-9, donde 9 es máxima compresión
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración de notificaciones relacionadas con documentos.
    |
    */

    'notificaciones' => [
        'notificar_paciente_documento_nuevo' => env('DOCUMENTOS_NOTIFICAR_PACIENTE', false),
        'notificar_doctor_documento_compartido' => true,
    ],

];
