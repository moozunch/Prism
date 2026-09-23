<x-filament::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Save Organization Information
            </x-filament::button>
        </div>
    </form>
</x-filament::page>
