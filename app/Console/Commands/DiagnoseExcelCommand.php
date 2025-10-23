<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DiagnoseExcelCommand extends Command
{
    protected $signature = 'excel:diagnose {file}';
    protected $description = 'Diagnosticar estructura del archivo Excel';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("❌ Archivo no encontrado: {$file}");
            return 1;
        }

        $this->info("📊 Analizando archivo: {$file}\n");

        try {
            $spreadsheet = IOFactory::load($file);
            $sheetNames = $spreadsheet->getSheetNames();
            
            $this->info("📋 Total de hojas: " . count($sheetNames) . "\n");

            foreach ($sheetNames as $index => $sheetName) {
                $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info("📄 Hoja #{$index}: {$sheetName}");
                
                $sheet = $spreadsheet->getSheet($index);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                
                $this->line("   Filas: {$highestRow}");
                $this->line("   Columnas: {$highestColumn}");
                
                // Obtener encabezados (primera fila)
                $headers = [];
                $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1')[0];
                
                $this->line("\n   🔤 Encabezados encontrados:");
                foreach ($headerRow as $key => $header) {
                    if (!empty($header)) {
                        $this->line("      • {$header}");
                        $headers[] = $header;
                    }
                }
                
                $this->line("\n   📊 Primeras 3 filas de datos:");
                for ($row = 2; $row <= min(4, $highestRow); $row++) {
                    $rowData = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}")[0];
                    $this->line("      Fila {$row}: " . implode(' | ', array_slice($rowData, 0, 5)));
                }
                
                $this->line("");
            }

            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
            
            $this->info("✅ Diagnóstico completado");
            $this->line("");
            $this->warn("⚠️  Verifica que las hojas tengan estos nombres:");
            $this->line("   1. TRASTORNOS 2025 (o similar)");
            $this->line("   2. EVENTO 356 2025 (o similar)");
            $this->line("   3. CONSUMO SPA 2025 (o similar)");
            $this->line("");
            $this->warn("⚠️  Verifica que existan estas columnas:");
            $this->line("   • tipo_de_documento");
            $this->line("   • numero_de_documento");
            $this->line("   • nombre_completo");
            
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}