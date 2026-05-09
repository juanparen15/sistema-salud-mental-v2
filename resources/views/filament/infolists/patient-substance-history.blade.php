{{-- Historial de consumo SPA del paciente --}}
@php
    $currentRecord = $getRecord();
    $patient = $currentRecord->patient;

    $allCases = \App\Models\SubstanceConsumption::where('patient_id', $patient->id)
        ->with(['followups' => fn($q) => $q->orderBy('year')->orderBy('month')])
        ->orderBy('admission_date', 'desc')
        ->get();

    $monthNames = [
        1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',
        7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic',
    ];
@endphp

<div class="space-y-3">
    @if($allCases->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron registros históricos para este paciente.</p>
    @else
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Total de registros: {{ $allCases->count() }}</p>

        @foreach($allCases as $case)
            @php
                $isCurrent = $case->id === $currentRecord->id;
                $cardClass = $isCurrent
                    ? 'border-orange-400 dark:border-orange-500 bg-orange-50 dark:bg-orange-950'
                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800';
                $statusColor = match($case->status) {
                    'active'       => 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200',
                    'in_treatment' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                    'recovered'    => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                    default        => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                };
                $statusLabel = match($case->status) {
                    'active'=>'Activo','inactive'=>'Inactivo','in_treatment'=>'En Tratamiento','recovered'=>'Recuperado', default=>$case->status
                };
                $substances = is_array($case->substances_used)
                    ? $case->substances_used
                    : json_decode($case->substances_used ?? '[]', true);
            @endphp
            <div class="border rounded-lg p-4 {{ $cardClass }}">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $case->admission_date->format('d/m/Y') }}</span>
                            @if($isCurrent)
                                <span class="text-xs bg-orange-500 text-white px-2 py-0.5 rounded-full">Registro actual</span>
                            @endif
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ Str::limit($case->diagnosis, 100) }}</div>
                        @if(!empty($substances))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($substances as $substance)
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">{{ $substance }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($case->followups->isNotEmpty())
                            <div class="mt-3">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Seguimientos ({{ $case->followups->count() }}):</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($case->followups as $f)
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                            {{ $monthNames[$f->month] ?? $f->month }}/{{ $f->year }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mt-2 text-xs text-gray-400 dark:text-gray-500">Sin seguimientos</div>
                        @endif
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full {{ $statusColor }} shrink-0">{{ $statusLabel }}</span>
                </div>
            </div>
        @endforeach
    @endif
</div>
