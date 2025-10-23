<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000003_create_suicide_attempts_table.php
// MIGRACIÓN DE INTENTOS DE SUICIDIO
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
        Schema::create('suicide_attempts', function (Blueprint $table) {
            $table->id();
            
            // Relación con paciente
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete()
                ->comment('Paciente asociado');
            
            // Información del evento
            $table->timestamp('event_date')
                ->comment('Fecha y hora del evento');
            $table->tinyInteger('week_number')->nullable()
                ->comment('Semana epidemiológica');
            
            // Información de ingreso
            $table->enum('admission_via', ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA', 'COMUNIDAD'])
                ->comment('Vía de ingreso');
            $table->string('benefit_plan', 200)->nullable()
                ->comment('Plan de beneficios');
            
            // Detalles del intento
            $table->integer('attempt_number')
                ->default(1)
                ->comment('Número de intento (primero, segundo, etc.)');
            $table->text('trigger_factor')->nullable()
                ->comment('Factor desencadenante del intento');
            $table->json('risk_factors')->nullable()
                ->comment('Factores de riesgo identificados');
            $table->text('mechanism')->nullable()
                ->comment('Mecanismo utilizado en el intento');
            
            // Observaciones
            $table->text('additional_observation')->nullable()
                ->comment('Observaciones adicionales del caso');
            
            // Estado del caso
            $table->enum('status', ['active', 'inactive', 'resolved'])
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
            $table->index('event_date');
            $table->index('week_number');
            $table->index('attempt_number');
            $table->index('status');
            $table->index('created_at');
            $table->index(['patient_id', 'status']);
            $table->index(['patient_id', 'attempt_number']);
            $table->index(['event_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suicide_attempts');
    }
};