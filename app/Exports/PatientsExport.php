<?php

namespace App\Exports;

use App\Models\Patient;
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

class PatientsExport implements 
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
     * Obtener colección de pacientes con filtros
     */
    public function collection()
    {
        $query = Patient::with([
            'assignedUser', 
            'mentalDisorders', 
            'suicideAttempts', 
            'substanceConsumptions'
        ]);

        // Aplicar filtros si existen
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['gender'])) {
            $query->where('gender', $this->filters['gender']);
        }

        if (!empty($this->filters['assigned_to'])) {
            $query->where('assigned_to', $this->filters['assigned_to']);
        }

        if (!empty($this->filters['document_type'])) {
            $query->where('document_type', $this->filters['document_type']);
        }

        // Ordenar por fecha de creación descendente
        $query->orderBy('created_at', 'desc');

        return $query->get();
    }

    /**
     * Definir encabezados de columnas
     */
    public function headings(): array
    {
        return [
            'ID',
            'Tipo Doc',
            'N° Documento',
            'Nombre Completo',
            'Género',
            'Fecha Nacimiento',
            'Edad',
            'Teléfono',
            'Dirección',
            'Barrio',
            'Vereda',
            'Código EPS',
            'Nombre EPS',
            'Estado',
            'Asignado a',
            'Trastornos Activos',
            'Intentos Suicidio',
            'Consumo SPA',
            'Total Casos',
            'Fecha Registro',
            'Última Actualización',
        ];
    }

    /**
     * Mapear cada fila con los datos del paciente
     */
    public function map($patient): array
    {
        return [
            $patient->id,
            $patient->document_type,
            $patient->document_number,
            $patient->full_name,
            $patient->gender,
            $patient->birth_date ? $patient->birth_date->format('d/m/Y') : '',
            $patient->age ?? 'N/A',
            $patient->phone ?? 'Sin teléfono',
            $patient->address ?? 'Sin dirección',
            $patient->neighborhood ?? '',
            $patient->village ?? '',
            $patient->eps_code ?? '',
            $patient->eps_name ?? 'Sin EPS',
            $this->formatStatus($patient->status),
            $patient->assignedUser?->name ?? 'Sin asignar',
            $patient->mentalDisorders()->where('status', 'active')->count(),
            $patient->suicideAttempts()->where('status', 'active')->count(),
            $patient->substanceConsumptions()->where('status', 'active')->count(),
            $patient->getTotalCasesCount(),
            $patient->created_at->format('d/m/Y H:i'),
            $patient->updated_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Aplicar estilos al Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado (fila 1)
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'], // Azul
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

        // Altura de la fila del encabezado
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Estilo para todas las celdas de datos
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:U' . $highestRow)->applyFromArray([
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

        // Aplicar colores alternados a las filas (zebra striping)
        for ($row = 2; $row <= $highestRow; $row++) {
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':U' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'], // Gris claro
                    ],
                ]);
            }
        }

        // Centrar columnas específicas
        $sheet->getStyle('A2:A' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:E' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G2:G' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('N2:N' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('P2:S' . $highestRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return $sheet;
    }

    /**
     * Definir anchos de columnas
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 10,  // Tipo Doc
            'C' => 15,  // N° Documento
            'D' => 30,  // Nombre Completo
            'E' => 12,  // Género
            'F' => 15,  // Fecha Nacimiento
            'G' => 8,   // Edad
            'H' => 15,  // Teléfono
            'I' => 35,  // Dirección
            'J' => 20,  // Barrio
            'K' => 20,  // Vereda
            'L' => 12,  // Código EPS
            'M' => 25,  // Nombre EPS
            'N' => 12,  // Estado
            'O' => 20,  // Asignado a
            'P' => 16,  // Trastornos Activos
            'Q' => 16,  // Intentos Suicidio
            'R' => 14,  // Consumo SPA
            'S' => 12,  // Total Casos
            'T' => 18,  // Fecha Registro
            'U' => 18,  // Última Actualización
        ];
    }

    /**
     * Título de la hoja
     */
    public function title(): string
    {
        return 'Pacientes';
    }

    /**
     * Formatear estado para visualización
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