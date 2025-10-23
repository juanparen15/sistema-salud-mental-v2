<?php

namespace App\Filament\Resources\PatientResource\Pages;

use App\Filament\Resources\PatientResource;
use App\Imports\MentalHealthImport;
use App\Imports\MentalHealthSystemImport;
use App\Imports\PatientsImport;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Illuminate\Support\HtmlString;

class ListPatients extends ListRecords
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('importMentalHealth')
                ->label('Importar Sistema Salud Mental')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Section::make('Archivo del Sistema de Salud Mental')
                        ->description('Sube tu archivo "SISTEMA DE INFORMACIÓN SALUD MENTAL 2025.xlsx" con las hojas requeridas')
                        ->schema([
                            Radio::make('import_type')
                                ->label('Tipo de Importación')
                                ->options([
                                    'mental_health_system' => 'Sistema Salud Mental Completo (3 hojas especializadas)',
                                    'generic' => 'Archivo Excel genérico de pacientes'
                                ])
                                ->default('mental_health_system')
                                ->reactive()
                                ->columnSpanFull(),

                            FileUpload::make('file')
                                ->label('Archivo Excel')
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/vnd.ms-excel'
                                ])
                                ->maxSize(20480) // 20MB para archivos grandes del sistema
                                ->required()
                                ->helperText(function (callable $get) {
                                    return $get('import_type') === 'mental_health_system'
                                        ? 'Archivo con hojas: TRASTORNOS 2025, EVENTO 356 2025, CONSUMO SPA 2025'
                                        : 'Archivo Excel estándar con datos de pacientes';
                                })
                                ->columnSpanFull(),
                        ]),

                    Section::make('Información del Procesamiento')
                        ->description('Detalles de lo que se procesará automáticamente:')
                        ->schema([
                            Placeholder::make('processing_info')
                                ->label('')
                                ->content(function (callable $get) {
                                    if ($get('import_type') === 'mental_health_system') {
                                        return new HtmlString('
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                                        <h4 class="font-semibold text-blue-800">Trastornos Mentales</h4>
                                                    </div>
                                                    <ul class="text-sm text-blue-700 space-y-1">
                                                        <li>• Pacientes y diagnósticos CIE-10</li>
                                                        <li>• Casos de trastornos mentales</li>
                                                        <li>• Seguimientos mensuales 2025</li>
                                                    </ul>
                                                </div>
                                                
                                                <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                                        <h4 class="font-semibold text-red-800">Intentos Suicidio</h4>
                                                    </div>
                                                    <ul class="text-sm text-red-700 space-y-1">
                                                        <li>• Casos de evento 356</li>
                                                        <li>• Factores de riesgo y mecanismos</li>
                                                        <li>• Seguimientos especializados</li>
                                                    </ul>
                                                </div>
                                                
                                                <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
                                                    <div class="flex items-center space-x-2 mb-2">
                                                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                                                        <h4 class="font-semibold text-orange-800">Consumo SPA</h4>
                                                    </div>
                                                    <ul class="text-sm text-orange-700 space-y-1">
                                                        <li>• Casos de consumo sustancias</li>
                                                        <li>• Niveles y diagnósticos</li>
                                                        <li>• Seguimientos de SPA</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4 p-3 bg-green-50 rounded border-l-4 border-green-400">
                                                <p class="text-sm text-green-700">
                                                    <strong>Automatización:</strong> Se procesarán las 3 hojas simultaneamente, 
                                                    creando pacientes únicos y casos específicos con sus seguimientos mensuales.
                                                </p>
                                            </div>
                                        ');
                                    } else {
                                        return new HtmlString('
                                            <div class="p-4 bg-gray-50 rounded-lg">
                                                <p class="text-sm text-gray-600">
                                                    Se procesará como archivo estándar de pacientes, 
                                                    detectando automáticamente las columnas disponibles.
                                                </p>
                                            </div>
                                        ');
                                    }
                                })
                                ->columnSpanFull(),
                        ])
                        ->visible(fn(callable $get) => !empty($get('import_type'))),
                ])
                ->action(function (array $data) {
                    try {
                        // Notificación inicial
                        Notification::make()
                            ->title('Iniciando Procesamiento...')
                            ->body('La importación ha comenzado. Este proceso puede tomar varios minutos dependiendo del tamaño del archivo.')
                            ->info()
                            ->persistent()
                            ->send();

                        DB::beginTransaction();

                        if ($data['import_type'] === 'mental_health_system') {
                            // Procesamiento especializado del sistema de salud mental
                            $import = new MentalHealthImport();
                            Excel::import($import, $data['file'], 'public');

                            $successMessage = $this->buildMentalHealthSuccessMessage($import);
                            $errors = $import->getErrors();
                        } else {
                            // Procesamiento genérico
                            $import = new PatientsImport();
                            Excel::import($import, $data['file'], 'public');

                            $successMessage = $this->buildGenericSuccessMessage($import);
                            $errors = method_exists($import, 'getErrors') ? $import->getErrors() : [];
                        }

                        // Limpiar archivo temporal
                        if (Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        }

                        DB::commit();

                        // Notificación de éxito
                        Notification::make()
                            ->title('Importación Completada Exitosamente')
                            ->body($successMessage)
                            ->success()
                            ->duration(15000)
                            ->send();

                        // Mostrar advertencias si existen
                        if (!empty($errors)) {
                            $this->showImportWarnings($errors);
                        }
                    } catch (Exception $e) {
                        DB::rollBack();

                        // Log del error completo
                        Log::error('Error en importación de salud mental', [
                            'message' => $e->getMessage(),
                            'file' => $data['file'] ?? 'unknown',
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        // Limpiar archivo en caso de error
                        if (isset($data['file']) && Storage::disk('public')->exists($data['file'])) {
                            Storage::disk('public')->delete($data['file']);
                        }

                        Notification::make()
                            ->title('Error en la Importación')
                            ->body($this->buildErrorMessage($e))
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                })
                ->modalWidth('4xl')
                ->modalHeading('Importar Sistema de Salud Mental')
                ->slideOver(),

            Actions\Action::make('viewStats')
                ->label('Estadísticas del Sistema')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->action(function () {
                    $this->showSystemStatistics();
                }),

            Actions\Action::make('exportTemplate')
                ->label('Guía de Importación')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->action(function () {
                    Notification::make()
                        ->title('Estructura de Archivo Requerida')
                        ->body(new HtmlString('
                            <div class="space-y-3 text-sm">
                                <p><strong>El archivo debe contener estas hojas:</strong></p>
                                <ul class="list-disc pl-4 space-y-1">
                                    <li><strong>TRASTORNOS 2025:</strong> Casos de trastornos mentales</li>
                                    <li><strong>EVENTO 356 2025:</strong> Intentos de suicidio</li>
                                    <li><strong>CONSUMO SPA 2025:</strong> Consumo de sustancias</li>
                                </ul>
                                <p class="mt-3 p-2 bg-blue-50 rounded text-blue-700">
                                    <strong>Tip:</strong> Cada hoja debe tener columnas de meses (enero_2025, febrero_2025, etc.) 
                                    para generar seguimientos automáticos.
                                </p>
                            </div>
                        '))
                        ->info()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    /**
     * Construir mensaje de éxito para importación de salud mental
     */
    private function buildMentalHealthSuccessMessage(MentalHealthImport $import): string
    {
        $newPatients = $import->getImportedCount();
        $updatedPatients = $import->getUpdatedCount();
        $totalFollowups = $import->getFollowupsCreated();
        $totalCases = $import->getCasesCreated();
        $skipped = $import->getSkippedCount();

        $message = "<div class='space-y-2'>";
        $message .= "<div class='font-semibold mb-3'>Resumen de Procesamiento:</div>";

        // Pacientes
        $message .= "<div class='grid grid-cols-2 gap-2 text-sm'>";
        $message .= "<div><span class='font-medium'>Pacientes nuevos:</span> <strong class='text-green-600'>{$newPatients}</strong></div>";
        $message .= "<div><span class='font-medium'>Pacientes actualizados:</span> <strong class='text-blue-600'>{$updatedPatients}</strong></div>";
        $message .= "</div>";

        // Casos y seguimientos
        $message .= "<div class='grid grid-cols-2 gap-2 text-sm mt-2'>";
        $message .= "<div><span class='font-medium'>Casos creados:</span> <strong class='text-purple-600'>{$totalCases}</strong></div>";
        $message .= "<div><span class='font-medium'>Seguimientos:</span> <strong class='text-orange-600'>{$totalFollowups}</strong></div>";
        $message .= "</div>";

        if ($skipped > 0) {
            $message .= "<div class='text-xs text-gray-600 mt-2'>Registros omitidos: {$skipped}</div>";
        }

        $message .= "<div class='mt-3 p-2 bg-green-100 rounded text-xs text-green-800'>";
        $message .= "Las 3 hojas del sistema fueron procesadas correctamente";
        $message .= "</div>";
        $message .= "</div>";

        return $message;
    }

    /**
     * Construir mensaje de éxito para importación genérica
     */
    private function buildGenericSuccessMessage($import): string
    {
        $imported = method_exists($import, 'getImportedCount') ? $import->getImportedCount() : 0;
        $updated = method_exists($import, 'getUpdatedCount') ? $import->getUpdatedCount() : 0;
        $skipped = method_exists($import, 'getSkippedCount') ? $import->getSkippedCount() : 0;

        $message = "<div class='space-y-2'>";
        $message .= "<div><strong>{$imported}</strong> pacientes nuevos creados</div>";
        $message .= "<div><strong>{$updated}</strong> pacientes actualizados</div>";

        if ($skipped > 0) {
            $message .= "<div><strong>{$skipped}</strong> registros omitidos</div>";
        }

        $message .= "</div>";

        return $message;
    }

    /**
     * Mostrar advertencias de importación
     */
    private function showImportWarnings(array $errors): void
    {
        $errorCount = count($errors);
        $displayErrors = array_slice($errors, 0, 8);

        $errorMessage = "<div class='space-y-1 text-sm'>";
        $errorMessage .= "<div class='font-medium'>Se encontraron {$errorCount} advertencias:</div>";

        foreach ($displayErrors as $error) {
            $errorMessage .= "<div class='text-xs'>• " . htmlspecialchars($error) . "</div>";
        }

        if ($errorCount > 8) {
            $errorMessage .= "<div class='text-xs text-gray-600 mt-2'>... y " . ($errorCount - 8) . " advertencias más</div>";
        }

        $errorMessage .= "</div>";

        Notification::make()
            ->title('Advertencias de Procesamiento')
            ->body($errorMessage)
            ->warning()
            ->duration(25000)
            ->send();
    }

    /**
     * Construir mensaje de error
     */
    private function buildErrorMessage(Exception $e): string
    {
        $message = "<div class='space-y-2'>";
        $message .= "<div class='font-medium text-red-800'>Detalles del Error:</div>";
        $message .= "<div class='text-sm'>" . htmlspecialchars($e->getMessage()) . "</div>";

        if (method_exists($e, 'getFile') && $e->getFile()) {
            $message .= "<div class='text-xs text-gray-600 mt-2'>Archivo: " . basename($e->getFile()) . " línea " . $e->getLine() . "</div>";
        }

        $message .= "<div class='mt-3 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700'>";
        $message .= "<strong>Sugerencias:</strong><br>";
        $message .= "• Verifica que el archivo tenga las hojas correctas<br>";
        $message .= "• Revisa que las columnas tengan los nombres esperados<br>";
        $message .= "• Asegúrate de que el archivo no esté corrupto";
        $message .= "</div>";
        $message .= "</div>";

        return $message;
    }

    /**
     * Mostrar estadísticas del sistema
     */
    private function showSystemStatistics(): void
    {
        try {
            $stats = [
                'total_patients' => \App\Models\Patient::count(),
                'mental_disorders' => \App\Models\MentalDisorder::count(),
                'suicide_attempts' => \App\Models\SuicideAttempt::count(),
                'substance_consumptions' => \App\Models\SubstanceConsumption::count(),
                'total_followups' => \App\Models\MonthlyFollowup::count(),
                'followups_2025' => \App\Models\MonthlyFollowup::where('year', 2025)->count(),
                'recent_followups' => \App\Models\MonthlyFollowup::where('followup_date', '>=', now()->subDays(30))->count(),
                'pending_followups' => \App\Models\MonthlyFollowup::where('status', 'pending')->count(),
            ];

            $message = "<div class='grid grid-cols-2 gap-3 text-sm'>";
            $message .= "<div><strong>Pacientes Total:</strong> <span class='text-blue-600'>{$stats['total_patients']}</span></div>";
            $message .= "<div><strong>Trastornos Mentales:</strong> <span class='text-blue-600'>{$stats['mental_disorders']}</span></div>";
            $message .= "<div><strong>Intentos Suicidio:</strong> <span class='text-red-600'>{$stats['suicide_attempts']}</span></div>";
            $message .= "<div><strong>Casos SPA:</strong> <span class='text-orange-600'>{$stats['substance_consumptions']}</span></div>";
            $message .= "<div><strong>Seguimientos Total:</strong> <span class='text-purple-600'>{$stats['total_followups']}</span></div>";
            $message .= "<div><strong>Seguimientos 2025:</strong> <span class='text-purple-600'>{$stats['followups_2025']}</span></div>";
            $message .= "<div><strong>Recientes (30d):</strong> <span class='text-green-600'>{$stats['recent_followups']}</span></div>";
            $message .= "<div><strong>Pendientes:</strong> <span class='text-yellow-600'>{$stats['pending_followups']}</span></div>";
            $message .= "</div>";

            Notification::make()
                ->title('Estadísticas del Sistema de Salud Mental')
                ->body($message)
                ->info()
                ->duration(12000)
                ->send();
        } catch (Exception $e) {
            Notification::make()
                ->title('Error al obtener estadísticas')
                ->body('No se pudieron cargar las estadísticas: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aquí puedes agregar widgets de estadísticas si los creas
        ];
    }
}
