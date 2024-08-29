<!doctype html>
<html lang="{{ str_replace('_', '-', config('app.locale')) }}" data-bs-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }} {{ empty($title) ? '' : " | $title" }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

        @production
            @php
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            @endphp

            <link rel="stylesheet" href="/build/{{ $manifest['resources/sass/app.scss']['file'] }}">
        @else
            @vite(['resources/sass/app.scss'])
        @endproduction

        <livewire:styles />
    </head>
    <body style="padding-top:80px;background-color:transparent;">
        {{ $slot }}

        <div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

        <div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 id="deleteModalLabel" class="modal-title fs-5">{{ __('Conferma Eliminazione') }}</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Annulla') }}</button>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">{{ __('Conferma') }}</button>
                    </div>
                </div>
            </div>
        </div>
        
        <livewire:scripts />

        @production
            <script type="module" src="/build/{{ $manifest['resources/js/app.js']['file'] }}"></script>
        @else
            @vite(['resources/js/app.js'])
        @endproduction

        <script>
            document.addEventListener('livewire:init', (event) => {
                Livewire.on('toast-message', (event) => {
                    utils.showToast(event[0].type, event[0].message);
                });

                @if (session()->has('toastType') && session()->has('toastMessage')) 
                    utils.showToast('{{ session()->get('toastType') }}', '{{ session()->get('toastMessage')}}');
                @endif
            });
        </script>

        {{ $pagejs ?? '' }}
    </body>
</html>
