<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Exception;

class MentalHealthImport implements WithMultipleSheets
{
    protected $importedCount = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];
    protected $followupsCreated = 0;
    protected $casesCreated = 0;

    public function sheets(): array
    {
        return [
            'TRASTORNOS 2025' => new TrastornosSheet($this),
            'EVENTO 356 2025' => new Evento356Sheet($this),
            'CONSUMO SPA 2025' => new ConsumoSpaSheet($this),
        ];
    }

    // Métodos para actualizar contadores
    public function incrementImported()
    {
        $this->importedCount++;
    }
    public function incrementUpdated()
    {
        $this->updatedCount++;
    }
    public function incrementSkipped()
    {
        $this->skippedCount++;
    }
    public function incrementFollowups()
    {
        $this->followupsCreated++;
    }
    public function incrementCases()
    {
        $this->casesCreated++;
    }
    public function addError($error)
    {
        $this->errors[] = $error;
    }

    // Getters para estadísticas
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
    public function getFollowupsCreated(): int
    {
        return $this->followupsCreated;
    }
    public function getCasesCreated(): int
    {
        return $this->casesCreated;
    }
    public function getErrors(): array
    {
        return $this->errors;
    }
}

// ==================== HOJA TRASTORNOS COMPLETA ====================
class TrastornosSheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja TRASTORNOS 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de trastorno mental
            $mentalDisorder = $this->createMentalDisorderCase($patient, $row, $rowNumber);
            if (!$mentalDisorder) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($mentalDisorder, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: " . $e->getMessage());
            Log::error("Error en TRASTORNOS fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_de_documento']),
            'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
            'gender' => $this->mapGender($row['sex0']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono']),
            'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
            'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
            'eps_code' => $this->truncateString($this->cleanString($row['eps_codigo']), 255),
            'eps_name' => $this->truncateString($this->cleanString($row['eps_nombre']), 255),
            'status' => 'active',
        ];

        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }

    private function createMentalDisorderCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = MentalDisorder::where('patient_id', $patient->id)->first();

            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $this->parseDate($row['fecha_de_ingreso']) ?? now(),
                'admission_type' => $this->cleanString($row['tipo_de_ingreso']),
                'admission_via' => $this->cleanString($row['ingreso_por']),
                'service_area' => $this->cleanString($row['area_servicio_de_atencion']),
                'diagnosis_code' => $this->cleanString($row['diag_codigo']),
                'diagnosis_description' => $this->cleanString($row['diagnostico']),
                'diagnosis_date' => $this->parseDate($row['fecha_diagnostico']),
                'diagnosis_type' => $this->cleanString($row['tipo_diagnostico']),
                'additional_observation' => $this->cleanString($row['observacion_adicional']),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, fn($value) => $value !== null);

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $mentalDisorder = MentalDisorder::create($caseData);
                $this->parent->incrementCases();
                return $mentalDisorder;
            }
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups($mentalDisorder, Collection $row, int $rowNumber)
    // {
    //     $months = [
    //         'enero_2025' => 1, 'febrero_2025' => 2, 'marzo_2025' => 3, 'abril_2025' => 4,
    //         'mayo_2025' => 5, 'junio_2025' => 6, 'julio_2025' => 7, 'agosto_2025' => 8,
    //         'septiembre_2025' => 9, 'octubre_2025' => 10, 'noviembre_2025' => 11, 'diciembre_2025' => 12
    //     ];

    //     foreach ($months as $columnName => $monthNumber) {
    //         $followupData = $this->cleanString($row[$columnName]);

    //         if (empty($followupData) || strlen($followupData) < 2) continue;

    //         // Verificar si ya existe seguimiento para este mes
    //         $existingFollowup = MonthlyFollowup::where('followupable_id', $mentalDisorder->id)
    //             ->where('followupable_type', Patient::class)
    //             ->where('year', 2025)
    //             ->where('month', $monthNumber)
    //             ->first();

    //         if ($existingFollowup) continue;

    //         try {
    //             $description = "Seguimiento TRASTORNO MENTAL - " . substr($followupData, 0, 500);

    //             if (!empty($mentalDisorder->diagnosis_description)) {
    //                 $description .= " | Dx: " . substr($mentalDisorder->diagnosis_description, 0, 100);
    //             }

    //             MonthlyFollowup::create([
    //                 'followupable_id' => $mentalDisorder->id,
    //                 'followupable_type' => Patient::class,
    //                 'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
    //                 'year' => 2025,
    //                 'month' => $monthNumber,
    //                 'description' => substr($description, 0, 1000),
    //                 'status' => 'completed',
    //                 'actions_taken' => json_encode(['Seguimiento trastorno mental']),
    //                 'performed_by' => auth()->id() ?? 1,
    //             ]);

    //             $this->parent->incrementFollowups();

    //         } catch (\Exception $e) {
    //             $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error seguimiento mes {$monthNumber} - " . $e->getMessage());
    //         }
    //     }
    // }
    {
        $months = [
            'enero_2025' => 1,
            'febrero_2025' => 2,
            'marzo_2025' => 3,
            'abril_2025' => 4,
            'mayo_2025' => 5,
            'junio_2025' => 6,
            'julio_2025' => 7,
            'agosto_2025' => 8,
            'septiembre_2025' => 9,
            'octubre_2025' => 10,
            'noviembre_2025' => 11,
            'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);

            if (empty($followupData) || strlen($followupData) < 2) continue;

            // ✅ CORRECCIÓN: Verificar seguimiento para ESTE TRASTORNO específico
            $existingFollowup = MonthlyFollowup::where('followupable_id', $mentalDisorder->id)
                ->where('followupable_type', MentalDisorder::class) // ✅ CAMBIO CLAVE
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "TRASTORNO MENTAL - " . substr($followupData, 0, 400);

                if (!empty($mentalDisorder->diagnosis_description)) {
                    $description .= " | Dx: " . substr($mentalDisorder->diagnosis_description, 0, 100);
                }
                if (!empty($mentalDisorder->diagnosis_code)) {
                    $description .= " | CIE: " . $mentalDisorder->diagnosis_code;
                }

                MonthlyFollowup::create([
                    'followupable_id' => $mentalDisorder->id,
                    'followupable_type' => MentalDisorder::class, // ✅ CAMBIO CLAVE
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => ['Seguimiento trastorno mental'], // Array directo
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error seguimiento mes {$monthNumber} - " . $e->getMessage());
            }
        }
    }

    // Métodos helper
    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string)$value)) ?: null;
    }

    private function cleanPhone($phone): ?string
    {
        if (empty($phone)) return null;
        $phones = preg_split('/[-,\s\/]/', (string)$phone);
        $firstPhone = trim($phones[0]);
        $cleaned = preg_replace('/\D/', '', $firstPhone);
        return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
    }

    private function truncateString($string, $maxLength): ?string
    {
        if (empty($string)) return null;
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
    }

    private function isValidDocument($document): bool
    {
        $clean = preg_replace('/\D/', '', (string)$document);
        return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim((string)$value));
        return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']) ? $type : 'CC';
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim((string)$value));
        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
        return 'Otro';
    }

    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value)
                ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value)
                : Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ==================== HOJA EVENTO 356 COMPLETA ====================
class Evento356Sheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja EVENTO 356 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            $documentNumber = $this->cleanString($row['n_documento']);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de intento de suicidio
            $suicideAttempt = $this->createSuicideAttemptCase($patient, $row, $rowNumber);
            if (!$suicideAttempt) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($suicideAttempt, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->cleanString($row['n_documento']);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc']),
            'full_name' => $this->truncateString($this->cleanString($row['nombres_y_apellidos']), 255),
            'gender' => $this->mapGender($row['sexo']),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono']),
            'address' => $this->truncateString($this->cleanString($row['direccion']), 255),
            'neighborhood' => $this->truncateString($this->cleanString($row['barrio']), 255),
            'village' => $this->truncateString($this->cleanString($row['vereda']), 255),
            'status' => 'active',
        ];

        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }

    private function createSuicideAttemptCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = SuicideAttempt::where('patient_id', $patient->id)->first();

            $riskFactorsArray = [];
            $riskFactorsText = $this->cleanString($row['factores_de_riesgo']);
            if (!empty($riskFactorsText)) {
                $riskFactorsArray = array_map('trim', explode(',', $riskFactorsText));
            }

            $caseData = [
                'patient_id' => $patient->id,
                'event_date' => $this->parseDate($row['fecha_de_ingreso']) ?? now(),
                'week_number' => $this->cleanString($row['semana']),
                'admission_via' => $this->cleanString($row['ingreso_por']),
                'attempt_number' => (int)($this->cleanString($row['n_intentos']) ?? 1),
                'benefit_plan' => $this->cleanString($row['plan_de_beneficios']),
                'trigger_factor' => $this->cleanString($row['desencadenante']) ?? 'No especificado',
                'risk_factors' => $riskFactorsArray ?: null,
                'mechanism' => $this->cleanString($row['mecanismo']) ?? 'No especificado',
                'additional_observation' => $this->cleanString($row['observacion_adicional']),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, function ($value) {
                return $value !== null && $value !== '' && $value !== [];
            });

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $suicideAttempt = SuicideAttempt::create($caseData);
                $this->parent->incrementCases();
                return $suicideAttempt;
            }
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    private function processMonthlyFollowups($suicideAttempt, Collection $row, int $rowNumber)
    {
        $months = [
            'enero_2025' => 1,
            'febrero_2025' => 2,
            'marzo_2025' => 3,
            'abril_2025' => 4,
            'mayo_2025' => 5,
            'junio_2025' => 6,
            'julio_2025' => 7,
            'agosto_2025' => 8,
            'septiembre_2025' => 9,
            'octubre_2025' => 10,
            'noviembre_2025' => 11,
            'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);

            if (empty($followupData) || strlen($followupData) < 2) continue;

            // ✅ CORRECCIÓN: Verificar seguimiento para ESTE INTENTO SUICIDIO específico
            $existingFollowup = MonthlyFollowup::where('followupable_id', $suicideAttempt->id)
                ->where('followupable_type', SuicideAttempt::class) // ✅ CAMBIO CLAVE
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "INTENTO SUICIDIO - " . substr($followupData, 0, 400);

                $additionalInfo = [];
                if (!empty($suicideAttempt->attempt_number)) {
                    $additionalInfo[] = "Intento #{$suicideAttempt->attempt_number}";
                }
                if (!empty($suicideAttempt->mechanism)) {
                    $additionalInfo[] = "Mecanismo: " . substr($suicideAttempt->mechanism, 0, 50);
                }
                if (!empty($suicideAttempt->trigger_factor)) {
                    $additionalInfo[] = "Trigger: " . substr($suicideAttempt->trigger_factor, 0, 50);
                }

                if (!empty($additionalInfo)) {
                    $description .= " | " . implode(" | ", $additionalInfo);
                }

                MonthlyFollowup::create([
                    'followupable_id' => $suicideAttempt->id,
                    'followupable_type' => SuicideAttempt::class, // ✅ CAMBIO CLAVE
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => ['Seguimiento intento suicidio'], // Array directo
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error seguimiento - " . $e->getMessage());
            }
        }
    }


    // Métodos helper (iguales que TrastornosSheet)
    private function cleanString($value): ?string
    {
        return empty($value) ? null : (trim(strip_tags((string)$value)) ?: null);
    }
    private function cleanPhone($phone): ?string
    {
        if (empty($phone)) return null;
        $phones = preg_split('/[-,\s\/]/', (string)$phone);
        $firstPhone = trim($phones[0]);
        $cleaned = preg_replace('/\D/', '', $firstPhone);
        return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
    }
    private function truncateString($string, $maxLength): ?string
    {
        return empty($string) ? null : (strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string);
    }
    private function isValidDocument($document): bool
    {
        $clean = preg_replace('/\D/', '', (string)$document);
        return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    }
    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim((string)$value));
        return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
    }
    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim((string)$value));
        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
        return 'Otro';
    }
    private function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ==================== HOJA CONSUMO SPA COMPLETA ====================
class ConsumoSpaSheet implements ToCollection, WithHeadingRow
{
    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }

    public function collection(Collection $collection)
    {
        Log::info("Procesando hoja CONSUMO SPA 2025 - {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $this->processRow($row, $index + 2);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            // ✅ Detectar automáticamente el nombre de la columna de documento
            $documentNumber = $this->findDocumentNumber($row);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                return;
            }

            // 1. Crear o actualizar paciente
            $patient = $this->createOrUpdatePatient($row, $rowNumber);
            if (!$patient) return;

            // 2. Crear caso de consumo de sustancias
            $substanceConsumption = $this->createSubstanceConsumptionCase($patient, $row, $rowNumber);
            if (!$substanceConsumption) return;

            // 3. Crear seguimientos mensuales asociados al caso
            $this->processMonthlyFollowups($substanceConsumption, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
        }
    }

    /**
     * Buscar el número de documento en diferentes posibles columnas
     */
    private function findDocumentNumber(Collection $row): ?string
    {
        $possibleColumns = [
            'n_documento',
            'numero_documento',
            'documento',
            'cedula',
            'identificacion'
        ];

        foreach ($possibleColumns as $column) {
            $value = $this->cleanString($row[$column] ?? null);
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Buscar el nombre completo en diferentes posibles columnas
     */
    private function findFullName(Collection $row): ?string
    {
        $possibleColumns = [
            'nombre_completo',
            'nombres_y_apellidos',
            'nombres_apellidos',
            'nombre',
            'paciente',
            'full_name'
        ];

        foreach ($possibleColumns as $column) {
            $value = $this->cleanString($row[$column] ?? null);
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Buscar la EPS en diferentes posibles columnas
     */
    private function findEPS(Collection $row): ?string
    {
        $possibleColumns = [
            'eps',
            'eps_nombre',
            'nombre_eps',
            'entidad',
            'aseguradora'
        ];

        foreach ($possibleColumns as $column) {
            $value = $this->cleanString($row[$column] ?? null);
            if (!empty($value)) {
                return $value;
            }
        }

        return null;
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber)
    {
        $documentNumber = $this->findDocumentNumber($row);
        $patient = Patient::where('document_number', $documentNumber)->first();

        $patientData = [
            'document_number' => $documentNumber,
            'document_type' => $this->mapDocumentType($row['tipo_doc'] ?? $row['tipo_documento'] ?? null),
            'full_name' => $this->truncateString($this->findFullName($row), 255),
            'gender' => $this->mapGender($row['sexo'] ?? $row['genero'] ?? null),
            'birth_date' => $this->parseDate($row['fecha_de_nacimiento'] ?? $row['fecha_nacimiento'] ?? null)?->format('Y-m-d'),
            'phone' => $this->cleanPhone($row['telefono'] ?? $row['celular'] ?? null),
            'eps_name' => $this->truncateString($this->findEPS($row), 255),
            'status' => 'active',
        ];

        // Filtrar valores null
        $patientData = array_filter($patientData, fn($value) => $value !== null);

        try {
            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }
            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
            return null;
        }
    }
    
    private function mapConsumptionLevel($value): string
    {
        if (empty($value)) {
            return 'Bajo Riesgo'; // Valor por defecto
        }

        $clean = strtoupper(trim((string)$value));

        return match ($clean) {
            'ALTO RIESGO', 'ALTO' => 'Alto Riesgo',
            'RIESGO MODERADO', 'MODERADO' => 'Riesgo Moderado',
            'BAJO RIESGO', 'BAJO' => 'Bajo Riesgo',
            'PERJUDICIAL' => 'Perjudicial',
            default => 'Bajo Riesgo',
        };
    }


    private function createSubstanceConsumptionCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            // Verificar si ya existe un caso para este paciente
            $existingCase = SubstanceConsumption::where('patient_id', $patient->id)->first();

            // ✅ Buscar sustancias en diferentes posibles columnas
            $substancesArray = $this->findSubstances($row);

            // ✅ Buscar fecha de ingreso en diferentes formatos
            $admissionDate = $this->findAdmissionDate($row);

            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $admissionDate ?? now(),
                'admission_via' => $this->cleanString($row['ingreso_por'] ?? $row['via_ingreso'] ?? null) ?? 'No especificado',
                'diagnosis' => $this->cleanString($row['diagnostico'] ?? $row['dx'] ?? null) ?? 'Sin diagnóstico específico',
                'substances_used' => $substancesArray,
                // 'consumption_level' => $this->cleanString($row['nivel_consumo'] ?? $row['nivel'] ?? $row['grado_consumo'] ?? null) ?? 'No especificado',
                'consumption_level' => $this->mapConsumptionLevel(
                    $row['nivel_consumo'] ?? $row['nivel'] ?? $row['grado_consumo'] ?? null
                ),
                'additional_observation' => $this->cleanString($row['observacion_adicional'] ?? $row['observaciones'] ?? null),
                'status' => 'active',
                'created_by' => auth()->id() ?? 1,
            ];

            // Filtrar valores nulos para campos opcionales
            $caseData = array_filter($caseData, function ($value, $key) {
                if ($key === 'additional_observation') {
                    return true; // Permitir null
                }
                return $value !== null && $value !== '' && $value !== [];
            }, ARRAY_FILTER_USE_BOTH);

            if ($existingCase) {
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $substanceConsumption = SubstanceConsumption::create($caseData);
                $this->parent->incrementCases();
                return $substanceConsumption;
            }
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar sustancias utilizadas en diferentes columnas posibles
     */
    private function findSubstances(Collection $row): array
    {
        $possibleColumns = [
            'sustancias',
            'sustancia',
            'drogas',
            'spa',
            'sustancias_usadas',
            'tipo_sustancia'
        ];

        foreach ($possibleColumns as $column) {
            $substancesText = $this->cleanString($row[$column] ?? null);
            if (!empty($substancesText)) {
                $substancesArray = array_map('trim', explode(',', $substancesText));
                $substancesArray = array_filter($substancesArray);
                if (!empty($substancesArray)) {
                    return $substancesArray;
                }
            }
        }

        // Si no se encuentran sustancias específicas, usar valor por defecto
        return ['No especificada'];
    }

    /**
     * Buscar fecha de ingreso en diferentes formatos
     */
    private function findAdmissionDate(Collection $row): ?\Carbon\Carbon
    {
        $possibleColumns = [
            'fecha_de_ingreso',
            'fecha_ingreso',
            'fecha_de_ingres', // Parece que en el código anterior había esta variante
            'fecha_registro',
            'fecha'
        ];

        foreach ($possibleColumns as $column) {
            $date = $this->parseDate($row[$column] ?? null);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    private function processMonthlyFollowups($substanceConsumption, Collection $row, int $rowNumber)
    {
        $months = [
            'enero_2025' => 1,
            'febrero_2025' => 2,
            'marzo_2025' => 3,
            'abril_2025' => 4,
            'mayo_2025' => 5,
            'junio_2025' => 6,
            'julio_2025' => 7,
            'agosto_2025' => 8,
            'septiembre_2025' => 9,
            'octubre_2025' => 10,
            'noviembre_2025' => 11,
            'diciembre_2025' => 12
        ];

        foreach ($months as $columnName => $monthNumber) {
            $followupData = $this->cleanString($row[$columnName]);

            if (empty($followupData) || strlen($followupData) < 2) continue;

            // Verificar seguimiento para ESTE CONSUMO SPA específico
            $existingFollowup = MonthlyFollowup::where('followupable_id', $substanceConsumption->id)
                ->where('followupable_type', SubstanceConsumption::class)
                ->where('year', 2025)
                ->where('month', $monthNumber)
                ->first();

            if ($existingFollowup) continue;

            try {
                $description = "CONSUMO SPA - " . substr($followupData, 0, 400);

                $additionalInfo = [];
                if (!empty($substanceConsumption->diagnosis)) {
                    $additionalInfo[] = "Dx: " . substr($substanceConsumption->diagnosis, 0, 100);
                }
                if (!empty($substanceConsumption->substances_used) && is_array($substanceConsumption->substances_used)) {
                    $additionalInfo[] = "Sustancias: " . implode(', ', array_slice($substanceConsumption->substances_used, 0, 3));
                }
                if (!empty($substanceConsumption->consumption_level)) {
                    $additionalInfo[] = "Nivel: " . $substanceConsumption->consumption_level;
                }

                if (!empty($additionalInfo)) {
                    $description .= " | " . implode(" | ", $additionalInfo);
                }

                MonthlyFollowup::create([
                    'followupable_id' => $substanceConsumption->id,
                    'followupable_type' => SubstanceConsumption::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => substr($description, 0, 1000),
                    'status' => 'completed',
                    'actions_taken' => ['Seguimiento consumo SPA'],
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error seguimiento - " . $e->getMessage());
            }
        }
    }

    // ==================== MÉTODOS HELPER MEJORADOS ====================

    private function cleanString($value): ?string
    {
        if (empty($value)) return null;
        return trim(strip_tags((string)$value)) ?: null;
    }

    private function cleanPhone($phone): ?string
    {
        if (empty($phone)) return null;
        $phones = preg_split('/[-,\s\/]/', (string)$phone);
        $firstPhone = trim($phones[0]);
        $cleaned = preg_replace('/\D/', '', $firstPhone);
        return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
    }

    private function truncateString($string, $maxLength): ?string
    {
        if (empty($string)) return null;
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
    }

    private function isValidDocument($document): bool
    {
        $clean = preg_replace('/\D/', '', (string)$document);
        return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    }

    private function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim((string)$value));
        return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC', 'MS', 'AS', 'CN']) ? $type : 'CC';
    }

    private function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim((string)$value));
        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
        return 'Otro';
    }

    private function parseDate($value): ?\Carbon\Carbon
    {
        if (empty($value)) return null;
        try {
            return is_numeric($value)
                ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value)
                : Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
// class ConsumoSpaSheet implements ToCollection, WithHeadingRow
// {
//     protected $parent;

//     public function __construct(MentalHealthImport $parent)
//     {
//         $this->parent = $parent;
//     }

//     public function collection(Collection $collection)
//     {
//         Log::info("Procesando hoja CONSUMO SPA 2025 - {$collection->count()} registros");

//         foreach ($collection as $index => $row) {
//             $this->processRow($row, $index + 2);
//         }
//     }

//     private function processRow(Collection $row, int $rowNumber)
//     {
//         try {
//             $documentNumber = $this->cleanString($row['n_documento']);

//             if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
//                 $this->parent->incrementSkipped();
//                 return;
//             }

//             // 1. Crear o actualizar paciente
//             $patient = $this->createOrUpdatePatient($row, $rowNumber);
//             if (!$patient) return;

//             // 2. Crear caso de consumo de sustancias
//             $substanceConsumption = $this->createSubstanceConsumptionCase($patient, $row, $rowNumber);
//             if (!$substanceConsumption) return;

//             // 3. Crear seguimientos mensuales asociados al caso
//             $this->processMonthlyFollowups($substanceConsumption, $row, $rowNumber);
//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: " . $e->getMessage());
//         }
//     }

//     private function createOrUpdatePatient(Collection $row, int $rowNumber)
//     {
//         $documentNumber = $this->cleanString($row['n_documento']);
//         $patient = Patient::where('document_number', $documentNumber)->first();

//         $patientData = [
//             'document_number' => $documentNumber,
//             'document_type' => $this->mapDocumentType($row['tipo_doc']),
//             'full_name' => $this->truncateString($this->cleanString($row['nombre_completo']), 255),
//             'gender' => $this->mapGender($row['sexo']),
//             'birth_date' => $this->parseDate($row['fecha_de_nacimiento'])?->format('Y-m-d'),
//             'phone' => $this->cleanPhone($row['telefono']),
//             'eps_name' => $this->truncateString($this->cleanString($row['eps'] ?? $row['eps_nombre']), 255),
//             'status' => 'active',
//         ];

//         $patientData = array_filter($patientData, fn($value) => $value !== null);

//         try {
//             if ($patient) {
//                 $patient->update($patientData);
//                 $this->parent->incrementUpdated();
//             } else {
//                 $patient = Patient::create($patientData);
//                 $this->parent->incrementImported();
//             }
//             return $patient;
//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD paciente - " . $e->getMessage());
//             return null;
//         }
//     }

//     private function createSubstanceConsumptionCase(Patient $patient, Collection $row, int $rowNumber)
//     {
//         try {
//             // Verificar si ya existe un caso para este paciente
//             $existingCase = SubstanceConsumption::where('patient_id', $patient->id)->first();

//             // Procesar sustancias utilizadas
//             $substancesArray = [];
//             // En este caso no hay columna específica de sustancias en CONSUMO SPA, 
//             // pero podemos inferirlo del diagnóstico o crear un array genérico
//             $substancesArray = ['SPA']; // Genérico por ahora

//             $caseData = [
//                 'patient_id' => $patient->id,
//                 'admission_date' => $this->parseDate($row['fecha_de_ingres']) ?? now(),
//                 'admission_via' => $this->cleanString($row['ingreso_por']),
//                 'diagnosis' => $this->cleanString($row['diagnostico']),
//                 'substances_used' => $substancesArray,
//                 'consumption_level' => 'No especificado', // No está en el Excel
//                 'additional_observation' => $this->cleanString($row['observacion_adicional']),
//                 'status' => 'active',
//                 'created_by' => auth()->id() ?? 1,
//             ];

//             $caseData = array_filter($caseData, function ($value) {
//                 return $value !== null && $value !== '' && $value !== [];
//             });

//             if ($existingCase) {
//                 $existingCase->update($caseData);
//                 return $existingCase;
//             } else {
//                 $substanceConsumption = SubstanceConsumption::create($caseData);
//                 $this->parent->incrementCases();
//                 return $substanceConsumption;
//             }
//         } catch (\Exception $e) {
//             $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error creando caso - " . $e->getMessage());
//             return null;
//         }
//     }

//     private function processMonthlyFollowups($substanceConsumption, Collection $row, int $rowNumber)
//     {
//         $months = [
//             'enero_2025' => 1,
//             'febrero_2025' => 2,
//             'marzo_2025' => 3,
//             'abril_2025' => 4,
//             'mayo_2025' => 5,
//             'junio_2025' => 6,
//             'julio_2025' => 7,
//             'agosto_2025' => 8,
//             'septiembre_2025' => 9,
//             'octubre_2025' => 10,
//             'noviembre_2025' => 11,
//             'diciembre_2025' => 12
//         ];

//         foreach ($months as $columnName => $monthNumber) {
//             $followupData = $this->cleanString($row[$columnName]);

//             if (empty($followupData) || strlen($followupData) < 2) continue;

//             // ✅ CORRECCIÓN: Verificar seguimiento para ESTE CONSUMO SPA específico
//             $existingFollowup = MonthlyFollowup::where('followupable_id', $substanceConsumption->id)
//                 ->where('followupable_type', SubstanceConsumption::class) // ✅ CAMBIO CLAVE
//                 ->where('year', 2025)
//                 ->where('month', $monthNumber)
//                 ->first();

//             if ($existingFollowup) continue;

//             try {
//                 $description = "CONSUMO SPA - " . substr($followupData, 0, 400);

//                 $additionalInfo = [];
//                 if (!empty($substanceConsumption->diagnosis)) {
//                     $additionalInfo[] = "Dx: " . substr($substanceConsumption->diagnosis, 0, 100);
//                 }
//                 if (!empty($substanceConsumption->substances_used) && is_array($substanceConsumption->substances_used)) {
//                     $additionalInfo[] = "Sustancias: " . implode(', ', array_slice($substanceConsumption->substances_used, 0, 3));
//                 }
//                 if (!empty($substanceConsumption->consumption_level)) {
//                     $additionalInfo[] = "Nivel: " . $substanceConsumption->consumption_level;
//                 }

//                 if (!empty($additionalInfo)) {
//                     $description .= " | " . implode(" | ", $additionalInfo);
//                 }

//                 MonthlyFollowup::create([
//                     'followupable_id' => $substanceConsumption->id,
//                     'followupable_type' => SubstanceConsumption::class, // ✅ CAMBIO CLAVE
//                     'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
//                     'year' => 2025,
//                     'month' => $monthNumber,
//                     'description' => substr($description, 0, 1000),
//                     'status' => 'completed',
//                     'actions_taken' => ['Seguimiento consumo SPA'], // Array directo
//                     'performed_by' => auth()->id() ?? 1,
//                 ]);

//                 $this->parent->incrementFollowups();
//             } catch (\Exception $e) {
//                 $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error seguimiento - " . $e->getMessage());
//             }
//         }
//     }

//     // Métodos helper (iguales que las otras hojas)
//     private function cleanString($value): ?string
//     {
//         return empty($value) ? null : (trim(strip_tags((string)$value)) ?: null);
//     }
//     private function cleanPhone($phone): ?string
//     {
//         if (empty($phone)) return null;
//         $phones = preg_split('/[-,\s\/]/', (string)$phone);
//         $firstPhone = trim($phones[0]);
//         $cleaned = preg_replace('/\D/', '', $firstPhone);
//         return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
//     }
//     private function truncateString($string, $maxLength): ?string
//     {
//         return empty($string) ? null : (strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string);
//     }
//     private function isValidDocument($document): bool
//     {
//         $clean = preg_replace('/\D/', '', (string)$document);
//         return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
//     }
//     private function mapDocumentType($value): string
//     {
//         if (empty($value)) return 'CC';
//         $type = strtoupper(trim((string)$value));
//         return in_array($type, ['CC', 'TI', 'CE', 'PA', 'RC']) ? $type : 'CC';
//     }
//     private function mapGender($value): string
//     {
//         if (empty($value)) return 'Otro';
//         $gender = strtoupper(trim((string)$value));
//         if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) return 'Masculino';
//         if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) return 'Femenino';
//         return 'Otro';
//     }
//     private function parseDate($value): ?Carbon
//     {
//         if (empty($value)) return null;
//         try {
//             return is_numeric($value) ? Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($value) : Carbon::parse($value);
//         } catch (\Exception $e) {
//             return null;
//         }
//     }
// }
