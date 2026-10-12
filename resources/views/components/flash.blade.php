@if (session('status') && session('status') !== 'verification-link-sent')
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" role="status"
         class="mb-4 rounded-lg border border-good bg-good-bg px-4 py-3 text-sm font-semibold text-good">{{ session('status') }}</div>
@endif
