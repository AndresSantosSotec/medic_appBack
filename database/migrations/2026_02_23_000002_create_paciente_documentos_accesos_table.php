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
        Schema::create('paciente_documentos_accesos', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('documento_id')->constrained('paciente_documentos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');

            // Tipo de acceso
            $table->enum('tipo_acceso', [
                'visualizacion',
                'descarga',
                'eliminacion',
                'modificacion',
                'compartir'
            ]);

            // Información del acceso
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('detalles')->nullable(); // JSON con info adicional

            $table->timestamp('created_at');

            // Índices para reportes de auditoría
            $table->index(['documento_id', 'created_at']);
            $table->index(['usuario_id', 'tipo_acceso']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paciente_documentos_accesos');
    }
};
