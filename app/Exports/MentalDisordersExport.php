<?php

namespace App\Exports;

use App\Models\MentalDisorder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class MentalDisordersExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths,
    WithTitle,
    ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Obtener colección de trastornos mentales
     */
    public function collection()
    {
        $query = MentalDisorder::with([
            'patient', 
            'createdBy', 
            'updatedBy',
            'followups'
        ]);

        // Aplicar filtros
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['admission_type'])) {
            $query->where('admission_type', $this->filters['admission_type']);
        }

        if (!empty($this->filters['diagnosis_type'])) {
            $query->where('diagnosis_type', $this->filters['diagnosis_type']);
        }

        if (!empty($this->filters['year'])) {
            $query->whereYear('admission_date', $this->filters['year']);
        }

        if (!empty($this->filters['month'])) {
            $query->whereMonth('admission_date', $this->filters['month']);
        }

        $query->orderBy('admission_date', 'desc');

        return $query->get();
    }

    /**
     * Definir encabezados
     */
    public function headings(): array
    {
        return [
            'ID',
            'Paciente',
            'Tipo Doc',
            'N° Documento',
            'Edad',
            'Género',
            'Teléfono',
            'EPS',
            'Fecha Ingreso',
            'Tipo Ingreso',
            'Ingreso Por',
            'Área Servicio',
            'Código Diagnóstico',
            'Descripción Diagnóstico',
            'Fecha Diagnóstico',
            'Tipo Diagnóstico',
            'Observaciones',
            'Estado',
            'Seguimientos Totales',
            'Seguimientos Completados',
            'Seguimientos Pendientes',
            'Último Seguimiento',
            'Próximo Seguimiento',
            'Registrado Por',
            'Fecha Registro',
            'Última Modificación',
        ];
    }

    /**
     * Mapear datos
     */
    public function map($disorder): array
    {
        $patient = $disorder->patient;
        $followups = $disorder->followups;
        $lastFollowup = $followups->sortByDesc('followup_date')->first();
        $nextFollowup = $followups->where('status', 'pending')
            ->sortBy('next_followup')
            ->first();

        return [
            $disorder->id,
            $patient->full_name,
            $patient->document_type,
            $patient->document_number,
            $patient->age ?? 'N/A',
            $patient->gender,
            $patient->phone ?? 'Sin teléfono',
            $patient->eps_name ?? 'Sin EPS',
            $disorder->admission_date->format('d/m/Y'),
            $disorder->admission_type,
            $disorder->admission_via,
            $disorder->service_area ?? 'N/A',
            $disorder->diagnosis_code,
            $disorder->diagnosis_description,
            $disorder->diagnosis_date->format('d/m/Y'),
            $disorder->diagnosis_type,
            $disorder->additional_observation ?? 'Sin observaciones',
            $this->formatStatus($disorder->status),
            $followups->count(),
            $followups->where('status', 'completed')->count(),
            $followups->where('status', 'pending')->count(),
            $lastFollowup ? $lastFollowup->followup_date->format('d/m/Y') : 'Sin seguimientos',
            $nextFollowup?->next_followup?->format('d/m/Y') ?? 'Sin programar',
            $disorder->createdBy?->name ?? 'Sistema',
            $disorder->created_at->format('d/m/Y H:i'),
            $disorder->updated_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Aplicar estilos
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo del encabezado
        $sheet->getStyle('A1:Z1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'], // Verde
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(25);

        // Estilo de datos
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:Z' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Filas alternadas
        for ($row = 2; $row <= $highestRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':Z' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F5E9'], // Verde muy claro
                    ],
                ]);
            }
        }

        // Centrar columnas específicas
        $centerColumns = ['A', 'C', 'D', 'E', 'F', 'I', 'J', 'K', 'M', 'O', 'P', 'R', 'S', 'T', 'U'];
        foreach ($centerColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . $highestRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        return $sheet;
    }

    /**
     * Anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 30,  // Paciente
            'C' => 10,  // Tipo Doc
            'D' => 15,  // N° Documento
            'E' => 8,   // Edad
            'F' => 12,  // Género
            'G' => 15,  // Teléfono
            'H' => 25,  // EPS
            'I' => 15,  // Fecha Ingreso
            'J' => 15,  // Tipo Ingreso
            'K' => 18,  // Ingreso Por
            'L' => 20,  // Área Servicio
            'M' => 12,  // Código Diagnóstico
            'N' => 40,  // Descripción Diagnóstico
            'O' => 15,  // Fecha Diagnóstico
            'P' => 18,  // Tipo Diagnóstico
            'Q' => 30,  // Observaciones
            'R' => 12,  // Estado
            'S' => 16,  // Seguimientos Totales
            'T' => 20,  // Seguimientos Completados
            'U' => 18,  // Seguimientos Pendientes
            'V' => 18,  // Último Seguimiento
            'W' => 18,  // Próximo Seguimiento
            'X' => 20,  // Registrado Por
            'Y' => 18,  // Fecha Registro
            'Z' => 18,  // Última Modificación
        ];
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Trastornos Mentales';
    }

    /**
     * Formatear estado
     */
    private function formatStatus(string $status): string
    {
        return match($status) {
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'discharged' => 'Dado de Alta',
            default => $status,
        };
    }
}