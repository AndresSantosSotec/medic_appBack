<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paciente_documentos', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('paciente_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('restrict');
            $table->foreignId('consulta_id')->nullable()->constrained('consultations')->onDelete('set null');
            $table->foreignId('cita_id')->nullable()->constrained('appointments')->onDelete('set null');

            // Clasificación del documento
            $table->enum('tipo_documento', [
                'analisis',
                'receta',
                'radiografia',
                'laboratorio',
                'imagen',
                'video',
                'dicom',
                'pdf',
                'otro'
            ])->default('otro');

            $table->enum('categoria', [
                'laboratorio',
                'radiologia',
                'consulta',
                'prescripcion',
                'consentimiento',
                'referencia',
                'otro'
            ])->default('otro');

            // Información del archivo
            $table->string('nombre_archivo')->unique(); // nombre seguro generado
            $table->string('nombre_original'); // nombre del usuario
            $table->string('ruta_storage'); // ruta completa en storage
            $table->string('mime_type', 100);
            $table->bigInteger('tamanio_bytes')->unsigned();
            $table->string('extension', 10);

            // Información médica
            $table->text('descripcion')->nullable();
            $table->date('fecha_documento')->nullable(); // fecha del estudio/análisis
            $table->boolean('es_dicom')->default(false);

            // Metadata JSON para información adicional
            // Para DICOM: modalidad, institucion, medico_referente, etc.
            // Para laboratorios: valores, rangos, etc.
            $table->json('metadata')->nullable();

            // Control de acceso
            $table->foreignId('subido_por')->constrained('users')->onDelete('restrict');
            $table->boolean('visible_para_paciente')->default(true);
            $table->boolean('es_confidencial')->default(false);

            // Seguridad y auditoría
            $table->string('hash_sha256')->nullable(); // para verificar integridad
            $table->timestamp('ultimo_acceso_at')->nullable();
            $table->integer('total_accesos')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar búsquedas
            $table->index(['paciente_id', 'categoria', 'created_at']);
            $table->index(['paciente_id', 'doctor_id']);
            $table->index(['tipo_documento', 'categoria']);
            $table->index('fecha_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paciente_documentos');
    }
};
