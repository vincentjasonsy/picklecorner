<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\BookingHistoryDateRange;
use App\Models\Booking;
use App\Models\CourtClient;
use App\Services\BookingCheckoutSnapshot;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Booking channels')]
class BookingChannelReport extends Component
{
    use BookingHistoryDateRange;
    use WithPagination;

    #[Url]
    public string $channelFilter = '';

    #[Url]
    public string $venue = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->maxHistoryRangeDays = 366;

        $this->initializeHistoryRangeDefaults();

        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }

        $this->normalizeChannelFilter();
    }

    public function updatingVenue(): void
    {
        if ($this->venue !== '' && ! CourtClient::query()->whereKey($this->venue)->exists()) {
            $this->venue = '';
        }
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->normalizeChannelFilter();
        $this->resetPage();
    }

    public function updatingFrom(): void
    {
        $this->resetPage();
    }

    public function updatingTo(): void
    {
        $this->resetPage();
    }

    protected function normalizeChannelFilter(): void
    {
        if (! in_array($this->channelFilter, ['', 'manual', 'automated'], true)) {
            $this->channelFilter = '';
        }
    }

    protected function baseQuery(): Builder
    {
        $tz = config('app.timezone', 'UTC');
        $rangeStart = Carbon::parse($this->from, $tz)->startOfDay();
        $rangeEnd = Carbon::parse($this->to, $tz)->endOfDay();

        $query = Booking::query()
            ->with(['courtClient:id,name,currency', 'court:id,name,court_client_id', 'user:id,name'])
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->countingTowardRevenue();

        if ($this->venue !== '') {
            $query->where('court_client_id', $this->venue);
        }

        if ($this->channelFilter === 'manual') {
            $query->where('checkout_snapshot->source', BookingCheckoutSnapshot::SOURCE_MANUAL_DESK);
        } elseif ($this->channelFilter === 'automated') {
            $query->where('checkout_snapshot->source', BookingCheckoutSnapshot::SOURCE_MEMBER_PUBLIC);
        }

        return $query;
    }

    /**
     * @return array{booking_count: int, amount_cents: int, platform_fee_cents: int, coach_fee_cents: int}
     */
    protected function aggregatesFor(Builder $query): array
    {
        return [
            'booking_count' => (clone $query)->count(),
            'amount_cents' => (int) (clone $query)->sum(DB::raw('coalesce(amount_cents, 0)')),
            'platform_fee_cents' => (int) (clone $query)->sum(DB::raw('coalesce(platform_booking_fee_cents, 0)')),
            'coach_fee_cents' => (int) (clone $query)->sum(DB::raw('coalesce(coach_fee_cents, 0)')),
        ];
    }

    protected function channelSlice(string $source): Builder
    {
        $q = $this->baseQueryForSlice();
        $q->where('checkout_snapshot->source', $source);

        return $q;
    }

    /** Same filters as {@see baseQuery()} but without channel filter — for summary cards. */
    protected function baseQueryForSlice(): Builder
    {
        $tz = config('app.timezone', 'UTC');
        $rangeStart = Carbon::parse($this->from, $tz)->startOfDay();
        $rangeEnd = Carbon::parse($this->to, $tz)->endOfDay();

        $query = Booking::query()
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->countingTowardRevenue();

        if ($this->venue !== '') {
            $query->where('court_client_id', $this->venue);
        }

        return $query;
    }

    public function render(): View
    {
        $tz = config('app.timezone', 'UTC');

        $tableQuery = $this->baseQuery();
        $footerTotals = $this->aggregatesFor($tableQuery);

        $manualSlice = $this->channelSlice(BookingCheckoutSnapshot::SOURCE_MANUAL_DESK);
        $automatedSlice = $this->channelSlice(BookingCheckoutSnapshot::SOURCE_MEMBER_PUBLIC);

        $manualSummary = $this->aggregatesFor($manualSlice);
        $automatedSummary = $this->aggregatesFor($automatedSlice);

        $bookings = (clone $tableQuery)
            ->orderBy('starts_at')
            ->paginate(25);

        $venues = CourtClient::query()->orderBy('name')->get(['id', 'name', 'currency']);

        $displayCurrency = $this->venue !== ''
            ? (CourtClient::query()->whereKey($this->venue)->value('currency') ?? 'PHP')
            : 'PHP';

        return view('livewire.admin.booking-channel-report', [
            'tz' => $tz,
            'bookings' => $bookings,
            'footerTotals' => $footerTotals,
            'manualSummary' => $manualSummary,
            'automatedSummary' => $automatedSummary,
            'venues' => $venues,
            'displayCurrency' => $displayCurrency,
        ]);
    }
}
