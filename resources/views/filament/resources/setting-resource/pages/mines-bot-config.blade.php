<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <x-filament::button type="submit" wire:loading.attr="disabled" class="mt-4">
            <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="save" />
            <span wire:loading.remove wire:target="save">
                Salvar Configurações
            </span>
            <span wire:loading wire:target="save">
                Salvando...
            </span>
        </x-filament::button>
    </form>
</x-filament-panels::page>
