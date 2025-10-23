<?php
// ================================
// ARCHIVO: database/migrations/2024_01_01_000005_create_monthly_followups_table.php
// MIGRACIÓN DE SEGUIMIENTOS MENSUALES (POLIMÓRFICA)
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
        Schema::create('monthly_followups', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica (puede ser trastorno, intento o consumo)
            $table->morphs('followupable', 'followupable_index');
            // Esto crea automáticamente:
            // - followupable_type (string) - Tipo de modelo relacionado
            // - followupable_id (unsignedBigInteger) - ID del registro relacionado
            // - índice compuesto para optimizar consultas

            // Información del seguimiento
            $table->date('followup_date')
                ->comment('Fecha del seguimiento');
            $table->time('followup_time')->nullable()
                ->comment('Hora del seguimiento');
            $table->text('description')
                ->comment('Descripción del seguimiento realizado');

            // Estado del seguimiento
            $table->enum('status', [
                'pending',
                'completed',
                'not_contacted',
                'refused',
                'rescheduled'
            ])->default('pending')
                ->comment('Estado del seguimiento');

            // Detalles del seguimiento
            $table->enum('contact_method', [
                'Teléfono',
                'Visita Domiciliaria',
                'Consulta Presencial',
                'WhatsApp',
                'Correo Electrónico'
            ])->nullable()
                ->comment('Método de contacto utilizado');

            $table->integer('duration_minutes')->nullable()
                ->comment('Duración del seguimiento en minutos');

            $table->json('actions_taken')->nullable()
                ->comment('Acciones realizadas durante el seguimiento');

            $table->date('next_followup')->nullable()
                ->comment('Fecha programada para el próximo seguimiento');

            // Asignación y responsables
            $table->foreignId('performed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que realizó el seguimiento');

            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario asignado al seguimiento');

            // Información temporal para estadísticas
            $table->tinyInteger('month')
                ->comment('Mes del seguimiento (1-12)');
            $table->smallInteger('year')
                ->comment('Año del seguimiento');

            $table->timestamps();
            $table->softDeletes();

            // Índices para optimizar consultas
            // El índice polimórfico ya se crea con morphs()
            $table->index('followup_date');
            $table->index('status');
            $table->index('month');
            $table->index('year');
            $table->index('performed_by');
            $table->index('assigned_to');
            $table->index('next_followup');
            $table->index('created_at');

            // Índices compuestos para consultas complejas
            $table->index(['followup_date', 'status']);
            $table->index(['status', 'assigned_to']);
            $table->index(['year', 'month']);
            $table->index(['year', 'month', 'status']);
            $table->index(['followupable_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_followups');
    }
};
