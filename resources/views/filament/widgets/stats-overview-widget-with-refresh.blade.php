@php
    use Filament\Support\Enums\MaxWidth;
@endphp

<x-filament-widgets::widget
    :class="
        \Illuminate\Support\Arr::toCssClasses([
            'fi-wi-stats-overview',
            'fi-wi-stats-overview-with-refresh',
        ])
    "
>
    <div class="flex items-center justify-between p-2">
        <h2 class="text-xl font-bold tracking-tight">
            {{ __('Cálculo de Lucro') }}
        </h2>
        
        <div>
            {{ $this->getAction('refresh') }}
        </div>
    </div>

    @if (count($this->getCachedStats()))
        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}
            @endif
            @class([
                'fi-wi-stats-overview-stats-ctn grid gap-4 lg:grid-cols-3 p-4',
                match ($this->getColumns()) {
                    1 => 'sm:grid-cols-1',
                    2 => 'sm:grid-cols-2',
                    3 => 'sm:grid-cols-3',
                    4 => 'sm:grid-cols-4',
                    5 => 'sm:grid-cols-5',
                    6 => 'sm:grid-cols-6',
                    7 => 'sm:grid-cols-7',
                    8 => 'sm:grid-cols-8',
                    9 => 'sm:grid-cols-9',
                    10 => 'sm:grid-cols-10',
                    11 => 'sm:grid-cols-11',
                    12 => 'sm:grid-cols-12',
                    default => $this->getColumns(),
                },
            ])
        >
            @foreach ($this->getCachedStats() as $stat)
                {{ $stat }}
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
