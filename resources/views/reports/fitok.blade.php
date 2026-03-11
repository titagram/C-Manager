<x-layouts.app title="Registro FITOK">
    <x-page-header title="Registro FITOK" description="Tracciabilità materiali FITOK" />

    @if(session('success'))
        <div class="alert alert-success mb-6">
            {{ session('success') }}
        </div>
    @endif

    <livewire:reports.fitok-report />
</x-layouts.app>
