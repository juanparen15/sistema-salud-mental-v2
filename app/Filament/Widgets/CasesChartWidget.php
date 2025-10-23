<?php
// ================================
// ARCHIVO: app/Filament/Widgets/CasesChartWidget.php
// GRÁFICO DE CASOS POR MES CON COMPARACIÓN
// ================================

namespace App\Filament\Widgets;

use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CasesChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Casos Registrados por Mes (2025)';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '60s'; // Actualización cada 60 segundos

    /**
     * Filtro para seleccionar el año
     */
    public ?string $filter = '2025';

    protected function getData(): array
    {
        $year = (int) $this->filter;

        // Nombres de los meses en español
        $months = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        // Inicializar arrays para cada tipo de caso
        $mentalDisordersData = [];
        $suicideAttemptsData = [];
        $substanceConsumptionsData = [];

        // Obtener datos por mes
        foreach (range(1, 12) as $month) {
            // Trastornos Mentales
            $mentalDisordersData[] = MentalDisorder::whereYear('admission_date', $year)
                ->whereMonth('admission_date', $month)
                ->count();

            // Intentos de Suicidio
            $suicideAttemptsData[] = SuicideAttempt::whereYear('event_date', $year)
                ->whereMonth('event_date', $month)
                ->count();

            // Consumo de SPA
            $substanceConsumptionsData[] = SubstanceConsumption::whereYear('admission_date', $year)
                ->whereMonth('admission_date', $month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Trastornos Mentales',
                    'data' => $mentalDisordersData,
                    'backgroundColor' => 'rgba(255, 159, 64, 0.2)',
                    'borderColor' => 'rgb(255, 159, 64)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4, // Suavizar las líneas
                ],
                [
                    'label' => 'Intentos de Suicidio',
                    'data' => $suicideAttemptsData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Consumo SPA',
                    'data' => $substanceConsumptionsData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => array_values($months),
        ];
    }

    /**
     * Tipo de gráfico
     */
    protected function getType(): string
    {
        return 'line'; // Gráfico de líneas
    }

    /**
     * Opciones adicionales del gráfico
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => '#ddd',
                    'borderWidth' => 1,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // Sin decimales
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }

    /**
     * Filtros disponibles
     */
    protected function getFilters(): ?array
    {
        $currentYear = now()->year;
        $years = [];

        // Generar opciones de años (últimos 5 años + año actual)
        for ($i = 0; $i <= 5; $i++) {
            $year = $currentYear - $i;
            $years[$year] = (string) $year;
        }

        return $years;
    }

    /**
     * Descripción del widget
     */
    public function getDescription(): ?string
    {
        $year = (int) $this->filter;
        $totalCases = $this->getTotalCases($year);

        return "Total de casos en {$year}: {$totalCases}";
    }

    /**
     * Calcular total de casos del año
     */
    protected function getTotalCases(int $year): int
    {
        $mentalDisorders = MentalDisorder::whereYear('admission_date', $year)->count();
        $suicideAttempts = SuicideAttempt::whereYear('event_date', $year)->count();
        $substanceConsumptions = SubstanceConsumption::whereYear('admission_date', $year)->count();

        return $mentalDisorders + $suicideAttempts + $substanceConsumptions;
    }

    /**
     * Determinar si el widget puede ser visto
     */
    public static function canView(): bool
    {
        return true;
    }
}
