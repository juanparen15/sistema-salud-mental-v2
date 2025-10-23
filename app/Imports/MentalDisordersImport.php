<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\MentalDisorder;
use App\Models\MonthlyFollowup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class MentalDisordersImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    
    protected $monthColumns = [
        'enero_2025' => ['year' => 2025, 'month' => 1],
        'febrero_2025' => ['year' => 2025, 'month' => 2],
        'marzo_2025' => ['year' => 2025, 'month' => 3],
        'abril_2025' => ['year' => 2025, 'month' => 4],
        'mayo_2025' => ['year' => 2025, 'month' => 5],
        'junio_2025' => ['year' => 2025, 'month' => 6],
        'julio_2025' => ['year' => 2025, 'month' => 7],
        'agosto_2025' => ['year' => 2025, 'month' => 8],
        'septiembre_2025' => ['year' => 2025, 'month' => 9],
        'octubre_2025' => ['year' => 2025, 'month' => 10],
        'noviembre_2025' => ['year' => 2025, 'month' => 11],
        'diciembre_2025' => ['year' => 2025, 'month' => 12],
    ];

    public function collection(Collection $collection)
    {
        DB::beginTransaction();

        try {
            foreach ($collection as $row) {
                $this->processRow($row);
            }

            DB::commit();
            
            Log::info('Importación de trastornos completada', [
                'importados' => $this->importedCount,
                'actualizados' => $this->updatedCount,
                'saltados' => $this->skippedCount
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en importación de trastornos: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processRow(Collection $row)
    {
        // Limpiar y validar datos
        $documentNumber = $this->cleanString($row['n_documento'] ?? $row['documento']);

        if (empty($documentNumber)) {
            $this->skippedCount++;
            $this->errors[] = "Fila sin número de documento saltada";
            return;
        }

        try {
            // 1. CREAR O ACTUALIZAR PACIENTE
            $patient = $this->createOrUpdatePatient($row, $documentNumber);
            
            if (!$patient) {
                $this->skippedCount++;
                return;
            }

            // 2. CREAR O ACTUALIZAR TRASTORNO MENTAL
            $mentalDisorder = $this->createOrUpdateMentalDisorder($patient, $row);
            
            if (!$mentalDisorder) {
                $this->skippedCount++;
                return;
            }

            // 3. PROCESAR SEGUIMIENTOS MENSUALES
            $this->processMonthlyFollowups($mentalDisorder, $row);
            
            $this->importedCount++;
            
        } catch (\Exception $e) {
            Log::error('Error procesando fila: ' . $e->getMessage(), [
                'documento' => $documentNumber,
                'error' => $e->getMessage()
            ]);
            $this->skippedCount++;
            $this->errors[] = "Error en documento {$documentNumber}: " . $e->getMessage();
        }
    }

    private function createOrUpdatePatient(Collection $row, string $documentNumber): ?Patient
    {
        $birthDate = $this->parseDate($row['fecha_de_nacimiento'] ?? null);

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_de_documento'] ?? 'CC'),
            'full_name' => $this->cleanString($row['nombres_y_apellidos'] ?? $row['nombre_completo']),
            'gender' => $this->mapGender($row['sex0'] ?? $row['sexo']),
            'birth_date' => $birthDate ? $birthDate->format('Y-m-d') : null,
            'phone' => $this->cleanString($row['telefono']),
            'address' => $this->cleanString($row['direccion']),
            'village' => $this->cleanString($row['vereda']),
            'eps_code' => $this->cleanString($row['eps_codigo']),
            'eps_name' => $this->cleanString($row['eps_nombre']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        // Buscar o crear paciente
        $patient = Patient::updateOrCreate(
            ['document_number' => $documentNumber],
            $patientData
        );

        if ($patient->wasRecentlyCreated) {
            $patient->update(['assigned_at' => now()]);
        }

        return $patient;
    }

    private function createOrUpdateMentalDisorder(Patient $patient, Collection $row): ?MentalDisorder
    {
        $admissionDate = $this->parseDate($row['fecha_de_ingreso'] ?? null);
        $diagnosisDate = $this->parseDate($row['fecha_diagnostico'] ?? $admissionDate);

        if (!$admissionDate) {
            Log::warning('No se pudo parsear fecha de ingreso', ['paciente' => $patient->id]);
            return null;
        }

        $disorderData = [
            'patient_id' => $patient->id,
            'admission_date' => $admissionDate,
            'admission_type' => $this->mapAdmissionType($row['tipo_de_ingreso'] ?? 'AMBULATORIO'),
            'admission_via' => $this->mapAdmissionVia($row['ingreso_por'] ?? 'CONSULTA_EXTERNA'),
            'service_area' => $this->cleanString($row['area_servicio_de_atencion']),
            'diagnosis_code' => $this->cleanString($row['diag_codigo']),
            'diagnosis_description' => $this->cleanString($row['diagnostico']),
            'diagnosis_date' => $diagnosisDate ?? $admissionDate,
            'diagnosis_type' => $this->mapDiagnosisType($row['tipo_diagnostico'] ?? 'Diagnostico Principal'),
            'additional_observation' => $this->cleanString($row['observacion_adicional']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        // Buscar trastorno existente o crear nuevo
        $mentalDisorder = MentalDisorder::where('patient_id', $patient->id)
            ->where('admission_date', $admissionDate)
            ->where('diagnosis_code', $disorderData['diagnosis_code'])
            ->first();

        if ($mentalDisorder) {
            $mentalDisorder->update($disorderData);
            $this->updatedCount++;
        } else {
            $mentalDisorder = MentalDisorder::create($disorderData);
        }

        return $mentalDisorder;
    }

    private function processMonthlyFollowups(MentalDisorder $mentalDisorder, Collection $row)
    {
        foreach ($this->monthColumns as $columnName => $dateInfo) {
            $followupValue = $this->cleanString($row[$columnName] ?? null);
            
            if (empty($followupValue)) {
                continue; // No hay seguimiento para este mes
            }

            // Verificar si ya existe seguimiento para este mes
            $existingFollowup = MonthlyFollowup::where('followupable_id', $mentalDisorder->id)
                ->where('followupable_type', MentalDisorder::class)
                ->where('year', $dateInfo['year'])
                ->where('month', $dateInfo['month'])
                ->first();

            if ($existingFollowup) {
                continue; // Ya existe seguimiento
            }

            // Crear seguimiento
            $followupData = [
                'followupable_id' => $mentalDisorder->id,
                'followupable_type' => MentalDisorder::class,
                'followup_date' => Carbon::create($dateInfo['year'], $dateInfo['month'], 15),
                'year' => $dateInfo['year'],
                'month' => $dateInfo['month'],
                'description' => $followupValue,
                'status' => $this->determineFollowupStatus($followupValue),
                'performed_by' => auth()->id() ?? 1,
                'assigned_to' => auth()->id() ?? 1,
            ];

            MonthlyFollowup::create($followupData);
        }
    }

    // ==========================================
    // MÉTODOS DE MAPEO Y LIMPIEZA
    // ==========================================

    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string) $value));
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $docType = strtoupper(trim($value));
        
        $mappings = [
            'CEDULA' => 'CC',
            'CEDULA DE CIUDADANIA' => 'CC',
            'TARJETA DE IDENTIDAD' => 'TI',
            'CEDULA EXTRANJERIA' => 'CE',
            'PASAPORTE' => 'PA',
            'REGISTRO CIVIL' => 'RC',
        ];

        return $mappings[$docType] ?? (in_array($docType, ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']) ? $docType : 'CC');
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim($value));

        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE', 'MALE'])) {
            return 'Masculino';
        } elseif (in_array($gender, ['F', 'FEMENINO', 'MUJER', 'FEMALE'])) {
            return 'Femenino';
        }
        return 'Otro';
    }

    private function mapAdmissionType($value): string
    {
        $type = strtoupper(trim($value ?? ''));
        $validTypes = ['AMBULATORIO', 'HOSPITALARIO', 'URGENCIAS'];
        return in_array($type, $validTypes) ? $type : 'AMBULATORIO';
    }

    private function mapAdmissionVia($value): string
    {
        $via = strtoupper(trim($value ?? ''));
        $validVias = ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA'];
        return in_array($via, $validVias) ? $via : 'CONSULTA_EXTERNA';
    }

    private function mapDiagnosisType($value): string
    {
        $type = trim($value ?? '');
        return in_array($type, ['Diagnostico Principal', 'Diagnostico Relacionado']) 
            ? $type 
            : 'Diagnostico Principal';
    }

    private function determineFollowupStatus($value): string
    {
        $value = strtolower(trim($value));
        
        if (in_array($value, ['no contactado', 'no localizado', 'sin contacto'])) {
            return 'not_contacted';
        } elseif (in_array($value, ['rechazado', 'rehusado', 'no acepta'])) {
            return 'refused';
        } elseif (in_array($value, ['pendiente', 'por realizar'])) {
            return 'pending';
        }
        
        return 'completed';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            $formats = [
                'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d',
                'd/m/y', 'm/d/y', 'd.m.Y', 'Y-m-d H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, trim($value));
                } catch (\Exception $e) {
                    continue;
                }
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("No se pudo parsear la fecha: {$value}");
            return null;
        }
    }

    // ==========================================
    // CONFIGURACIÓN
    // ==========================================

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}