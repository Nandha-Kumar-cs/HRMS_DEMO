@extends('layouts.app')
@section('title', 'Holiday Management')
@section('breadcrumb')
    <li class="breadcrumb-item active">Holidays</li>
@endsection

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3">
    <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
    @if(session('import_errors'))
    <ul class="mb-0 mt-1 small">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('import_error'))
<div class="alert alert-danger alert-dismissible fade show mb-3">
    <i class="fa fa-exclamation-triangle me-2"></i>{{ session('import_error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- Left: Holiday list --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar-day me-2 text-danger"></i>Public Holidays</h5>
                <div class="d-flex gap-2 align-items-center">
                    {{-- Year picker --}}
                    <form method="GET" class="d-flex gap-2">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>
                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fa fa-file-excel me-1"></i>Import Excel
                    </button>
                </div>
            </div>

            {{-- 1st & 3rd Saturday info box --}}
            <div class="card-body pb-0">
                <div class="alert alert-info border-info d-flex align-items-start gap-2 mb-3 py-2">
                    <i class="fa fa-circle-info mt-1 text-info"></i>
                    <div class="small">
                        <strong>Automatic Weekly Offs:</strong> The 1st and 3rd Saturday of every month are automatically treated as holidays in attendance and salary calculation. These are listed below for reference.
                    </div>
                </div>
            </div>

            {{-- 1st & 3rd Saturday Reference --}}
            @if(!empty($weeklyOffs))
            <div class="card-body pt-0 pb-2">
                <div class="fw-semibold small text-muted mb-2"><i class="fa fa-calendar-week me-1"></i>1st & 3rd Saturdays — {{ $year }}</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($weeklyOffs as $sat)
                    <span class="badge bg-secondary py-2 px-3" style="font-size:.78rem">
                        <i class="fa fa-moon me-1"></i>{{ \Carbon\Carbon::parse($sat['date'])->format('d M') }} — {{ $sat['label'] }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Holiday table --}}
            <div class="card-body">
                @if($holidays->isEmpty())
                <p class="text-muted text-center py-4"><i class="fa fa-calendar-xmark fa-2x d-block mb-2 opacity-25"></i>No holidays added for {{ $year }}. Use the form on the right to add one.</p>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0" style="font-size:.9rem">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Holiday Name</th>
                                <th>Type</th>
                                <th style="width:80px" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($holidays as $i => $holiday)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $holiday->date->format('d M Y') }}</td>
                                <td>{{ $holiday->date->format('l') }}</td>
                                <td>{{ $holiday->name }}</td>
                                <td>
                                    @if($holiday->holidayType)
                                        <span class="badge bg-{{ $holiday->holidayType->color }}">{{ $holiday->holidayType->name }}</span>
                                    @else
                                        <span class="badge bg-light text-dark">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('holidays.destroy', $holiday) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Remove {{ $holiday->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="6" class="small text-muted">
                                    {{ $holidays->count() }} holiday(s) in {{ $year }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right: Add holiday form --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-plus-circle me-2 text-success"></i>Add Holiday Manually</h6>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger small py-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('holidays.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                               value="{{ old('date') }}" required>
                        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="e.g. Republic Day"
                               value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                            <span>Type</span>
                            @if(auth()->user()?->role === 'admin')
                            <a href="{{ route('holiday-types.index') }}" class="small text-decoration-none">
                                <i class="fa fa-cog"></i> Manage Types
                            </a>
                            @endif
                        </label>
                        <select name="holiday_type_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" {{ old('holiday_type_id') == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-plus me-1"></i>Add Holiday
                    </button>
                </form>
            </div>
        </div>

        {{-- Quick add common holidays --}}
        <div class="card page-card mt-3">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold"><i class="fa fa-bolt me-2 text-warning"></i>Quick Add — National Holidays {{ $year }}</h6>
            </div>
            <div class="card-body p-2">
                @php
                $national = [
                    ['date' => $year.'-01-26', 'name' => 'Republic Day'],
                    ['date' => $year.'-08-15', 'name' => 'Independence Day'],
                    ['date' => $year.'-10-02', 'name' => 'Gandhi Jayanti'],
                    ['date' => $year.'-12-25', 'name' => 'Christmas'],
                ];
                $existingDates = $holidays->pluck('date')->map(fn($d) => $d->toDateString())->toArray();
                $nationalTypeId = $types->firstWhere('name', 'National')?->id ?? $types->firstWhere('name', 'Public')?->id;
                @endphp
                @foreach($national as $h)
                    @if(!in_array($h['date'], $existingDates))
                    <form action="{{ route('holidays.store') }}" method="POST" class="d-flex align-items-center gap-2 p-2 border-bottom">
                        @csrf
                        <input type="hidden" name="date" value="{{ $h['date'] }}">
                        <input type="hidden" name="name" value="{{ $h['name'] }}">
                        <input type="hidden" name="holiday_type_id" value="{{ $nationalTypeId }}">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold">{{ $h['name'] }}</div>
                            <div class="text-muted">{{ \Carbon\Carbon::parse($h['date'])->format('d M Y') }}</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-success" title="Add">
                            <i class="fa fa-plus"></i>
                        </button>
                    </form>
                    @else
                    <div class="d-flex align-items-center gap-2 p-2 border-bottom text-muted">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold">{{ $h['name'] }}</div>
                            <div>{{ \Carbon\Carbon::parse($h['date'])->format('d M Y') }}</div>
                        </div>
                        <span class="badge bg-success"><i class="fa fa-check"></i></span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Import Excel Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold">
                    <i class="fa fa-file-excel me-2 text-success"></i>Import Holidays from Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold mb-2"><i class="fa fa-info-circle text-primary me-1"></i>Excel File Format:</div>
                    <ul class="small mb-2">
                        <li>Column A — <strong>Month</strong> (number: 1–12)</li>
                        <li>Column B — <strong>Date / Day</strong> (number: 1–31)</li>
                        <li>Column C — <strong>Holiday Name</strong> (optional; defaults to "Holiday")</li>
                        <li>First row can be a header — it will be skipped automatically if non-numeric.</li>
                    </ul>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-0 small" style="width:auto">
                            <thead class="table-dark"><tr><th>Month</th><th>Date</th><th>Name</th></tr></thead>
                            <tbody>
                                <tr><td>1</td><td>26</td><td>Republic Day</td></tr>
                                <tr><td>8</td><td>15</td><td>Independence Day</td></tr>
                                <tr><td>10</td><td>2</td><td>Gandhi Jayanti</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form action="{{ route('holidays.import') }}" method="POST" enctype="multipart/form-data" id="holidayImportForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select">
                                @for($y = now()->year + 1; $y >= now()->year - 2; $y--)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Apply Type</label>
                            <select name="holiday_type_id" class="form-select">
                                <option value="">— Default (Public) —</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required id="holidayFile">
                            <div class="form-text">Accepted: .xlsx, .xls, .csv — Max 5 MB</div>
                        </div>
                    </div>
                    <div id="holidayPreview" class="d-none alert alert-secondary small py-2 mt-2">
                        <i class="fa fa-file-excel text-success me-1"></i><span id="holidayFileName"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="holidayImportForm" class="btn btn-success" id="btnHolidayImport">
                    <i class="fa fa-upload me-1"></i>Import Holidays
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function(){
    $('#holidayFile').on('change', function(){
        var f = this.files[0];
        if (f) {
            $('#holidayFileName').text(f.name + ' (' + (f.size/1024).toFixed(1) + ' KB)');
            $('#holidayPreview').removeClass('d-none');
        }
    });
    $('#holidayImportForm').on('submit', function(){
        $('#btnHolidayImport').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span>Importing…');
    });
});
</script>
@endpush
