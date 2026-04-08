<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\InvoicePdfController;
use App\Http\Controllers\InternalTeamPlayReminderPreferencesController;
use App\Http\Controllers\OpenPlaySessionController;
use App\Http\Controllers\OpenPlayShareController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Admin\ActivityIndex;
use App\Livewire\Admin\AdminCourtChangeRequests;
use App\Livewire\Admin\BookingHistory;
use App\Livewire\Admin\BookingShow;
use App\Livewire\Admin\CoachBookingManagement;
use App\Livewire\Admin\CourtClientCreate;
use App\Livewire\Admin\CourtClientEdit;
use App\Livewire\Admin\CourtClientManualBooking;
use App\Livewire\Admin\GalleryImageApprovals;
use App\Livewire\Admin\GiftCardShow;
use App\Livewire\Admin\InternalTeamPlayReminders;
use App\Livewire\Admin\InvoiceCreate;
use App\Livewire\Admin\InvoiceIndex;
use App\Livewire\Admin\InvoiceShow;
use App\Livewire\Admin\ManualBookingHub;
use App\Livewire\Admin\UserForm;
use App\Livewire\Admin\UserSummary;
use App\Livewire\Admin\VenueQuickSetup;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\BookNow\VenueBookingPage;
use App\Livewire\BookNowPage;
use App\Livewire\Coach\CoachAvailability;
use App\Livewire\Coach\CoachCourtsManage;
use App\Livewire\Coach\CoachGiftCards;
use App\Livewire\Coach\CoachGiftCardShow;
use App\Livewire\Coach\CoachHome;
use App\Livewire\Coach\CoachProfileEdit;
use App\Livewire\ContactPage;
use App\Livewire\Desk\DeskCourtsLive;
use App\Livewire\Desk\DeskHome;
use App\Livewire\Desk\DeskManualBooking;
use App\Livewire\Desk\DeskMyRequests;
use App\Livewire\Member\MemberBookingHistory;
use App\Livewire\Member\MemberCourtOpenPlayHost;
use App\Livewire\Member\MemberCourtOpenPlayHub;
use App\Livewire\Member\MemberCourtOpenPlayJoin;
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
use App\Livewire\Venue\VenueCrmContact;
use App\Livewire\Venue\VenueCrmIndex;
use App\Livewire\Venue\VenueGiftCardShow;
use App\Livewire\Venue\VenueHome;
use App\Livewire\Venue\VenueManualBooking;
use App\Livewire\Venue\VenuePlan;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'home-page')->name('home');
Route::get('/about', function () {
    return redirect()->route('home')->withFragment('about');
})->name('about');
Route::get('/tools', function () {
    return redirect()->route('home')->withFragment('tools');
})->name('tools');
Route::livewire('/contact', ContactPage::class)->name('contact');
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

Route::get('/internal-team-play-reminders/unsubscribe/{user}', [InternalTeamPlayReminderPreferencesController::class, 'unsubscribe'])
    ->middleware(['signed', 'throttle:24,1'])
    ->name('internal-team-play-reminders.unsubscribe');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', 'login-page')->name('login');
    Route::livewire('/register', RegisterPage::class)->name('register');
    Route::livewire('/try', RegisterPage::class)->name('register.demo');
});

Route::middleware(['auth', 'demo.valid'])->post(
    '/internal-team-play-reminders/resubscribe',
    [InternalTeamPlayReminderPreferencesController::class, 'resubscribe'],
)->name('internal-team-play-reminders.resubscribe');

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
        Route::livewire('/court-open-plays', MemberCourtOpenPlayHub::class)->name('court-open-plays.index');
        Route::livewire('/court-open-plays/{booking}/host', MemberCourtOpenPlayHost::class)->name('court-open-plays.host');
        Route::livewire('/court-open-plays/{booking}/join', MemberCourtOpenPlayJoin::class)->name('court-open-plays.join');
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
        Route::livewire('/tools/gameq', OpenPlayOrganizer::class)->name('open-play');
        Route::get('/tools/pickle-game-q', fn () => redirect('/account/tools/gameq', 301));
        Route::get('/open-play', fn () => redirect()->route('account.open-play'))
            ->name('open-play.legacy');
        Route::livewire('/settings', MemberProfileSettings::class)->name('settings');
    });

    Route::middleware('coach')->prefix('account/coach')->name('account.coach.')->group(function (): void {
        Route::livewire('/', CoachHome::class)->name('dashboard');
        Route::livewire('/courts', CoachCourtsManage::class)->name('courts');
        Route::livewire('/availability', CoachAvailability::class)->name('availability');
        Route::livewire('/gift-cards', CoachGiftCards::class)->name('gift-cards.index');
        Route::livewire('/gift-cards/{giftCard}', CoachGiftCardShow::class)->name('gift-cards.show');
        Route::livewire('/profile', CoachProfileEdit::class)->name('profile');
    });

    Route::middleware('court_admin')->prefix('venue')->name('venue.')->group(function (): void {
        Route::livewire('/', VenueHome::class)->name('home');
        Route::livewire('/plan', VenuePlan::class)->name('plan');
        Route::livewire('/settings', VenueCourtClientManage::class)->name('settings');
        Route::livewire('/manual-booking', VenueManualBooking::class)->name('manual-booking');
        Route::livewire('/bookings/pending', VenueBookingApprovals::class)->name('bookings.pending');
        Route::livewire('/bookings/history', VenueBookingHistory::class)->name('bookings.history');
        Route::livewire('/bookings/{booking}', VenueBookingShow::class)->name('bookings.show');
        Route::livewire('/customers', VenueCrmIndex::class)->name('crm.index');
        Route::livewire('/customers/{user}/summary', UserSummary::class)->name('customers.summary');
        Route::livewire('/courts', VenueCourts::class)->name('courts');
        Route::livewire('/reports', 'venue-reports')->name('reports');
        Route::get('/reports/export/bookings', [ReportExportController::class, 'venueBookings'])
            ->name('reports.export.bookings');

        Route::middleware('venue_premium')->group(function (): void {
            Route::livewire('/customers/{contact}', VenueCrmContact::class)->name('crm.contacts.show');
            Route::livewire('/gift-cards', 'venue-gift-cards-index')->name('gift-cards.index');
            Route::livewire('/gift-cards/{giftCard}', VenueGiftCardShow::class)->name('gift-cards.show');
        });
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
        Route::livewire('/users/{user}/summary', UserSummary::class)->name('users.summary');
        Route::livewire('/users/{user}/edit', UserForm::class)->name('users.edit');
        Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'store'])
            ->name('users.impersonate');
        Route::livewire('/court-clients', 'admin-court-clients-index')->name('court-clients.index');
        Route::livewire('/court-clients/create', CourtClientCreate::class)->name('court-clients.create');
        Route::livewire('/venue-quick-setup', VenueQuickSetup::class)->name('venue-quick-setup');
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
        Route::livewire('/coach-bookings', CoachBookingManagement::class)->name('coach-bookings.index');
        Route::livewire('/bookings/{booking}', BookingShow::class)->name('bookings.show');
        Route::get('/reports/export/bookings', [ReportExportController::class, 'adminBookings'])
            ->name('reports.export.bookings');
        Route::livewire('/court-change-requests', AdminCourtChangeRequests::class)
            ->name('court-change-requests');
        Route::livewire('/activity', ActivityIndex::class)->name('activity.index');
        Route::livewire('/internal-play-reminders', InternalTeamPlayReminders::class)
            ->name('internal-play-reminders');
        Route::livewire('/gallery-approvals', GalleryImageApprovals::class)->name('gallery-approvals');
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
