<?php
// ================================
// ARCHIVO: app/Imports/SuicideAttemptsImport.php
// ================================

namespace App\Imports;

use App\Models\Patient;
use App\Models\SuicideAttempt;
use App\Models\MonthlyFollowup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class SuicideAttemptsImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $importedCount = 0;
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
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en importación de intentos de suicidio: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processRow(Collection $row)
    {
        $documentNumber = $this->cleanString($row['n_documento'] ?? $row['documento']);

        if (empty($documentNumber)) {
            $this->skippedCount++;
            return;
        }

        try {
            // Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $documentNumber);
            if (!$patient) return;

            // Crear intento de suicidio
            $suicideAttempt = $this->createSuicideAttempt($patient, $row);
            if (!$suicideAttempt) return;

            // Procesar seguimientos mensuales
            $this->processMonthlyFollowups($suicideAttempt, $row);
            
            $this->importedCount++;
        } catch (\Exception $e) {
            $this->skippedCount++;
            Log::error('Error procesando intento de suicidio: ' . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, string $documentNumber): ?Patient
    {
        $birthDate = $this->parseDate($row['fecha_de_nacimiento'] ?? null);

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc'] ?? 'CC'),
            'full_name' => $this->cleanString($row['nombres_y_apellidos']),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $birthDate ? $birthDate->format('Y-m-d') : null,
            'phone' => $this->cleanString($row['telefono']),
            'address' => $this->cleanString($row['direccion']),
            'neighborhood' => $this->cleanString($row['barrio']),
            'village' => $this->cleanString($row['vereda']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        return Patient::updateOrCreate(
            ['document_number' => $documentNumber],
            $patientData
        );
    }

    private function createSuicideAttempt(Patient $patient, Collection $row): ?SuicideAttempt
    {
        $eventDate = $this->parseDate($row['fecha_de_ingreso'] ?? null);
        if (!$eventDate) return null;

        // Procesar factores de riesgo
        $riskFactors = $this->parseRiskFactors($row['factores_de_riesgo'] ?? null);

        $attemptData = [
            'patient_id' => $patient->id,
            'event_date' => $eventDate,
            'week_number' => $row['semana'] ?? null,
            'admission_via' => $this->mapAdmissionVia($row['ingreso_por'] ?? 'URGENCIAS'),
            'attempt_number' => $row['n_intentos'] ?? 1,
            'benefit_plan' => $this->cleanString($row['plan_de_beneficios']),
            'trigger_factor' => $this->cleanString($row['desencadenante']),
            'risk_factors' => $riskFactors,
            'mechanism' => $this->cleanString($row['mecanismo']),
            'additional_observation' => $this->cleanString($row['observacion_adicional']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        return SuicideAttempt::create($attemptData);
    }

    private function processMonthlyFollowups(SuicideAttempt $suicideAttempt, Collection $row)
    {
        foreach ($this->monthColumns as $columnName => $dateInfo) {
            $followupValue = $this->cleanString($row[$columnName] ?? null);
            
            if (empty($followupValue)) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $suicideAttempt->id)
                ->where('followupable_type', SuicideAttempt::class)
                ->where('year', $dateInfo['year'])
                ->where('month', $dateInfo['month'])
                ->first();

            if ($existingFollowup) continue;

            MonthlyFollowup::create([
                'followupable_id' => $suicideAttempt->id,
                'followupable_type' => SuicideAttempt::class,
                'followup_date' => Carbon::create($dateInfo['year'], $dateInfo['month'], 15),
                'year' => $dateInfo['year'],
                'month' => $dateInfo['month'],
                'description' => $followupValue,
                'status' => $this->determineFollowupStatus($followupValue),
                'performed_by' => auth()->id() ?? 1,
                'assigned_to' => auth()->id() ?? 1,
            ]);
        }
    }

    private function parseRiskFactors($value): ?array
    {
        if (empty($value)) return null;
        
        $factors = explode(',', $value);
        return array_map('trim', $factors);
    }

    // Métodos de utilidad (similar a MentalDisordersImport)
    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string) $value));
    }

    private function mapDocumentType($value): string
    {
        $docType = strtoupper(trim($value ?? ''));
        $mappings = ['CEDULA' => 'CC', 'TARJETA DE IDENTIDAD' => 'TI'];
        return $mappings[$docType] ?? (in_array($docType, ['CC', 'TI', 'CE', 'PA']) ? $docType : 'CC');
    }

    private function mapGender($value): string
    {
        $gender = strtoupper(trim($value ?? ''));
        return in_array($gender, ['M', 'MASCULINO']) ? 'Masculino' : (in_array($gender, ['F', 'FEMENINO']) ? 'Femenino' : 'Otro');
    }

    private function mapAdmissionVia($value): string
    {
        $via = strtoupper(trim($value ?? ''));
        $validVias = ['URGENCIAS', 'CONSULTA_EXTERNA', 'HOSPITALIZACION', 'REFERENCIA', 'COMUNIDAD'];
        return in_array($via, $validVias) ? $via : 'URGENCIAS';
    }

    private function determineFollowupStatus($value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, ['no contactado', 'sin contacto'])) return 'not_contacted';
        if (in_array($value, ['rechazado', 'rehusado'])) return 'refused';
        if (in_array($value, ['pendiente'])) return 'pending';
        return 'completed';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function batchSize(): int { return 100; }
    public function chunkSize(): int { return 100; }
    public function getImportedCount(): int { return $this->importedCount; }
}
