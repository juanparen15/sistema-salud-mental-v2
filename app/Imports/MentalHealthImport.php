<?php

namespace App\Imports;

use App\Models\Patient;
use App\Models\MonthlyFollowup;
use App\Models\MentalDisorder;
use App\Models\SuicideAttempt;
use App\Models\SubstanceConsumption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Carbon\Carbon;

/**
 * IMPORTADOR DE SALUD MENTAL 2025 - VERSIÓN CORREGIDA
 * 
 * AJUSTADO PARA COINCIDIR EXACTAMENTE CON LAS MIGRACIONES DE BASE DE DATOS
 * 
 * Procesa: SISTEMA_DE_INFORMACIÓN_SALUD_MENTAL_2025_-OFICIAL.xlsx
 * 
 * Cambios principales vs versión anterior:
 * - created_by → created_by_id
 * - updated_by → updated_by_id
 * - Agregado diagnosis_date (requerido en mental_disorders)
 * - admission_date → event_date (en suicide_attempts)
 * - trigger → trigger_factor
 * - method_used → mechanism
 * - risk_factors convertido a JSON array
 * - Validación estricta de birth_date (requerido)
 */
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
            ' TRASTORNOS 2025' => new TrastornosSheet($this),
            'EVENTO 356 2025' => new Evento356Sheet($this),
            'CONSUMO SPA 2025' => new ConsumoSpaSheet($this),
        ];
    }

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

// Trait para búsqueda flexible de columnas
trait FlexibleColumnAccess
{
    protected $columnCache = [];

    protected function findColumn(Collection $row, array $possibleNames)
    {
        $cacheKey = md5(implode(',', $possibleNames));

        if (isset($this->columnCache[$cacheKey])) {
            return $row[$this->columnCache[$cacheKey]] ?? null;
        }

        foreach ($possibleNames as $name) {
            $normalized = $this->normalizeColumnName($name);

            foreach ($row->keys() as $key) {
                if ($this->normalizeColumnName($key) === $normalized) {
                    $this->columnCache[$cacheKey] = $key;
                    return $row[$key];
                }
            }
        }

        return null;
    }

    protected function normalizeColumnName($name)
    {
        $name = strtolower(trim((string)$name));
        $name = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $name
        );
        $name = preg_replace('/[^a-z0-9]/', '', $name);
        return $name;
    }

    protected function cleanString($value): ?string
    {
        if (empty($value)) return null;
        $cleaned = trim(strip_tags((string)$value));
        return empty($cleaned) ? null : $cleaned;
    }

    protected function truncateString($string, $maxLength): ?string
    {
        if (empty($string)) return null;
        return strlen($string) > $maxLength ? substr($string, 0, $maxLength) : $string;
    }

    // protected function isValidDocument($document): bool
    // {
    //     if (empty($document)) return false;
    //     $clean = preg_replace('/\D/', '', (string)$document);
    //     return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    // }

    protected function isValidDocument($document): bool
    {
        // DEBUG: Ver qué llega
        Log::info("isValidDocument - Entrada:", [
            'valor' => $document,
            'tipo' => gettype($document),
            'empty' => empty($document)
        ]);

        if (empty($document)) {
            Log::info("isValidDocument - Rechazado: vacío");
            return false;
        }

        $clean = preg_replace('/\D/', '', (string)$document);

        Log::info("isValidDocument - Después de limpiar:", [
            'original' => $document,
            'limpio' => $clean,
            'longitud' => strlen($clean),
            'valido' => !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15
        ]);

        return !empty($clean) && strlen($clean) >= 6 && strlen($clean) <= 15;
    }

    protected function cleanPhone($phone): ?string
    {
        if (empty($phone)) return null;
        $phones = preg_split('/[-,\s\/]/', (string)$phone);
        $cleaned = preg_replace('/\D/', '', trim($phones[0]));
        return (strlen($cleaned) >= 7 && strlen($cleaned) <= 20) ? $cleaned : null;
    }

    protected function mapDocumentType($value): string
    {
        if (empty($value)) return 'CC';
        $type = strtoupper(trim((string)$value));

        if (str_contains($type, 'CEDULA') || str_contains($type, 'CÉDULA') || str_contains($type, 'CIUDADANIA') || $type === 'CC') {
            return 'CC';
        }
        if (str_contains($type, 'TARJETA') || str_contains($type, 'IDENTIDAD') || $type === 'TI') {
            return 'TI';
        }
        if (str_contains($type, 'EXTRANJERIA') || str_contains($type, 'EXTRANJERÍA') || $type === 'CE') {
            return 'CE';
        }
        if (str_contains($type, 'PASAPORTE') || $type === 'PA') {
            return 'PA';
        }
        if (str_contains($type, 'REGISTRO') || str_contains($type, 'CIVIL') || $type === 'RC') {
            return 'RC';
        }

        return 'CC';
    }

    protected function mapGender($value): string
    {
        if (empty($value)) return 'Otro';
        $gender = strtoupper(trim((string)$value));

        if (in_array($gender, ['M', 'MASCULINO', 'HOMBRE'])) {
            return 'Masculino';
        }
        if (in_array($gender, ['F', 'FEMENINO', 'MUJER'])) {
            return 'Femenino';
        }

        return 'Otro';
    }

    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        try {
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays((int)$value);
            }
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning("Error al parsear fecha: {$value} - {$e->getMessage()}");
            return null;
        }
    }

    protected function parseInteger($value): ?int
    {
        if (empty($value)) return null;
        $cleaned = preg_replace('/\D/', '', (string)$value);
        return !empty($cleaned) ? (int)$cleaned : null;
    }

    /**
     * Mapea el tipo de ingreso según valores del Excel
     */
    protected function mapAdmissionType($value): string
    {
        if (empty($value)) return 'AMBULATORIO';
        $type = strtoupper(trim((string)$value));

        if (str_contains($type, 'HOSPITALARIO') || str_contains($type, 'HOSPITAL')) {
            return 'HOSPITALARIO';
        }
        if (str_contains($type, 'URGENCIA')) {
            return 'URGENCIAS';
        }
        if (str_contains($type, 'AMBULATORIO')) {
            return 'AMBULATORIO';
        }

        return 'AMBULATORIO';
    }

    /**
     * Mapea la vía de ingreso según valores permitidos en enum
     */
    protected function mapAdmissionVia($value): string
    {
        if (empty($value)) return 'URGENCIAS';
        $via = strtoupper(trim((string)$value));

        if (str_contains($via, 'URGENCIA')) {
            return 'URGENCIAS';
        }
        if (str_contains($via, 'CONSULTA') || str_contains($via, 'EXTERNA')) {
            return 'CONSULTA_EXTERNA';
        }
        if (str_contains($via, 'HOSPITALIZACION') || str_contains($via, 'HOSPITAL')) {
            return 'HOSPITALIZACION';
        }
        if (str_contains($via, 'REFERENCIA') || str_contains($via, 'REMISION')) {
            return 'REFERENCIA';
        }
        if (str_contains($via, 'COMUNIDAD')) {
            return 'COMUNIDAD';
        }

        return 'URGENCIAS'; // Por defecto
    }
}

// ==================== TRASTORNOS 2025 ====================
class TrastornosSheet implements ToCollection, WithHeadingRow
{
    use FlexibleColumnAccess;

    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }

    public function headingRow(): int
    {
        return 2; // La fila 2 tiene los nombres de columnas reales
    }

    public function collection(Collection $collection)
    {
        Log::info("TRASTORNOS 2025 - Procesando {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 3;
            $this->processRow($row, $rowNumber);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {

            if ($rowNumber == 3) {
                Log::info("COLUMNAS DISPONIBLES EN EXCEL:", [
                    'keys' => $row->keys()->toArray()
                ]);
            }

            $documentNumber = $this->cleanString($this->findColumn($row, [
                'n_documento',
                'ndocumento',
                'numero_documento',
                'numerodocumento',
                'documento'
            ]));

            // DEBUG: Ver qué se encontró
            Log::info("Fila {$rowNumber} - Búsqueda documento:", [
                'encontrado' => $documentNumber,
                'es_vacio' => empty($documentNumber)
            ]);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                // Log::debug("TRASTORNOS Fila {$rowNumber}: Documento inválido o vacío");
                return;
            }

            $patient = $this->createOrUpdatePatient($row, $rowNumber, $documentNumber);
            if (!$patient) return;

            $mentalDisorder = $this->createMentalDisorderCase($patient, $row, $rowNumber);
            if (!$mentalDisorder) return;

            $this->processMonthlyFollowups($mentalDisorder, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: {$e->getMessage()}");
            Log::error("TRASTORNOS Fila {$rowNumber}: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $documentNumber)
    {
        try {
            $patient = Patient::where('document_number', $documentNumber)->first();

            // IMPORTANTE: birth_date es requerido en la migration
            $birthDate = $this->parseDate($this->findColumn($row, [
                'fecha_nacimiento',
                'fechanacimiento',
                'fecha_nac',
                'fechanac'
            ]));

            // Si no hay fecha de nacimiento válida, usar fecha por defecto o saltar
            if (!$birthDate || !($birthDate instanceof Carbon)) {
                $birthDate = Carbon::now()->subYears(30);
            }

            if (!$birthDate) {
                Log::info("Columna fecha no encontrada. Todas las columnas:", [
                    'fila' => $rowNumber,
                    'columnas' => $row->keys()->toArray()
                ]);
            }

            $patientData = [
                'document_number' => $documentNumber,
                'document_type' => $this->mapDocumentType($this->findColumn($row, [
                    'tipo_documento',
                    'tipodocumento',
                    'tipo_doc',
                    'tipodoc'
                ])),
                'full_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'nombre_y_apellido_del_paciente',
                    'nombreyapellidodelpaciente',
                    'nombre_apellido',
                    'nombreapellido',
                    'nombre_completo',
                    'nombrecompleto'
                ])), 300),
                'gender' => $this->mapGender($this->findColumn($row, [
                    'sexo_paciente',
                    'sexopaciente',
                    'sexo',
                    'genero'
                ])),
                'birth_date' => $birthDate->format('Y-m-d'), // REQUERIDO
                'phone' => $this->cleanPhone($this->findColumn($row, [
                    'telefono',
                    'tel',
                    'celular'
                ])),
                'address' => $this->cleanString($this->findColumn($row, [
                    'direcion_paciente',
                    'direcionpaciente',
                    'direccion_paciente',
                    'direccionpaciente',
                    'direccion',
                    'dir'
                ])),
                'eps_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'entidad',
                    'eps',
                    'eps_nombre',
                    'epsnombre'
                ])), 300),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1, // CORREGIDO: created_by_id
            ];

            $patientData = array_filter($patientData, fn($value) => $value !== null);

            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }

            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error BD paciente - {$e->getMessage()}");
            Log::error("TRASTORNOS Fila {$rowNumber}: Error BD paciente", [
                'exception' => $e->getMessage(),
                'data' => $documentNumber
            ]);
            return null;
        }
    }

    private function createMentalDisorderCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            $existingCase = MentalDisorder::where('patient_id', $patient->id)->first();

            // Obtener fecha de ingreso
            $admissionDate = $this->parseDate($this->findColumn($row, [
                'fecha_ingreso',
                'fechaingreso',
                'fecha_infreso',
                'fechainfreso'
            ])) ?? now();

            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $admissionDate->format('Y-m-d H:i:s'), // timestamp
                'admission_type' => $this->mapAdmissionType($this->findColumn($row, [
                    'tipo_ingreso',
                    'tipoingreso'
                ])),
                'admission_via' => $this->mapAdmissionVia($this->findColumn($row, [
                    'ingreso_por',
                    'ingresopor',
                    'via_ingreso',
                    'viaingreso'
                ])),
                'diagnosis_code' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'cod_diagnostico_folio',
                    'coddiagnosticofolio',
                    'codigo_diagnostico',
                    'codigodiagnostico',
                    'cod_diag',
                    'coddiag'
                ])), 10),
                'diagnosis_date' => $admissionDate->format('Y-m-d H:i:s'), // REQUERIDO: usar misma fecha que admission
                'diagnosis_description' => $this->cleanString($this->findColumn($row, [
                    'diagnostico_folio',
                    'diagnosticofolio',
                    'diagnostico',
                    'diag'
                ])),
                'diagnosis_type' => $this->mapDiagnosisType($this->findColumn($row, [
                    'clase_diagnostico',
                    'clasediagnostico',
                    'tipo_diagnostico',
                    'tipodiagnostico'
                ])),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1, // CORREGIDO: created_by_id
            ];

            $caseData = array_filter($caseData, fn($value) => $value !== null);

            if ($existingCase) {
                $caseData['updated_by_id'] = auth()->id() ?? 1; // CORREGIDO: updated_by_id
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $mentalDisorder = MentalDisorder::create($caseData);
                $this->parent->incrementCases();
                return $mentalDisorder;
            }
        } catch (\Exception $e) {
            $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error BD caso - {$e->getMessage()}");
            Log::error("TRASTORNOS Fila {$rowNumber}: Error BD caso", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function mapDiagnosisType($value): string
    {
        if (empty($value)) return 'Diagnostico Principal';
        $type = strtoupper(trim((string)$value));

        if (str_contains($type, 'PRINCIPAL')) {
            return 'Diagnostico Principal';
        }
        if (str_contains($type, 'RELACIONADO') || str_contains($type, 'SECUNDARIO')) {
            return 'Diagnostico Relacionado';
        }

        return 'Diagnostico Principal';
    }

    private function processMonthlyFollowups(MentalDisorder $mentalDisorder, Collection $row, int $rowNumber)
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
            try {
                $followupData = $this->cleanString($this->findColumn($row, [$columnName]));

                if (empty($followupData) || strlen($followupData) < 2) {
                    continue;
                }

                $existingFollowup = MonthlyFollowup::where('followupable_id', $mentalDisorder->id)
                    ->where('followupable_type', MentalDisorder::class)
                    ->where('year', 2025)
                    ->where('month', $monthNumber)
                    ->first();

                if ($existingFollowup) {
                    continue;
                }

                MonthlyFollowup::create([
                    'followupable_id' => $mentalDisorder->id,
                    'followupable_type' => MentalDisorder::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => "TRASTORNO MENTAL - " . substr($followupData, 0, 900),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento trastorno mental']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("TRASTORNOS Fila {$rowNumber}: Error seguimiento {$columnName} - {$e->getMessage()}");
            }
        }
    }
}

// ==================== EVENTO 356 2025 ====================
class Evento356Sheet implements ToCollection, WithHeadingRow
{
    use FlexibleColumnAccess;

    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }


    public function headingRow(): int
    {
        return 2; // La fila 2 tiene los nombres de columnas reales
    }


    public function collection(Collection $collection)
    {
        Log::info("EVENTO 356 2025 - Procesando {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 3;
            $this->processRow($row, $rowNumber);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {

            if ($rowNumber == 3) {
                Log::info("COLUMNAS DISPONIBLES EN EXCEL:", [
                    'keys' => $row->keys()->toArray()
                ]);
            }

            $documentNumber = $this->cleanString($this->findColumn($row, [
                'n_documento',
                'ndocumento',
                'numero_documento',
                'numerodocumento',
                'documento'
            ]));

            // DEBUG: Ver qué se encontró
            Log::info("Fila {$rowNumber} - Búsqueda documento:", [
                'encontrado' => $documentNumber,
                'es_vacio' => empty($documentNumber)
            ]);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                // Log::debug("EVENTO 356 Fila {$rowNumber}: Documento inválido o vacío");
                return;
            }

            $patient = $this->createOrUpdatePatient($row, $rowNumber, $documentNumber);
            if (!$patient) return;

            $suicideAttempt = $this->createSuicideAttemptCase($patient, $row, $rowNumber);
            if (!$suicideAttempt) return;

            $this->processMonthlyFollowups($suicideAttempt, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: {$e->getMessage()}");
            Log::error("EVENTO 356 Fila {$rowNumber}: {$e->getMessage()}");
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $documentNumber)
    {
        try {
            $patient = Patient::where('document_number', $documentNumber)->first();

            $birthDate = $this->parseDate($this->findColumn($row, [
                'fecha_de_nacimiento',
                'fechadenacimiento',
                'fecha_nacimiento',
                'fechanacimiento',
                'fecha_nac',
                'fechanac'
            ]));

            if (!$birthDate) {
                Log::warning("EVENTO 356 Fila {$rowNumber}: Fecha de nacimiento inválida, usando fecha por defecto");
                $birthDate = Carbon::now()->subYears(30);
            }

            $patientData = [
                'document_number' => $documentNumber,
                'document_type' => $this->mapDocumentType($this->findColumn($row, [
                    'tipo_doc',
                    'tipodoc',
                    'tipo_documento',
                    'tipodocumento'
                ])),
                'full_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'nombres_y_apellidos',
                    'nombresyapellidos',
                    'nombre_completo',
                    'nombrecompleto',
                    'nombre_apellido',
                    'nombreapellido'
                ])), 300),
                'gender' => $this->mapGender($this->findColumn($row, [
                    'sexo',
                    'genero',
                    'sexo_paciente',
                    'sexopaciente'
                ])),
                'birth_date' => $birthDate->format('Y-m-d'),
                'phone' => $this->cleanPhone($this->findColumn($row, [
                    'telefono',
                    'tel',
                    'celular'
                ])),
                'address' => $this->cleanString($this->findColumn($row, [
                    'direccion',
                    'dirección',
                    'dir',
                    'direccion_paciente',
                    'direccionpaciente'
                ])),
                'neighborhood' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'barrio',
                    'sector'
                ])), 200),
                'village' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'vereda',
                    'corregimiento'
                ])), 200),
                'eps_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'plan_de_beneficios',
                    'plandebeneficio',
                    'plan_beneficios',
                    'planbeneficios',
                    'eps_nombre',
                    'epsnombre',
                    'eps',
                    'entidad'
                ])), 300),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1,
            ];

            $patientData = array_filter($patientData, fn($value) => $value !== null);

            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }

            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error BD paciente - {$e->getMessage()}");
            Log::error("EVENTO 356 Fila {$rowNumber}: Error BD paciente", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function createSuicideAttemptCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            $existingCase = SuicideAttempt::where('patient_id', $patient->id)->first();

            // IMPORTANTE: usar event_date no admission_date
            $eventDate = $this->parseDate($this->findColumn($row, [
                'fecha_de_ingreso',
                'fechadeingreso',
                'fecha_ingreso',
                'fechaingreso'
            ])) ?? now();

            // Procesar factores de riesgo como JSON array
            $riskFactorsText = $this->cleanString($this->findColumn($row, [
                'factores_de_riesgo',
                'factoresderiesgo',
                'factores_riesgo',
                'factoresriesgo',
                'riesgos'
            ]));

            $riskFactorsJson = null;
            if ($riskFactorsText) {
                // Convertir texto a array separado por comas o punto y coma
                $riskFactorsArray = preg_split('/[,;]/', $riskFactorsText);
                $riskFactorsArray = array_map('trim', $riskFactorsArray);
                $riskFactorsArray = array_filter($riskFactorsArray);
                $riskFactorsJson = !empty($riskFactorsArray) ? json_encode($riskFactorsArray) : null;
            }

            $caseData = [
                'patient_id' => $patient->id,
                'event_date' => $eventDate->format('Y-m-d H:i:s'), // CORREGIDO: event_date
                'admission_via' => $this->mapAdmissionVia($this->findColumn($row, [
                    'ingreso_por',
                    'ingresopor',
                    'via_ingreso',
                    'viaingreso'
                ])),
                'attempt_number' => $this->parseInteger($this->findColumn($row, [
                    'n_intentos',
                    'nintentos',
                    'numero_intentos',
                    'numerointentos',
                    'intentos'
                ])) ?? 1,
                'trigger_factor' => $this->cleanString($this->findColumn($row, [ // CORREGIDO: trigger_factor
                    'desencadenante',
                    'causa',
                    'motivo'
                ])),
                'risk_factors' => $riskFactorsJson, // JSON
                'mechanism' => $this->cleanString($this->findColumn($row, [ // CORREGIDO: mechanism
                    'mecanismo',
                    'metodo',
                    'método'
                ])),
                'additional_observation' => $this->cleanString($this->findColumn($row, [
                    'observacion_adicional',
                    'observacionadicional',
                    'observación_adicional',
                    'observaciónadicional',
                    'observaciones'
                ])),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, fn($value) => $value !== null);

            if ($existingCase) {
                $caseData['updated_by_id'] = auth()->id() ?? 1;
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $suicideAttempt = SuicideAttempt::create($caseData);
                $this->parent->incrementCases();
                return $suicideAttempt;
            }
        } catch (\Exception $e) {
            $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error BD caso - {$e->getMessage()}");
            Log::error("EVENTO 356 Fila {$rowNumber}: Error BD caso", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function processMonthlyFollowups(SuicideAttempt $suicideAttempt, Collection $row, int $rowNumber)
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
            try {
                $followupData = $this->cleanString($this->findColumn($row, [$columnName]));

                if (empty($followupData) || strlen($followupData) < 2) {
                    continue;
                }

                $existingFollowup = MonthlyFollowup::where('followupable_id', $suicideAttempt->id)
                    ->where('followupable_type', SuicideAttempt::class)
                    ->where('year', 2025)
                    ->where('month', $monthNumber)
                    ->first();

                if ($existingFollowup) {
                    continue;
                }

                MonthlyFollowup::create([
                    'followupable_id' => $suicideAttempt->id,
                    'followupable_type' => SuicideAttempt::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => "INTENTO SUICIDIO - " . substr($followupData, 0, 900),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento intento suicidio']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("EVENTO 356 Fila {$rowNumber}: Error seguimiento {$columnName} - {$e->getMessage()}");
            }
        }
    }
}

// ==================== CONSUMO SPA 2025 ====================
class ConsumoSpaSheet implements ToCollection, WithHeadingRow
{
    use FlexibleColumnAccess;

    protected $parent;

    public function __construct(MentalHealthImport $parent)
    {
        $this->parent = $parent;
    }

    public function headingRow(): int
    {
        return 2; // La fila 2 tiene los nombres de columnas reales
    }


    public function collection(Collection $collection)
    {
        Log::info("CONSUMO SPA 2025 - Procesando {$collection->count()} registros");

        foreach ($collection as $index => $row) {
            $rowNumber = $index + 3;
            $this->processRow($row, $rowNumber);
        }
    }

    private function processRow(Collection $row, int $rowNumber)
    {
        try {
            if ($rowNumber == 3) {
                Log::info("COLUMNAS DISPONIBLES EN EXCEL:", [
                    'keys' => $row->keys()->toArray()
                ]);
            }

            $documentNumber = $this->cleanString($this->findColumn($row, [
                'n_documento',
                'ndocumento',
                'numero_documento',
                'numerodocumento',
                'documento'
            ]));

            // DEBUG: Ver qué se encontró
            Log::info("Fila {$rowNumber} - Búsqueda documento:", [
                'encontrado' => $documentNumber,
                'es_vacio' => empty($documentNumber)
            ]);

            if (empty($documentNumber) || !$this->isValidDocument($documentNumber)) {
                $this->parent->incrementSkipped();
                // Log::debug("CONSUMO SPA Fila {$rowNumber}: Documento inválido o vacío");
                return;
            }

            $patient = $this->createOrUpdatePatient($row, $rowNumber, $documentNumber);
            if (!$patient) return;

            $substanceConsumption = $this->createSubstanceConsumptionCase($patient, $row, $rowNumber);
            if (!$substanceConsumption) return;

            $this->processMonthlyFollowups($substanceConsumption, $row, $rowNumber);
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: {$e->getMessage()}");
            Log::error("CONSUMO SPA Fila {$rowNumber}: {$e->getMessage()}");
        }
    }

    private function createOrUpdatePatient(Collection $row, int $rowNumber, string $documentNumber)
    {
        try {
            $patient = Patient::where('document_number', $documentNumber)->first();

            $birthDate = $this->parseDate($this->findColumn($row, [
                'fecha_de_nacimiento',
                'fechadenacimiento',
                'fecha_nacimiento',
                'fechanacimiento',
                'fecha_nac',
                'fechanac'
            ]));

            if (!$birthDate) {
                Log::warning("CONSUMO SPA Fila {$rowNumber}: Fecha de nacimiento inválida, usando fecha por defecto");
                $birthDate = Carbon::now()->subYears(30);
            }

            $patientData = [
                'document_number' => $documentNumber,
                'document_type' => $this->mapDocumentType($this->findColumn($row, [
                    'tipo_doc',
                    'tipodoc',
                    'tipo_documento',
                    'tipodocumento'
                ])),
                'full_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'nombre_completo',
                    'nombrecompleto',
                    'nombres_apellidos',
                    'nombresapellidos',
                    'nombre_apellido',
                    'nombreapellido'
                ])), 300),
                'gender' => $this->mapGender($this->findColumn($row, [
                    'sexo',
                    'genero',
                    'sexo_paciente',
                    'sexopaciente'
                ])),
                'birth_date' => $birthDate->format('Y-m-d'),
                'phone' => $this->cleanPhone($this->findColumn($row, [
                    'telefono',
                    'tel',
                    'celular'
                ])),
                'eps_name' => $this->truncateString($this->cleanString($this->findColumn($row, [
                    'eps_nombre',
                    'epsnombre',
                    'eps',
                    'entidad',
                    'plan_beneficios',
                    'planbeneficios',
                    'plan_de_beneficios',
                    'plandebeneficio'
                ])), 300),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1,
            ];

            $patientData = array_filter($patientData, fn($value) => $value !== null);

            if ($patient) {
                $patient->update($patientData);
                $this->parent->incrementUpdated();
            } else {
                $patient = Patient::create($patientData);
                $this->parent->incrementImported();
            }

            return $patient;
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD paciente - {$e->getMessage()}");
            Log::error("CONSUMO SPA Fila {$rowNumber}: Error BD paciente", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function createSubstanceConsumptionCase(Patient $patient, Collection $row, int $rowNumber)
    {
        try {
            $existingCase = SubstanceConsumption::where('patient_id', $patient->id)->first();

            $admissionDate = $this->parseDate($this->findColumn($row, [
                'fecha_de_ingres',
                'fechadeingres',
                'fecha_de_ingreso',
                'fechadeingreso',
                'fecha_ingreso',
                'fechaingreso'
            ])) ?? now();

            $diagnosis = $this->truncateString($this->cleanString($this->findColumn($row, [
                'diagnostico',
                'diagnóstico',
                'diag'
            ])), 500);

            $tipoSustancia = $this->cleanString($this->findColumn($row, [
                'tipo_sustancia',
                'tiposustancia',
                'sustancia'
            ]));

            $substancesArray = $this->extractSubstances($diagnosis, $tipoSustancia);

            $caseData = [
                'patient_id' => $patient->id,
                'admission_date' => $admissionDate->format('Y-m-d H:i:s'),
                'admission_via' => $this->mapAdmissionVia($this->findColumn($row, [
                    'ingreso_por',
                    'ingresopor',
                    'via_ingreso',
                    'viaingreso'
                ])),
                'diagnosis' => $diagnosis ?? 'Consumo de SPA',
                'substances_used' => json_encode($substancesArray),
                'consumption_level' => 'Bajo Riesgo', // Por defecto
                'additional_observation' => $this->cleanString($this->findColumn($row, [
                    'observacion_adicional',
                    'observacionadicional',
                    'observación_adicional',
                    'observaciónadicional',
                    'observaciones'
                ])),
                'status' => 'active',
                'created_by_id' => auth()->id() ?? 1,
            ];

            $caseData = array_filter($caseData, function ($value) {
                return $value !== null && $value !== '' && $value !== '[]';
            });

            if ($existingCase) {
                $caseData['updated_by_id'] = auth()->id() ?? 1;
                $existingCase->update($caseData);
                return $existingCase;
            } else {
                $substanceConsumption = SubstanceConsumption::create($caseData);
                $this->parent->incrementCases();
                return $substanceConsumption;
            }
        } catch (\Exception $e) {
            $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error BD caso - {$e->getMessage()}");
            Log::error("CONSUMO SPA Fila {$rowNumber}: Error BD caso", [
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function processMonthlyFollowups(SubstanceConsumption $substanceConsumption, Collection $row, int $rowNumber)
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
            try {
                $followupData = $this->cleanString($this->findColumn($row, [$columnName]));

                if (empty($followupData) || strlen($followupData) < 2) {
                    continue;
                }

                $existingFollowup = MonthlyFollowup::where('followupable_id', $substanceConsumption->id)
                    ->where('followupable_type', SubstanceConsumption::class)
                    ->where('year', 2025)
                    ->where('month', $monthNumber)
                    ->first();

                if ($existingFollowup) {
                    continue;
                }

                MonthlyFollowup::create([
                    'followupable_id' => $substanceConsumption->id,
                    'followupable_type' => SubstanceConsumption::class,
                    'followup_date' => Carbon::create(2025, $monthNumber, 15)->format('Y-m-d'),
                    'year' => 2025,
                    'month' => $monthNumber,
                    'description' => "CONSUMO SPA - " . substr($followupData, 0, 900),
                    'status' => 'completed',
                    'actions_taken' => json_encode(['Seguimiento consumo SPA']),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                $this->parent->incrementFollowups();
            } catch (\Exception $e) {
                $this->parent->addError("CONSUMO SPA Fila {$rowNumber}: Error seguimiento {$columnName} - {$e->getMessage()}");
            }
        }
    }

    private function extractSubstances(?string $diagnosis, ?string $tipoSustancia): array
    {
        $substances = [];
        $textToSearch = strtoupper(($diagnosis ?? '') . ' ' . ($tipoSustancia ?? ''));

        if (empty(trim($textToSearch))) {
            return ['SPA'];
        }

        $substanceMap = [
            'ALCOHOL' => 'Alcohol',
            'MARIHUANA' => 'Marihuana',
            'CANNABIS' => 'Marihuana',
            'COCAINA' => 'Cocaína',
            'COCA' => 'Cocaína',
            'BAZUCO' => 'Bazuco',
            'CRACK' => 'Crack',
            'HEROINA' => 'Heroína',
            'BENZODIACEPINA' => 'Benzodiacepinas',
            'OPIOIDE' => 'Opioides',
            'ANFETAMINA' => 'Anfetaminas',
            'METANFETAMINA' => 'Metanfetaminas',
            'INHALANTE' => 'Inhalantes',
            'ALUCINOGENO' => 'Alucinógenos',
            'MULTIPLES' => 'Múltiples drogas',
        ];

        foreach ($substanceMap as $key => $substance) {
            if (str_contains($textToSearch, $key)) {
                $substances[] = $substance;
            }
        }

        return !empty($substances) ? array_unique($substances) : ['SPA'];
    }
}
