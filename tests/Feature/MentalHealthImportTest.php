<?php

namespace Tests\Feature;

use App\Imports\MentalHealthImport;
use App\Imports\TrastornosSheet;
use App\Models\MentalDisorder;
use App\Models\MonthlyFollowup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MentalHealthImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeRow(array $overrides = []): Collection
    {
        return collect(array_merge([
            'n_documento'                    => '12345678',
            'nombre_y_apellido_del_paciente' => 'Juan Pérez',
            'fecha_nacimiento'               => '1990-01-15',
            'sexo_paciente'                  => 'M',
            'tipo_documento'                 => 'CC',
            'fecha_ingreso'                  => '2026-03-10',
            'tipo_ingreso'                   => 'AMBULATORIO',
            'ingreso_por'                    => 'CONSULTA_EXTERNA',
            'cod_diagnostico_folio'          => 'F32.0',
            'diagnostico_folio'              => 'Episodio depresivo moderado',
            'clase_diagnostico'              => 'Diagnostico Principal',
            'seguimiento'                    => 'Paciente contactado, evolución favorable',
        ], $overrides));
    }

    private function getSheet(): TrastornosSheet
    {
        return new TrastornosSheet(new MentalHealthImport());
    }

    public function test_single_seguimiento_creates_followup_for_admission_month(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getSheet()->collection(collect([$this->makeRow()]));
        $this->assertDatabaseHas('monthly_followups', [
            'month'  => 3,
            'year'   => 2026,
            'status' => 'completed',
        ]);
    }

    public function test_empty_seguimiento_does_not_create_followup(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getSheet()->collection(collect([$this->makeRow(['seguimiento' => ''])]));
        $this->assertDatabaseCount('monthly_followups', 0);
    }

    public function test_duplicate_row_same_date_and_diagnosis_creates_only_one_case(): void
    {
        $this->actingAs(User::factory()->create());
        $row = $this->makeRow();
        $this->getSheet()->collection(collect([$row, $row]));
        $this->assertDatabaseCount('mental_disorders', 1);
    }

    public function test_same_patient_different_diagnosis_code_creates_new_case(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getSheet()->collection(collect([
            $this->makeRow(['cod_diagnostico_folio' => 'F32.0']),
            $this->makeRow(['cod_diagnostico_folio' => 'F33.1']),
        ]));
        $this->assertDatabaseCount('mental_disorders', 2);
    }

    public function test_same_patient_different_admission_date_creates_new_case(): void
    {
        $this->actingAs(User::factory()->create());
        $this->getSheet()->collection(collect([
            $this->makeRow(['fecha_ingreso' => '2026-01-15']),
            $this->makeRow(['fecha_ingreso' => '2026-03-10']),
        ]));
        $this->assertDatabaseCount('mental_disorders', 2);
    }

    public function test_old_monthly_columns_are_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $rowWithOldColumns = $this->makeRow([
            'seguimiento' => '',
            'enero_2025'  => 'Contactado',
        ]);
        $this->getSheet()->collection(collect([$rowWithOldColumns]));
        $this->assertDatabaseCount('monthly_followups', 0);
    }
}
