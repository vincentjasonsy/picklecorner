@if (session('status'))
    <div
        class="border-b border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100"
        role="status"
    >
        {{ session('status') }}
    </div>
@endif

@if (session('warning'))
    <div
        class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100"
        role="alert"
    >
        {{ session('warning') }}
    </div>
@endif
