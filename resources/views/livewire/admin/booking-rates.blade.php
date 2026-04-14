<div class="mx-auto max-w-2xl space-y-8">
    <div>
        <h2 class="font-display text-lg font-bold uppercase tracking-wide text-zinc-900 dark:text-white">
            Platform booking service fee
        </h2>
        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
            Member checkout uses:
            <span class="font-mono font-semibold text-zinc-800 dark:text-zinc-200">fee = base + (courts subtotal × percentage)</span>,
            capped when a maximum is set. Only one configuration can be active; saving as active turns others off.
        </p>
    </div>

    @if (session('status'))
        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100"
            role="status"
        >
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label for="br-base" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                Base fee (PHP)
            </label>
            <input
                id="br-base"
                type="text"
                inputmode="decimal"
                wire:model.live="base_fee"
                class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
            />
            @error('base_fee')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="br-pct" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                Percentage fee
            </label>
            <input
                id="br-pct"
                type="text"
                inputmode="decimal"
                wire:model.live="percentage_fee"
                class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
            />
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                Example: <span class="font-mono">0.02</span> = 2% of courts subtotal.
            </p>
            @error('percentage_fee')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="br-max" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                Maximum fee cap (PHP)
            </label>
            <input
                id="br-max"
                type="text"
                inputmode="decimal"
                wire:model.live="max_fee"
                placeholder="No cap if empty"
                class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
            />
            @error('max_fee')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <input
                id="br-active"
                type="checkbox"
                wire:model="is_active"
                class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
            />
            <label for="br-active" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                Active (used at checkout when set; only one row may be active)
            </label>
        </div>

        <div class="rounded-lg border border-zinc-100 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-800 dark:bg-zinc-950/60">
            <p class="font-medium text-zinc-700 dark:text-zinc-300">Checkout preview</p>
            <p class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ $this->previewBreakdown }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500"
            >
                Save rates
            </button>
        </div>
    </form>

    @if ($this->allSettings->isNotEmpty())
        <div>
            <h3 class="font-display text-sm font-bold uppercase tracking-wide text-zinc-700 dark:text-zinc-300">
                Saved rows
            </h3>
            <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full min-w-[28rem] text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/80">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">ID</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Base</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">%</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Max</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Active</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->allSettings as $row)
                            <tr @class(['bg-emerald-50/50 dark:bg-emerald-950/20' => $row->is_active])>
                                <td class="px-3 py-2 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $row->id }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->base_fee }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->percentage_fee }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->max_fee ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row->is_active ? 'Yes' : 'No' }}</td>
                                <td class="px-3 py-2 text-xs text-zinc-500">{{ $row->updated_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
