<x-filament::page>
    <form wire:submit.prevent="submit">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Salvar Configurações
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
