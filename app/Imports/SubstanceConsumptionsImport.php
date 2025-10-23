<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\SubstanceConsumption;
use App\Models\MonthlyFollowup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class SubstanceConsumptionsImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    protected $importedCount = 0;
    protected $skippedCount = 0;

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
            Log::error('Error en importación de consumo SPA: ' . $e->getMessage());
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
            $patient = $this->createOrUpdatePatient($row, $documentNumber);
            if (!$patient) return;

            $consumption = $this->createSubstanceConsumption($patient, $row);
            if (!$consumption) return;

            $this->processMonthlyFollowups($consumption, $row);

            $this->importedCount++;
        } catch (\Exception $e) {
            $this->skippedCount++;
            Log::error('Error procesando consumo SPA: ' . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, string $documentNumber): ?Patient
    {
        $birthDate = $this->parseDate($row['fecha_de_nacimiento'] ?? null);

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc'] ?? 'CC'),
            'full_name' => $this->cleanString($row['nombre_completo']),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $birthDate ? $birthDate->format('Y-m-d') : null,
            'phone' => $this->cleanString($row['telefono']),
            'eps_name' => $this->cleanString($row['eps_nombre']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        return Patient::updateOrCreate(
            ['document_number' => $documentNumber],
            $patientData
        );
    }

    private function createSubstanceConsumption(Patient $patient, Collection $row): ?SubstanceConsumption
    {
        $admissionDate = $this->parseDate($row['fecha_de_ingres'] ?? null);
        if (!$admissionDate) return null;

        // Extraer sustancias del diagnóstico
        $substances = $this->extractSubstances($row['diagnostico'] ?? '');

        $consumptionData = [
            'patient_id' => $patient->id,
            'admission_date' => $admissionDate,
            'admission_via' => $this->mapAdmissionVia($row['ingreso_por'] ?? 'CONSULTA_EXTERNA'),
            'diagnosis' => $this->cleanString($row['diagnostico']),
            'substances_used' => $substances,
            'consumption_level' => $this->mapConsumptionLevel($row['nivel_consumo'] ?? 'Bajo Riesgo'),
            'additional_observation' => $this->cleanString($row['observacion_adicional']),
            'status' => 'active',
            'created_by_id' => auth()->id() ?? 1,
        ];

        return SubstanceConsumption::create($consumptionData);
    }

    private function processMonthlyFollowups(SubstanceConsumption $consumption, Collection $row)
    {
        foreach ($this->monthColumns as $columnName => $dateInfo) {
            $followupValue = $this->cleanString($row[$columnName] ?? null);

            if (empty($followupValue)) continue;

            $existingFollowup = MonthlyFollowup::where('followupable_id', $consumption->id)
                ->where('followupable_type', SubstanceConsumption::class)
                ->where('year', $dateInfo['year'])
                ->where('month', $dateInfo['month'])
                ->first();

            if ($existingFollowup) continue;

            MonthlyFollowup::create([
                'followupable_id' => $consumption->id,
                'followupable_type' => SubstanceConsumption::class,
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

    private function extractSubstances($diagnosis): array
    {
        $substances = ['Alcohol', 'Marihuana', 'Cocaína', 'Basuco', 'Heroína'];
        $found = [];

        foreach ($substances as $substance) {
            if (stripos($diagnosis, $substance) !== false) {
                $found[] = $substance;
            }
        }

        return !empty($found) ? $found : ['Otra'];
    }

    private function mapConsumptionLevel($value): string
    {
        $levels = ['Alto Riesgo', 'Riesgo Moderado', 'Bajo Riesgo', 'Perjudicial'];
        return in_array($value, $levels) ? $value : 'Bajo Riesgo';
    }

    // Métodos de utilidad
    private function cleanString($value): ?string
    {
        return empty($value) ? null : trim(strip_tags((string) $value));
    }

    private function mapDocumentType($value): string
    {
        $docType = strtoupper(trim($value ?? ''));
        return in_array($docType, ['CC', 'TI', 'CE', 'PA']) ? $docType : 'CC';
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
        return in_array($via, $validVias) ? $via : 'CONSULTA_EXTERNA';
    }

    private function determineFollowupStatus($value): string
    {
        $value = strtolower(trim($value));
        if (in_array($value, ['no contactado'])) return 'not_contacted';
        if (in_array($value, ['rechazado'])) return 'refused';
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
}
