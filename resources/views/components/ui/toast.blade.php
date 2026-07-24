<div
    aria-live="assertive"
    class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6"
>
    <div id="toast-container" class="flex w-full flex-col items-center space-y-4 sm:items-end"></div>
</div>

@if(session('status') || session('success') || session('error') || session('info'))
    @php
        $flash = session('status') ?? session('success') ?? session('error') ?? session('info');
        $flashType = session('error') ? 'error' : (session('info') ? 'info' : 'success');
    @endphp
    <meta name="flash-message" content="{{ $flash }}" data-flash-type="{{ $flashType }}">
@endif
