<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000001_create_patients_table.php
// MIGRACIÓN DE PACIENTES
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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Información de identificación
            $table->enum('document_type', ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN'])
                ->comment('Tipo de documento de identidad');
            $table->string('document_number', 20)
                ->comment('Número de documento');
            $table->string('full_name', 300)
                ->comment('Nombre completo del paciente');

            // Información demográfica
            $table->enum('gender', ['Masculino', 'Femenino', 'Otro'])
                ->comment('Género del paciente');
            $table->date('birth_date')
                ->comment('Fecha de nacimiento');

            // Información de contacto
            $table->string('phone', 50)->nullable()
                ->comment('Número de teléfono');
            $table->text('address')->nullable()
                ->comment('Dirección de residencia');
            $table->string('neighborhood', 200)->nullable()
                ->comment('Barrio de residencia');
            $table->string('village', 200)->nullable()
                ->comment('Vereda (zona rural)');

            // Información de salud
            $table->string('eps_code', 100)->nullable()
                ->comment('Código de la EPS');
            $table->string('eps_name', 300)->nullable()
                ->comment('Nombre de la EPS');

            // Estado y asignación
            $table->enum('status', ['active', 'inactive', 'discharged'])
                ->default('active')
                ->comment('Estado del paciente en el sistema');
            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario profesional asignado');
            $table->timestamp('assigned_at')->nullable()
                ->comment('Fecha de asignación');

            // Auditoría
            $table->foreignId('created_by_id')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que creó el registro');

            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas
            $table->unique(['document_type', 'document_number'], 'unique_patient_document');
            $table->index('full_name');
            $table->index('status');
            $table->index('gender');
            $table->index('birth_date');
            $table->index('assigned_to');
            $table->index('created_at');
            $table->index(['status', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
