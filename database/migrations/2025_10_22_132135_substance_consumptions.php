<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000004_create_substance_consumptions_table.php
// MIGRACIÓN DE CONSUMO DE SUSTANCIAS PSICOACTIVAS
// ================================

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
        Schema::create('substance_consumptions', function (Blueprint $table) {
            $table->id();
            
            // Relación con paciente
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete()
                ->comment('Paciente asociado');
            
            // Información de ingreso
            $table->timestamp('admission_date')
                ->comment('Fecha y hora de ingreso');
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA', 'COMUNIDAD'])
                ->comment('Vía de ingreso');
            
            // Información del consumo
            $table->string('diagnosis', 500)
                ->comment('Diagnóstico relacionado al consumo');
            $table->json('substances_used')->nullable()
                ->comment('Sustancias psicoactivas utilizadas');
            $table->enum('consumption_level', [
                'Alto Riesgo',
                'Riesgo Moderado',
                'Bajo Riesgo',
                'Perjudicial'
            ])->default('Bajo Riesgo')
                ->comment('Nivel de riesgo del consumo');
            
            // Observaciones
            $table->text('additional_observation')->nullable()
                ->comment('Observaciones adicionales del caso');
            
            // Estado del caso
            $table->enum('status', ['active', 'inactive', 'in_treatment', 'recovered'])
                ->default('active')
                ->comment('Estado del caso');
            
            // Auditoría
            $table->foreignId('created_by_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que creó el registro');
            $table->foreignId('updated_by_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que actualizó el registro');
            
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('patient_id');
            $table->index('admission_date');
            $table->index('consumption_level');
            $table->index('status');
            $table->index('created_at');
            $table->index(['patient_id', 'status']);
            $table->index(['admission_date', 'status']);
            $table->index(['consumption_level', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substance_consumptions');
    }
};