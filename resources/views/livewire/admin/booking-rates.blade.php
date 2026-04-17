@php use App\Models\BookingFeeSetting; @endphp

<div class="mx-auto max-w-4xl space-y-8">
    <div>
        <h2 class="font-display text-lg font-bold uppercase tracking-wide text-zinc-900 dark:text-white">
            Convenience fee
        </h2>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
            Member checkout uses either a
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">fee on total court rental</span>
            (base amount plus a percentage of the courts subtotal, with an optional cap), or a
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">fixed amount per court hour</span>
            /
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">percentage of each booked court hour rate</span>.
            Add new rates over time; past rows stay in the history. Only
            <span class="font-semibold text-zinc-800 dark:text-zinc-200">one</span>
            row may be active — activating a row automatically deactivates the others.
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

    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Form</p>
                <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $this->formModeLabel }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="startNewRate"
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Add new rate
                </button>
                <button
                    type="button"
                    wire:click="editDefaultRate"
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                >
                    Edit active / latest
                </button>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            <fieldset class="space-y-3">
                <legend class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Fee basis</legend>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-transparent px-1 py-1 hover:border-zinc-200 dark:hover:border-zinc-700">
                        <input
                            type="radio"
                            wire:model.live="fee_basis"
                            value="{{ BookingFeeSetting::FEE_BASIS_SUBTOTAL }}"
                            class="mt-1 size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                        />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="font-semibold text-zinc-900 dark:text-white">Courts subtotal</span>
                            <span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                Base PHP amount + percentage of total court rental before coach add-ons.
                            </span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-transparent px-1 py-1 hover:border-zinc-200 dark:hover:border-zinc-700">
                        <input
                            type="radio"
                            wire:model.live="fee_basis"
                            value="{{ BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR }}"
                            class="mt-1 size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                        />
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                            <span class="font-semibold text-zinc-900 dark:text-white">Per court hour</span>
                            <span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">
                                Charge a flat PHP amount per booked court hour, or a percentage of each hour’s court rate.
                            </span>
                        </span>
                    </label>
                </div>
                @error('fee_basis')
                    <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </fieldset>

            @if ($fee_basis === BookingFeeSetting::FEE_BASIS_SUBTOTAL)
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
            @else
                <fieldset class="space-y-3">
                    <legend class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Per court hour type</legend>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                wire:model.live="per_court_hour_mode"
                                value="{{ BookingFeeSetting::PER_COURT_HOUR_FIXED }}"
                                class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                            />
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Fixed amount per hour</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                type="radio"
                                wire:model.live="per_court_hour_mode"
                                value="{{ BookingFeeSetting::PER_COURT_HOUR_PERCENT }}"
                                class="size-4 border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-950"
                            />
                            <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Percentage of each booked court hour rate</span>
                        </label>
                    </div>
                    @error('per_court_hour_mode')
                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </fieldset>

                @if ($per_court_hour_mode === BookingFeeSetting::PER_COURT_HOUR_FIXED)
                    <div>
                        <label for="br-ph-fixed" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            Fixed fee per court hour (PHP)
                        </label>
                        <input
                            id="br-ph-fixed"
                            type="text"
                            inputmode="decimal"
                            wire:model.live="per_court_hour_fixed"
                            class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('per_court_hour_fixed')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div>
                        <label for="br-ph-pct" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            Percentage of each booked court hour rental
                        </label>
                        <input
                            id="br-ph-pct"
                            type="text"
                            inputmode="decimal"
                            wire:model.live="per_court_hour_percent"
                            class="mt-1 w-full max-w-xs rounded-lg border border-zinc-200 px-3 py-2 font-mono text-sm tabular-nums dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            Example: <span class="font-mono">0.02</span> = 2% of each booked court hour rate (peak/off-peak aware).
                        </p>
                        @error('per_court_hour_percent')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            @endif

            <div>
                <label for="br-max" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    Maximum fee cap (PHP)
                </label>
                <input
                    id="br-max"
                    type="text"
                    inputmode="decimal"
                    wire:model.live="max_fee"
                    placeholder="No cap if empty (do not use 0)"
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
                    Active (used at checkout; saving as active turns every other row off)
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
                    Save rate
                </button>
            </div>
        </form>
    </div>

    <div>
        <h3 class="font-display text-sm font-bold uppercase tracking-wide text-zinc-700 dark:text-zinc-300">
            Rate history
        </h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Newest first. Use actions to switch which row is active without editing numbers.
        </p>
        @if ($this->allSettings->isEmpty())
            <p class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-zinc-50 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                No saved rates yet. Submit the form above to create the first row.
            </p>
        @else
            <div class="mt-3 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full min-w-[52rem] text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/80">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">ID</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Basis</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Base</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">%</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Per hr</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Max</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Active</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Created</th>
                            <th class="px-3 py-2 font-semibold text-zinc-700 dark:text-zinc-300">Updated</th>
                            <th class="px-3 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($this->allSettings as $row)
                            <tr
                                wire:key="booking-fee-{{ $row->id }}"
                                @class(['bg-emerald-50/50 dark:bg-emerald-950/20' => $row->is_active])
                            >
                                <td class="px-3 py-2 font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $row->id }}</td>
                                <td class="px-3 py-2 text-xs text-zinc-700 dark:text-zinc-300">
                                    @if (($row->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL) === BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR)
                                        Per court hour
                                    @else
                                        Courts subtotal
                                    @endif
                                </td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->base_fee }}</td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->percentage_fee }}</td>
                                <td class="px-3 py-2 text-xs tabular-nums text-zinc-700 dark:text-zinc-300">
                                    @if (($row->fee_basis ?? BookingFeeSetting::FEE_BASIS_SUBTOTAL) === BookingFeeSetting::FEE_BASIS_PER_COURT_HOUR)
                                        @if (($row->per_court_hour_mode ?? '') === BookingFeeSetting::PER_COURT_HOUR_PERCENT)
                                            {{ $row->per_court_hour_percent ?? '—' }}
                                        @else
                                            {{ $row->per_court_hour_fixed ?? '—' }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 tabular-nums">{{ $row->max_fee ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    @if ($row->is_active)
                                        <span class="font-semibold text-emerald-800 dark:text-emerald-200">Yes</span>
                                    @else
                                        <span class="text-zinc-500">No</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $row->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i a') ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-500">
                                    {{ $row->updated_at?->diffForHumans() ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex flex-wrap justify-end gap-1.5">
                                        <button
                                            type="button"
                                            wire:click="editRate({{ $row->id }})"
                                            class="rounded-md border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Edit
                                        </button>
                                        @unless ($row->is_active)
                                            <button
                                                type="button"
                                                wire:click="activateRate({{ $row->id }})"
                                                wire:loading.attr="disabled"
                                                class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                                            >
                                                Activate
                                            </button>
                                        @endunless
                                        @if ($row->is_active)
                                            <button
                                                type="button"
                                                wire:click="deactivateRate({{ $row->id }})"
                                                wire:loading.attr="disabled"
                                                class="rounded-md border border-amber-300 bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-950 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100 dark:hover:bg-amber-950/60"
                                            >
                                                Deactivate
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
