<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\InvoicePdfController;
use App\Http\Controllers\OpenPlaySessionController;
use App\Http\Controllers\OpenPlayShareController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Admin\ActivityIndex;
use App\Livewire\Admin\AdminCourtChangeRequests;
use App\Livewire\Admin\BookingHistory;
use App\Livewire\Admin\BookingShow;
use App\Livewire\Admin\CourtClientEdit;
use App\Livewire\Admin\CourtClientManualBooking;
use App\Livewire\Admin\GiftCardShow;
use App\Livewire\Admin\InvoiceCreate;
use App\Livewire\Admin\InvoiceIndex;
use App\Livewire\Admin\InvoiceShow;
use App\Livewire\Admin\ManualBookingHub;
use App\Livewire\Admin\UserForm;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\BookNow\VenueBookingPage;
use App\Livewire\BookNowPage;
use App\Livewire\Desk\DeskCourtsLive;
use App\Livewire\Desk\DeskHome;
use App\Livewire\Desk\DeskManualBooking;
use App\Livewire\Desk\DeskMyRequests;
use App\Livewire\Member\MemberBookingHistory;
use App\Livewire\Member\MemberDashboard;
use App\Livewire\Member\MemberProfileSettings;
use App\Livewire\OpenPlayAbout;
use App\Livewire\OpenPlayOrganizer;
use App\Livewire\OpenPlayWatch;
use App\Livewire\PublicCourtShow;
use App\Livewire\Venue\VenueBookingApprovals;
use App\Livewire\Venue\VenueBookingHistory;
use App\Livewire\Venue\VenueBookingShow;
use App\Livewire\Venue\VenueCourtClientManage;
use App\Livewire\Venue\VenueCourts;
use App\Livewire\Venue\VenueGiftCardShow;
use App\Livewire\Venue\VenueHome;
use App\Livewire\Venue\VenueManualBooking;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'home-page')->name('home');
Route::get('/about', function () {
    return redirect()->route('home')->withFragment('about');
})->name('about');
Route::livewire('/book-now', BookNowPage::class)->name('book-now');
Route::livewire('/book-now/courts/{court}', PublicCourtShow::class)->name('book-now.court');
Route::livewire('/book-now/venues/{courtClient:slug}/book', VenueBookingPage::class)->name('book-now.venue.book');
Route::get('/open-play/watch/{openPlayShare}/data', [OpenPlayShareController::class, 'data'])
    ->middleware('throttle:180,1')
    ->name('open-play.watch.data');
Route::livewire('/open-play/watch/{openPlayShare}', OpenPlayWatch::class)
    ->middleware('throttle:120,1')
    ->name('open-play.watch');
Route::livewire('/open-play', OpenPlayAbout::class)->name('open-play.about');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', 'login-page')->name('login');
    Route::livewire('/register', RegisterPage::class)->name('register');
    Route::livewire('/try', RegisterPage::class)->name('register.demo');
});

Route::middleware(['auth', 'demo.valid'])->group(function (): void {
    Route::post('/open-play/share', [OpenPlayShareController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('open-play.share.store');
    Route::put('/open-play/share/{openPlayShare}', [OpenPlayShareController::class, 'update'])
        ->middleware('throttle:90,1')
        ->name('open-play.share.update');
    Route::delete('/open-play/share/{openPlayShare}', [OpenPlayShareController::class, 'destroy'])
        ->middleware('throttle:12,1')
        ->name('open-play.share.destroy');

    Route::prefix('account')->name('account.')->group(function (): void {
        Route::livewire('/', MemberDashboard::class)->name('dashboard');
        Route::livewire('/book', BookNowPage::class)->name('book');
        Route::livewire('/book/venues/{courtClient:slug}', VenueBookingPage::class)->name('book.venue');
        Route::livewire('/bookings', MemberBookingHistory::class)->name('bookings');
        Route::get('/open-play/sessions', [OpenPlaySessionController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('open-play.sessions.index');
        Route::post('/open-play/sessions', [OpenPlaySessionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('open-play.sessions.store');
        Route::get('/open-play/sessions/{openPlaySession}', [OpenPlaySessionController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('open-play.sessions.show');
        Route::patch('/open-play/sessions/{openPlaySession}', [OpenPlaySessionController::class, 'update'])
            ->middleware('throttle:30,1')
            ->name('open-play.sessions.update');
        Route::delete('/open-play/sessions/{openPlaySession}', [OpenPlaySessionController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('open-play.sessions.destroy');
        Route::livewire('/tools/pickle-game-q', OpenPlayOrganizer::class)->name('open-play');
        Route::get('/open-play', fn () => redirect()->route('account.open-play'))
            ->name('open-play.legacy');
        Route::livewire('/settings', MemberProfileSettings::class)->name('settings');
    });

    Route::middleware('court_admin')->prefix('venue')->name('venue.')->group(function (): void {
        Route::livewire('/', VenueHome::class)->name('home');
        Route::livewire('/settings', VenueCourtClientManage::class)->name('settings');
        Route::livewire('/manual-booking', VenueManualBooking::class)->name('manual-booking');
        Route::livewire('/bookings/pending', VenueBookingApprovals::class)->name('bookings.pending');
        Route::livewire('/bookings/history', VenueBookingHistory::class)->name('bookings.history');
        Route::livewire('/bookings/{booking}', VenueBookingShow::class)->name('bookings.show');
        Route::livewire('/courts', VenueCourts::class)->name('courts');
        Route::livewire('/gift-cards', 'venue-gift-cards-index')->name('gift-cards.index');
        Route::livewire('/gift-cards/{giftCard}', VenueGiftCardShow::class)->name('gift-cards.show');
        Route::livewire('/reports', 'venue-reports')->name('reports');
        Route::get('/reports/export/bookings', [ReportExportController::class, 'venueBookings'])
            ->name('reports.export.bookings');
    });

    Route::middleware('court_client_desk')->prefix('desk')->name('desk.')->group(function (): void {
        Route::livewire('/', DeskHome::class)->name('home');
        Route::livewire('/courts-live', DeskCourtsLive::class)->name('courts-live');
        Route::livewire('/booking-request', DeskManualBooking::class)->name('booking-request');
        Route::livewire('/my-requests', DeskMyRequests::class)->name('my-requests');
    });

    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'destroy'])
        ->name('admin.stop-impersonating');
});

Route::middleware(['auth', 'demo.valid', 'super_admin', 'admin_not_impersonating'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::livewire('/', 'admin-dashboard')->name('dashboard');
        Route::livewire('/users', 'admin-users-index')->name('users.index');
        Route::livewire('/users/create', UserForm::class)->name('users.create');
        Route::livewire('/users/{user}/edit', UserForm::class)->name('users.edit');
        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'store'])
            ->name('users.impersonate');
        Route::livewire('/court-clients', 'admin-court-clients-index')->name('court-clients.index');
        Route::livewire('/court-clients/{courtClient}/edit', CourtClientEdit::class)
            ->name('court-clients.edit');
        Route::livewire('/court-clients/{courtClient}/manual-booking', CourtClientManualBooking::class)
            ->name('court-clients.manual-booking');
        Route::livewire('/manual-booking', ManualBookingHub::class)->name('manual-booking.hub');
        Route::livewire('/gift-cards', 'admin-gift-cards-index')->name('gift-cards.index');
        Route::livewire('/gift-cards/{giftCard}', GiftCardShow::class)->name('gift-cards.show');
        Route::livewire('/invoices', InvoiceIndex::class)->name('invoices.index');
        Route::livewire('/invoices/create', InvoiceCreate::class)->name('invoices.create');
        Route::livewire('/invoices/{invoice}', InvoiceShow::class)->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->name('invoices.pdf');
        Route::livewire('/reports', 'admin-reports')->name('reports');
        Route::livewire('/bookings', BookingHistory::class)->name('bookings.index');
        Route::livewire('/bookings/{booking}', BookingShow::class)->name('bookings.show');
        Route::get('/reports/export/bookings', [ReportExportController::class, 'adminBookings'])
            ->name('reports.export.bookings');
        Route::livewire('/court-change-requests', AdminCourtChangeRequests::class)
            ->name('court-change-requests');
        Route::livewire('/activity', ActivityIndex::class)->name('activity.index');
    });

Route::post('/logout', function () {
    if (Auth::check()) {
        ActivityLogger::log('auth.logout', ['email' => Auth::user()->email], Auth::user(), 'Signed out');
    }

    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware(['auth', 'demo.valid'])->name('logout');
