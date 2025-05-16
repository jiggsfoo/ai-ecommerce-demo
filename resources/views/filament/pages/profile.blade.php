<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="fi-form-actions mt-6">
            <x-filament-actions::actions :actions="$this->getFormActions()" />
        </div>
    </form>
</x-filament-panels::page>
