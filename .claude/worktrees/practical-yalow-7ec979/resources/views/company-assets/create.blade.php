@extends('layouts.app')
@section('title','Add Asset')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('assets.index') }}" class="text-decoration-none">Assets</a></li>
<li class="breadcrumb-item active">Add</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Add Asset</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('assets.store') }}" method="POST">
            @csrf
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                    <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Asset Type <span class="text-danger">*</span></label>
                    <select name="asset_type" class="form-select">
                        <option value="">Select</option>
                        @foreach(\App\Models\CompanyAsset::$types as $val => $label)
                            <option value="{{ $val }}" {{ old('asset_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Add Asset</button>
                <a href="{{ route('assets.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
