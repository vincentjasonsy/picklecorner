@php
    use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

    $isSuperAdmin = auth()->user()?->isSuperAdmin() ?? false;
@endphp

<div class="rounded-lg border border-zinc-200 bg-zinc-50/50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
    <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-200">{{ $court->name }} — photos</p>
    <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
        Up to {{ $maxImages }} photos per court. Used on browse cards and the public court page after platform approval.
        Without approved uploads, a default illustration is shown.
    </p>
    @unless ($isSuperAdmin)
        <p class="mt-2 text-[11px] text-amber-800 dark:text-amber-200/90">
            New uploads stay <span class="font-semibold">pending</span> until a super admin approves them.
        </p>
    @endunless

    @if ($images->isNotEmpty())
        <ul class="mt-2 space-y-2">
            @foreach ($images as $img)
                <li
                    class="flex flex-wrap items-center gap-2 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-600 dark:bg-zinc-950"
                    wire:key="cg-{{ $img->id }}"
                >
                    <div class="h-12 w-16 shrink-0 overflow-hidden rounded border border-zinc-200 dark:border-zinc-600">
                        <img
                            src="{{ $img->publicUrl() }}"
                            alt=""
                            class="size-full object-cover object-center"
                            loading="lazy"
                        />
                    </div>
                    @if ($img->isApproved())
                        <span
                            class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200"
                        >
                            Live
                        </span>
                    @else
                        <span
                            class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-amber-950 dark:bg-amber-950/50 dark:text-amber-200"
                        >
                            Pending
                        </span>
                    @endif
                    <button
                        type="button"
                        wire:click="moveUp('{{ $img->id }}')"
                        class="rounded border border-zinc-200 px-2 py-0.5 text-[11px] font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-200"
                    >
                        Up
                    </button>
                    <button
                        type="button"
                        wire:click="moveDown('{{ $img->id }}')"
                        class="rounded border border-zinc-200 px-2 py-0.5 text-[11px] font-semibold text-zinc-700 dark:border-zinc-600 dark:text-zinc-200"
                    >
                        Down
                    </button>
                    <button
                        type="button"
                        wire:click="removeImage('{{ $img->id }}')"
                        wire:confirm="Remove this court photo?"
                        class="text-[11px] font-semibold text-red-600 dark:text-red-400"
                    >
                        Remove
                    </button>
                    @if ($isSuperAdmin && ! $img->isApproved())
                        <button
                            type="button"
                            wire:click="approveImage('{{ $img->id }}')"
                            class="rounded bg-emerald-600 px-2 py-0.5 text-[11px] font-bold text-white hover:bg-emerald-700"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            wire:click="rejectImage('{{ $img->id }}')"
                            wire:confirm="Reject and delete?"
                            class="text-[11px] font-bold text-red-600 dark:text-red-400"
                        >
                            Reject
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-2 space-y-1">
        @if ($galleryFull)
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                Max {{ $maxImages }} photos — remove one to add more.
            </p>
        @else
            <input
                type="file"
                wire:model="uploads"
                accept="image/jpeg,image/png,image/webp"
                multiple
                class="block w-full text-xs text-zinc-600 file:mr-2 file:rounded file:border-0 file:bg-teal-600 file:px-2 file:py-1 file:text-[11px] file:font-semibold file:text-white dark:text-zinc-400"
            />
        @endif
        @error('uploads')
            <p class="text-[11px] text-red-600">{{ $message }}</p>
        @enderror
        @error('uploads.*')
            <p class="text-[11px] text-red-600">{{ $message }}</p>
        @enderror
        @if (count($uploads) > 0)
            <ul class="mt-1 flex flex-wrap gap-1.5" aria-label="Selected files preview">
                @foreach ($uploads as $idx => $file)
                    @if ($file instanceof TemporaryUploadedFile && $file->isPreviewable())
                        <li
                            class="h-12 w-16 shrink-0 overflow-hidden rounded border border-zinc-200 dark:border-zinc-600"
                            wire:key="cg-upload-preview-{{ $idx }}-{{ $file->getFilename() }}"
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
                class="rounded bg-teal-700 px-2 py-1 text-[11px] font-bold uppercase tracking-wide text-white hover:bg-teal-800"
            >
                Upload
            </button>
        @endif
    </div>
</div>
