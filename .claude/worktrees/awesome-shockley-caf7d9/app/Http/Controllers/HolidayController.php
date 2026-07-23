<?php

namespace App\Http\Controllers;

use App\Helpers\WorkCalendar;
use App\Imports\HolidayImport;
use App\Models\CompOff;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\Holiday;
use App\Models\HolidayType;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $year     = (int) $request->get('year', now()->year);
        $holidays = Holiday::with('holidayType')
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get();

        $types = HolidayType::orderBy('name')->get();

        // DB records keyed by date string (includes Sat/Sun that were toggled)
        $dbDateMap  = $holidays->keyBy(fn($h) => $h->date->toDateString());

        // 1st & 3rd Saturday dates for the year
        $weeklyOffs    = WorkCalendar::getFirstAndThirdSaturdays($year);
        $weeklySatDates = collect($weeklyOffs)->keyBy('date'); // date => label

        $allDays   = collect();
        $processed = [];

        // Walk every day of the year
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd   = Carbon::create($year, 12, 31);

        for ($d = $yearStart->copy(); $d->lte($yearEnd); $d->addDay()) {
            $dateStr = $d->toDateString();
            $dbRow   = $dbDateMap[$dateStr] ?? null;

            $isSun = $d->isSunday();
            $isSat = isset($weeklySatDates[$dateStr]);

            if ($isSun) {
                $allDays->push([
                    'date'           => $d->copy(),
                    'name'           => 'Sunday',
                    'kind'           => 'sunday',
                    'type_label'     => 'Weekly Off',
                    'type_color'     => 'secondary',
                    'is_working_day' => $dbRow?->is_working_day ?? false,
                    'model'          => $dbRow,
                ]);
                $processed[] = $dateStr;
            } elseif ($isSat) {
                $allDays->push([
                    'date'           => $d->copy(),
                    'name'           => $weeklySatDates[$dateStr]['label'],
                    'kind'           => 'weekly_sat',
                    'type_label'     => 'Weekly Off',
                    'type_color'     => 'secondary',
                    'is_working_day' => $dbRow?->is_working_day ?? false,
                    'model'          => $dbRow,
                ]);
                $processed[] = $dateStr;
            } elseif ($dbRow) {
                $allDays->push([
                    'date'           => $dbRow->date,
                    'name'           => $dbRow->name,
                    'kind'           => 'holiday',
                    'type_label'     => $dbRow->holidayType?->name ?? null,
                    'type_color'     => $dbRow->holidayType?->color ?? 'secondary',
                    'is_working_day' => $dbRow->is_working_day,
                    'model'          => $dbRow,
                ]);
                $processed[] = $dateStr;
            }
        }

        // Dates whose comp offs have been availed — toggle should be locked for these
        $availedHolidayDates = \App\Models\CompOff::whereYear('holiday_date', $year)
            ->where('status', 'availed')
            ->pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->unique()
            ->flip()
            ->toArray(); // [date_string => index] for O(1) lookup

        $entities = Entity::orderBy('name')->get();

        return view('holidays.index', compact('holidays', 'allDays', 'year', 'types', 'availedHolidayDates', 'entities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'            => 'required|date|unique:holidays,date',
            'name'            => 'required|string|max:150',
            'holiday_type_id' => 'nullable|exists:holiday_types,id',
        ]);

        Holiday::create([
            'date'            => $request->date,
            'name'            => $request->name,
            'holiday_type_id' => $request->holiday_type_id,
        ]);

        return back()->with('success', 'Holiday "' . $request->name . '" added successfully.');
    }

    public function toggleWorkingDay(Request $request, Holiday $holiday)
    {
        $isNowWorking = !$holiday->is_working_day;

        $updates = ['is_working_day' => $isNowWorking];

        if ($isNowWorking) {
            $request->validate([
                'entity_id'          => 'required|exists:entities,id',
                'working_day_reason' => 'required|string|max:500',
            ]);
            $updates['entity_id']          = $request->entity_id;
            $updates['working_day_reason'] = $request->working_day_reason;
        } else {
            $updates['entity_id']          = null;
            $updates['working_day_reason'] = null;
        }

        $holiday->update($updates);

        if ($isNowWorking) {
            $employees = Employee::where('status', 'active')->get();
            foreach ($employees as $emp) {
                CompOff::updateOrCreate(
                    ['employee_id' => $emp->id, 'holiday_date' => $holiday->date->toDateString()],
                    ['holiday_name' => $holiday->name, 'status' => 'pending']
                );
            }
            $msg = "'{$holiday->name}' marked as working day. Comp off granted to {$employees->count()} employee(s).";
        } else {
            $msg = "'{$holiday->name}' restored as an off day.";
        }

        return back()->with('success', $msg)
                     ->with('circular_holiday_id', $isNowWorking ? $holiday->id : null);
    }

    /**
     * Toggle working-day status for a Saturday / Sunday (auto-generated rows).
     * Creates a Holiday record if one doesn't exist yet.
     */
    public function toggleDateWorkingDay(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:150',
        ]);

        $holiday = Holiday::firstOrCreate(
            ['date' => $request->date],
            ['name' => $request->name, 'is_working_day' => false]
        );

        $isNowWorking = !$holiday->is_working_day;
        $updates = ['is_working_day' => $isNowWorking];

        if ($isNowWorking) {
            $request->validate([
                'entity_id'          => 'required|exists:entities,id',
                'working_day_reason' => 'required|string|max:500',
            ]);
            $updates['entity_id']          = $request->entity_id;
            $updates['working_day_reason'] = $request->working_day_reason;
        } else {
            $updates['entity_id']          = null;
            $updates['working_day_reason'] = null;
        }

        $holiday->update($updates);

        if ($isNowWorking) {
            $employees = Employee::where('status', 'active')->get();
            foreach ($employees as $emp) {
                CompOff::updateOrCreate(
                    ['employee_id' => $emp->id, 'holiday_date' => $holiday->date->toDateString()],
                    ['holiday_name' => $holiday->name, 'status' => 'pending']
                );
            }
            $msg = "'{$holiday->name}' marked as working day. Comp off granted to {$employees->count()} employee(s).";
        } else {
            $msg = "'{$holiday->name}' restored as an off day.";
        }

        return back()->with('success', $msg)
                     ->with('circular_holiday_id', $isNowWorking ? $holiday->id : null);
    }

    /**
     * Generate and download the working-day circular PDF.
     */
    public function circular(Holiday $holiday)
    {
        abort_unless($holiday->is_working_day, 404, 'This holiday is not marked as a working day.');

        $holiday->load('entity');
        $entity = $holiday->entity ?? Entity::first();

        $employees = Employee::where('status', 'active')
            ->with(['designation', 'department'])
            ->orderBy('employee_code')
            ->get();

        $pdf = Pdf::loadView('holidays.circular', compact('holiday', 'entity', 'employees'))
            ->setPaper('a4', 'portrait');

        $filename = 'Circular_' . $holiday->date->format('d-M-Y') . '_' . str_replace(' ', '_', $holiday->name) . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'year'            => 'required|integer|min:2000|max:2099',
            'holiday_type_id' => 'nullable|exists:holiday_types,id',
        ]);

        $year   = (int) $request->year;
        $typeId = $request->holiday_type_id;
        $import = new HolidayImport($year, $typeId);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('import_error', 'Import failed: ' . $e->getMessage());
        }

        $msg = "{$import->imported} holiday(s) imported for {$year}.";
        if ($import->skipped) {
            $msg .= " {$import->skipped} row(s) skipped.";
        }

        return back()
            ->with('success', $msg)
            ->with('import_errors', $import->errors);
    }
}
