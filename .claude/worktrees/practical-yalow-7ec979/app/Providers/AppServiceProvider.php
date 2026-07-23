<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Observers\AttendanceObserver;
use Illuminate\Pagination\Paginator;
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
        // Use Bootstrap 5 pagination views instead of the default Tailwind views.
        // Without this, pagination arrows render as unstyled SVGs (giant chevrons).
        Paginator::useBootstrapFive();

        // Auto-generate comp off credits when attendance is saved on a non-working day.
        Attendance::observe(AttendanceObserver::class);
    }
}
