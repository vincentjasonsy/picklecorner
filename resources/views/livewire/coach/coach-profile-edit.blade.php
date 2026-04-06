<div class="space-y-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-zinc-900 dark:text-white">Coach profile</h1>
        <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
            Your hourly coaching rate and bio appear to players when they review a booking that includes you.
        </p>
    </div>

    <form wire:submit="save" class="max-w-lg space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900/80">
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" for="coach-rate">
                Hourly rate (whole pesos)
            </label>
            <input
                id="coach-rate"
                type="number"
                min="0"
                max="500000"
                wire:model="hourlyRatePesos"
                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            />
            @error('hourlyRatePesos')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" for="coach-ccy">
                Currency
            </label>
            <select
                id="coach-ccy"
                wire:model="currency"
                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
            >
                <option value="PHP">PHP</option>
                <option value="USD">USD</option>
            </select>
            @error('currency')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" for="coach-bio">
                Bio (optional)
            </label>
            <textarea
                id="coach-bio"
                wire:model="bio"
                rows="4"
                class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                placeholder="Certifications, playing style, what you focus on in lessons…"
            ></textarea>
            @error('bio')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <button
                type="submit"
                class="inline-flex rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-violet-500 dark:bg-violet-500 dark:hover:bg-violet-400"
            >
                Save
            </button>
        </div>
    </form>
</div>
