@extends('layouts.app')
@section('title','Generate No Due Certificate')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('no-due.index') }}" class="text-decoration-none">No Due</a></li>
<li class="breadcrumb-item active">Generate</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:550px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('no-due.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Generate No Due Certificate</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('no-due.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select @error('employee_id') is-invalid @enderror">
                    <option value="">Select Employee</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->employee_code }})</option>@endforeach
                </select>
                @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-certificate me-1"></i>Generate Certificate</button>
        </form>
    </div>
</div>
@endsection
