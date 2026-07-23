@extends('layouts.app')
@section('title','Record Asset Return')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('assets.index') }}" class="text-decoration-none">Assets</a></li>
<li class="breadcrumb-item active">Return Asset</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Return Asset — {{ $asset->asset_name }}</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning mb-4">
            Assigned to: <strong>{{ $assignment?->employee->full_name }}</strong> since {{ $assignment?->issue_date->format('d M Y') }}
        </div>
        <form action="{{ route('assets.return', $asset) }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Return Date <span class="text-danger">*</span></label>
                    <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Condition on Return</label>
                    <input type="text" name="condition_on_return" class="form-control" placeholder="e.g. Good, Damaged">
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-warning"><i class="fa fa-undo me-1"></i>Record Return</button>
                <a href="{{ route('assets.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
