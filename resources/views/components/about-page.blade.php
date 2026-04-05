<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::guest'), Title('About')] class extends Component {};
?>

<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
        About
    </h1>
    <p class="mt-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
        This is a placeholder for your marketing copy. Guest pages use the guest layout with
        <code class="rounded bg-zinc-200/80 px-1 py-0.5 text-xs dark:bg-zinc-800">wire:navigate</code>
        so Home and About feel closer to a single-page app.
    </p>
</div>
