<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000002_create_mental_disorders_table.php
// MIGRACIÓN DE TRASTORNOS MENTALES
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
        Schema::create('mental_disorders', function (Blueprint $table) {
            $table->id();
            
            // Relación con paciente
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete()
                ->comment('Paciente asociado');
            
            // Información de ingreso
            $table->timestamp('admission_date')
                ->comment('Fecha y hora de ingreso');
            $table->enum('admission_type', ['AMBULATORIO', 'HOSPITALARIO', 'URGENCIAS'])
                ->comment('Tipo de ingreso');
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA'])
                ->comment('Vía por la cual ingresó');
            $table->string('service_area', 200)->nullable()
                ->comment('Área o servicio de atención');
            
            // Información del diagnóstico
            $table->string('diagnosis_code', 10)
                ->comment('Código del diagnóstico (CIE-10)');
            $table->timestamp('diagnosis_date')
                ->comment('Fecha del diagnóstico');
            $table->text('diagnosis_description')
                ->comment('Descripción del diagnóstico');
            $table->enum('diagnosis_type', ['Diagnostico Principal', 'Diagnostico Relacionado'])
                ->default('Diagnostico Principal')
                ->comment('Tipo de diagnóstico');
            
            // Observaciones
            $table->text('additional_observation')->nullable()
                ->comment('Observaciones adicionales del caso');
            
            // Estado del trastorno
            $table->enum('status', ['active', 'inactive', 'discharged'])
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
            $table->index('admission_type');
            $table->index('diagnosis_code');
            $table->index('diagnosis_date');
            $table->index('status');
            $table->index('created_at');
            $table->index(['patient_id', 'status']);
            $table->index(['admission_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mental_disorders');
    }
};