<?php

namespace App\Providers;

use App\Models\Booking;
use App\Observers\BookingObserver;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        Booking::observe(BookingObserver::class);

        View::composer('layouts::venue-portal', function (\Illuminate\View\View $view): void {
            $client = auth()->user()?->administeredCourtClient;
            $view->with('venueHasPremiumFeatures', $client?->hasPremiumSubscription() ?? false);
        });

        $livewireOverrides = resource_path('views/vendor/livewire');
        if (is_dir($livewireOverrides)) {
            View::prependNamespace('livewire', $livewireOverrides);
        }

        $this->ensureSqliteDatabaseFileExists();
    }

    /**
     * Create an empty SQLite file when using the file driver so migrate / artisan can run
     * before the file exists (e.g. fresh clone, Docker, CI).
     */
    private function ensureSqliteDatabaseFileExists(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $path = config('database.connections.sqlite.database');

        if (! is_string($path) || $path === '' || $path === ':memory:') {
            return;
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            $path = base_path($path);
        }

        if (! File::exists($path)) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, '');
        }
    }
}
