@php
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
@endphp

<div class="space-y-4">
    <div>
        <h3 class="font-display text-base font-bold text-zinc-900 dark:text-white">Venue photos (carousel)</h3>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            Up to {{ $maxImages }} photos. Shown on your public booking page as a carousel when you add more than one.
            Order: first listed shows first. If the gallery is empty, a legacy single cover image is used when set on the
            venue.
        </p>
        @unless ($isSuperAdmin)
            <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50/90 px-3 py-2 text-xs text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                New uploads are reviewed by platform admin before they appear publicly (usually quick). You’ll see
                <span class="font-semibold">Pending</span> until approved.
            </p>
        @endunless
    </div>

    @if ($images->isNotEmpty())
        <ul class="space-y-2">
            @foreach ($images as $img)
                <li
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50/80 p-2 dark:border-zinc-700 dark:bg-zinc-900/50"
                    wire:key="vcg-{{ $img->id }}"
                >
                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-600">
                        <img
                            src="{{ $img->publicUrl() }}"
                            alt=""
                            class="size-full object-cover object-center"
                            loading="lazy"
                        />
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($img->isApproved())
                                <span
                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                                >
                                    Live
                                </span>
                            @else
                                <span
                                    class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-950 dark:bg-amber-950/50 dark:text-amber-200"
                                >
                                    Pending review
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="moveUp('{{ $img->id }}')"
                                class="rounded border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Up
                            </button>
                            <button
                                type="button"
                                wire:click="moveDown('{{ $img->id }}')"
                                class="rounded border border-zinc-200 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-white dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Down
                            </button>
                            <button
                                type="button"
                                wire:click="removeImage('{{ $img->id }}')"
                                wire:confirm="Remove this photo from the gallery?"
                                class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                Remove
                            </button>
                            @if ($isSuperAdmin && ! $img->isApproved())
                                <button
                                    type="button"
                                    wire:click="approveImage('{{ $img->id }}')"
                                    class="rounded bg-emerald-600 px-2 py-1 text-xs font-bold text-white hover:bg-emerald-700"
                                >
                                    Approve
                                </button>
                                <button
                                    type="button"
                                    wire:click="rejectImage('{{ $img->id }}')"
                                    wire:confirm="Reject and delete this image?"
                                    class="text-xs font-bold text-red-600 hover:text-red-800 dark:text-red-400"
                                >
                                    Reject
                                </button>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            No gallery photos yet.
            @if ($legacyCover)
                <span class="font-medium text-zinc-700 dark:text-zinc-300">A single cover image is still set.</span>
            @endif
        </p>
    @endif

    <div class="space-y-2">
        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
            Add images
        </label>
        @if ($galleryFull)
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Maximum {{ $maxImages }} photos reached — remove one to upload more.
            </p>
        @else
            <input
                type="file"
                wire:model="uploads"
                accept="image/jpeg,image/png,image/webp"
                multiple
                class="block w-full max-w-md text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-emerald-700 dark:text-zinc-400 dark:file:bg-emerald-700"
            />
        @endif
        @error('uploads')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('uploads.*')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <div wire:loading wire:target="uploads" class="text-xs text-zinc-500">Preparing…</div>
        @if (count($uploads) > 0)
            <ul class="flex flex-wrap gap-2" aria-label="Selected files preview">
                @foreach ($uploads as $idx => $file)
                    @if ($file instanceof TemporaryUploadedFile && $file->isPreviewable())
                        <li
                            class="h-16 w-24 shrink-0 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-600"
                            wire:key="vcg-upload-preview-{{ $idx }}-{{ $file->getFilename() }}"
                        >
                            <img
                                src="{{ $file->temporaryUrl() }}"
                                alt=""
                                class="size-full object-cover object-center"
                            />
                        </li>
                    @endif
                @endforeach
            </ul>
            <button
                type="button"
                wire:click="saveUploads"
                wire:loading.attr="disabled"
                class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-bold uppercase tracking-wide text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
            >
                Upload
            </button>
        @endif
    </div>
</div>
