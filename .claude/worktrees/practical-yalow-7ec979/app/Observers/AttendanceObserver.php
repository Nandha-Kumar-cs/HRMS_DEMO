<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Services\CompOffService;

class AttendanceObserver
{
    /**
     * Auto-generate (or cancel) comp off credits whenever an attendance
     * record is created or updated.
     */
    public function saved(Attendance $attendance): void
    {
        app(CompOffService::class)->processAttendanceCredit($attendance);
    }
}
