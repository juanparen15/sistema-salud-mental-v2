@php
    $followups = $getRecord()->followups()
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->get();
@endphp

@if($followups->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No se han registrado seguimientos mensuales
    </div>
@else
    <div class="space-y-3">
        <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Total de seguimientos: {{ $followups->count() }}
        </div>

        @foreach($followups as $followup)
            @php
                $statusColors = [
                    'completed' => ['bg' => 'bg-green-100 dark:bg-green-900', 'text' => 'text-green-800 dark:text-green-200'],
                    'pending' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900', 'text' => 'text-yellow-800 dark:text-yellow-200'],
                    'not_contacted' => ['bg' => 'bg-red-100 dark:bg-red-900', 'text' => 'text-red-800 dark:text-red-200'],
                    'refused' => ['bg' => 'bg-gray-100 dark:bg-gray-900', 'text' => 'text-gray-800 dark:text-gray-200'],
                ];

                $statusTexts = [
                    'completed' => 'Completado',
                    'pending' => 'Pendiente',
                    'not_contacted' => 'No Contactado',
                    'refused' => 'Rechazado',
                ];

                $statusColor = $statusColors[$followup->status] ?? ['bg' => 'bg-blue-100 dark:bg-blue-900', 'text' => 'text-blue-800 dark:text-blue-200'];
                $statusText = $statusTexts[$followup->status] ?? ucfirst($followup->status);
            @endphp

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-800">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $followup->month_name }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Fecha: {{ $followup->followup_date->format('d/m/Y') }}
                        </div>
                        @if($followup->description)
                            <div class="text-sm text-gray-700 dark:text-gray-300 mt-2">
                                {{ Str::limit($followup->description, 150) }}
                            </div>
                        @endif
                        @if($followup->next_followup)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Próximo seguimiento: {{ $followup->next_followup->format('d/m/Y') }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor['bg'] }} {{ $statusColor['text'] }}">
                            {{ $statusText }}
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
