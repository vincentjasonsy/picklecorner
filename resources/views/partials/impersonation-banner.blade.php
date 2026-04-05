@auth
    @if (session()->has('impersonator_id'))
        <div
            class="flex flex-wrap items-center justify-center gap-3 border-b border-amber-300 bg-amber-100 px-4 py-2.5 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-50"
            role="alert"
        >
            <span>
                You are signed in as <strong>{{ auth()->user()->name }}</strong> (impersonation).
            </span>
            <form method="POST" action="{{ route('admin.stop-impersonating') }}" class="inline">
                @csrf
                <button
                    type="submit"
                    class="font-display rounded-md bg-amber-900 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white hover:bg-amber-800 dark:bg-amber-600 dark:hover:bg-amber-500"
                >
                    Leave session
                </button>
            </form>
        </div>
    @endif
@endauth
