<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Usuario que realizó la acción
            $table->string('user_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Evento y modelo auditado
            $table->string('event', 100);
            $table->morphs('auditable');
            
            // Valores antes y después
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            
            // Información de la solicitud
            $table->string('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            // Etiquetas adicionales
            $table->json('tags')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'user_type']);
            $table->index(['event', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};