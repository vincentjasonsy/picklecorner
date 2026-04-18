<div class="relative" x-data x-on:keydown.escape.window="$wire.close()">
    <button
        type="button"
        wire:click="toggle"
        class="relative inline-flex size-10 items-center justify-center rounded-lg border border-zinc-200 text-zinc-600 transition-colors hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
        aria-label="Notifications"
        :aria-expanded="$wire.open ? 'true' : 'false'"
        aria-haspopup="true"
    >
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75v-.102V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
            />
        </svg>
        @if ($unreadCount > 0)
            <span
                class="absolute -right-0.5 -top-0.5 flex min-w-5 justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-bold leading-5 text-white dark:bg-emerald-500"
            >
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div
            class="absolute right-0 z-50 mt-2 w-[min(100vw-2rem,22rem)] origin-top-right rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900"
            role="dialog"
            aria-label="Notification list"
            wire:click.outside="close"
        >
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white">Notifications</p>
                @if ($unreadCount > 0)
                    <button
                        type="button"
                        wire:click="markAllRead"
                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300"
                    >
                        Mark all read
                    </button>
                @endif
            </div>
            <ul class="max-h-[min(70vh,24rem)] divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800">
                @forelse ($notifications as $n)
                    @php($d = $n->data)
                    <li wire:key="{{ $n->id }}">
                        @if (($d['kind'] ?? '') === 'member_venue_booking')
                            <div
                                class="flex gap-3 px-4 py-3 {{ $n->read_at ? 'bg-white dark:bg-zinc-900' : 'bg-emerald-50/80 dark:bg-emerald-950/25' }}"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                        {{ $d['status_label'] ?? 'Booking' }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $d['title'] ?? 'Booking update' }}
                                    </p>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                                        {{ $d['body'] ?? '' }}
                                    </p>
                                    @if (! empty($d['lines']))
                                        <ul class="mt-2 space-y-1 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                                            @foreach ($d['lines'] as $line)
                                                <li class="text-xs text-zinc-700 dark:text-zinc-300">
                                                    <span class="font-medium">{{ $line['court'] ?? 'Court' }}</span>
                                                    @if (! empty($line['when']))
                                                        <span class="text-zinc-500"> · {{ $line['when'] }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (! empty($d['booking_url']))
                                        <div class="mt-3">
                                            <button
                                                type="button"
                                                wire:click="markReadAndGoUrl('{{ $n->id }}', {{ json_encode($d['booking_url']) }})"
                                                class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                                            >
                                                View booking
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @elseif (($d['kind'] ?? '') === 'internal_team_play_reminder')
                            <div
                                class="flex gap-3 px-4 py-3 {{ $n->read_at ? 'bg-white dark:bg-zinc-900' : 'bg-emerald-50/80 dark:bg-emerald-950/25' }}"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                                        Court reminder
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $d['title'] ?? 'Reminder' }}
                                    </p>
                                    <p class="mt-1 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">
                                        {{ $d['body'] ?? '' }}
                                    </p>
                                    @if (! empty($d['courts']))
                                        <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-500">
                                            Courts you can book
                                        </p>
                                        <ul class="mt-1 space-y-1">
                                            @foreach (array_slice($d['courts'], 0, 4) as $court)
                                                <li class="text-xs text-zinc-700 dark:text-zinc-300">
                                                    <span class="font-medium">{{ $court['court_name'] ?? 'Court' }}</span>
                                                    <span class="text-zinc-500"> · {{ $court['venue_name'] ?? '' }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if (! empty($d['tips']))
                                        <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-amber-800/90 dark:text-amber-400/90">
                                            Suggestions
                                        </p>
                                        <ul class="mt-1 list-disc space-y-1 pl-4 text-xs text-zinc-600 dark:text-zinc-400">
                                            @foreach (array_slice($d['tips'], 0, 2) as $tip)
                                                <li>{{ $tip }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="markReadAndGoRoute('{{ $n->id }}', 'account.book')"
                                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600"
                                        >
                                            Book now
                                        </button>
                                        @if (! empty($d['browse_url']))
                                            <button
                                                type="button"
                                                wire:click="markReadAndGoRoute('{{ $n->id }}', 'book-now')"
                                                class="inline-flex items-center rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                            >
                                                Browse courts
                                            </button>
                                        @endif
                                    </div>
                                    @if (! empty($d['unsubscribe_url']))
                                        <p class="mt-3 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                            <a
                                                href="{{ $d['unsubscribe_url'] }}"
                                                class="text-[11px] font-semibold text-zinc-500 underline decoration-zinc-400/70 underline-offset-2 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-300"
                                            >
                                                Unsubscribe from booking reminders
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $d['title'] ?? class_basename($n->type) }}
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-zinc-500">You’re all caught up.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
