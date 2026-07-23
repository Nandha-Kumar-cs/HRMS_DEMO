@extends('layouts.app')
@section('title','Edit Asset')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('assets.index') }}" class="text-decoration-none">Assets</a></li>
<li class="breadcrumb-item active">Edit</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:600px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Edit Asset — {{ $asset->asset_name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('assets.update', $asset) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Asset Name</label>
                    <input type="text" name="asset_name" class="form-control" value="{{ old('asset_name', $asset->asset_name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Asset Type</label>
                    <select name="asset_type" class="form-select">
                        @foreach(\App\Models\CompanyAsset::$types as $val => $label)
                            <option value="{{ $val }}" {{ old('asset_type', $asset->asset_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['available','assigned','returned','damaged'] as $s)
                            <option value="{{ $s }}" {{ old('status', $asset->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $asset->description) }}</textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Update</button>
                <a href="{{ route('assets.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
