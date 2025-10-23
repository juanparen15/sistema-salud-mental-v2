<?php

// ================================
// ARCHIVO: app/Console/Commands/ImportMentalHealthData.php
// COMANDO ARTISAN PARA IMPORTAR DATOS DESDE TERMINAL
// ================================

namespace App\Console\Commands;

use App\Imports\MentalHealthImport;
use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use App\Models\MonthlyFollowup;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportMentalHealthData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mental-health:import 
                            {file : Ruta al archivo Excel}
                            {--force : Forzar importación sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar datos de salud mental desde archivo Excel (TRASTORNOS, EVENTO 356, CONSUMO SPA)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $force = $this->option('force');

        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            $this->error("❌ El archivo no existe: {$filePath}");
            return 1;
        }

        // Verificar extensión del archivo
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xlsx', 'xls'])) {
            $this->error("❌ El archivo debe ser .xlsx o .xls");
            return 1;
        }

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('   SISTEMA DE IMPORTACIÓN DE DATOS DE SALUD MENTAL');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->info('📄 Archivo: ' . basename($filePath));
        $this->info('📊 Tamaño: ' . $this->formatBytes(filesize($filePath)));
        $this->newLine();

        // Mostrar estadísticas actuales
        $this->showCurrentStats();
        $this->newLine();

        // Confirmación
        if (!$force && !$this->confirm('¿Desea iniciar la importación?', true)) {
            $this->warn('Importación cancelada');
            return 0;
        }

        $this->info('🚀 Iniciando importación...');
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Crear barra de progreso
            $this->withProgressBar(1, function () use ($filePath) {
                Excel::import(new MentalHealthImport, $filePath);
            });

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine(2);
            $this->info('✅ Importación completada exitosamente');
            $this->info("⏱️  Tiempo transcurrido: {$duration} segundos");
            $this->newLine();

            // Mostrar estadísticas finales
            $this->showFinalStats();

            $this->newLine();
            $this->info('═══════════════════════════════════════════════════════');
            $this->info('✨ Proceso completado con éxito');
            $this->info('═══════════════════════════════════════════════════════');
        } catch (\Exception $e) {
            $this->newLine(2);
            $this->error('═══════════════════════════════════════════════════════');
            $this->error('❌ Error durante la importación:');
            $this->error('═══════════════════════════════════════════════════════');
            $this->error($e->getMessage());
            $this->newLine();

            if ($this->output->isVerbose()) {
                $this->error($e->getTraceAsString());
            } else {
                $this->warn('💡 Usa la opción -v para ver más detalles del error');
            }

            return 1;
        }

        return 0;
    }

    /**
     * Mostrar estadísticas actuales
     */
    protected function showCurrentStats()
    {
        $this->info('📊 Estadísticas actuales en la base de datos:');
        $this->newLine();

        $this->table(
            ['Tabla', 'Registros'],
            [
                ['Pacientes', Patient::count()],
                ['Trastornos Mentales', MentalDisorder::count()],
                ['Intentos de Suicidio', SuicideAttempt::count()],
                ['Consumo SPA', SubstanceConsumption::count()],
                ['Seguimientos', MonthlyFollowup::count()],
            ]
        );
    }

    /**
     * Mostrar estadísticas finales
     */
    protected function showFinalStats()
    {
        $this->info('📊 Estadísticas después de la importación:');
        $this->newLine();

        $this->table(
            ['Tabla', 'Registros', 'Activos'],
            [
                [
                    'Pacientes',
                    Patient::count(),
                    Patient::where('status', 'active')->count()
                ],
                [
                    'Trastornos Mentales',
                    MentalDisorder::count(),
                    MentalDisorder::where('status', 'active')->count()
                ],
                [
                    'Intentos de Suicidio',
                    SuicideAttempt::count(),
                    SuicideAttempt::where('status', 'active')->count()
                ],
                [
                    'Consumo SPA',
                    SubstanceConsumption::count(),
                    SubstanceConsumption::whereIn('status', ['active', 'in_treatment'])->count()
                ],
                [
                    'Seguimientos',
                    MonthlyFollowup::count(),
                    MonthlyFollowup::where('status', 'completed')->count() . ' completados'
                ],
            ]
        );
    }

    /**
     * Formatear bytes a tamaño legible
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
