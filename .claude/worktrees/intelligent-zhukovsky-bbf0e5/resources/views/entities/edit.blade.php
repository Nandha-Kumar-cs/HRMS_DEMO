@extends('layouts.app')

@section('title', 'Edit Entity')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('entities.index') }}" class="text-decoration-none">Entities</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('entities.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Edit Entity — {{ $entity->name }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('entities.update', $entity) }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Company Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $entity->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Company Logo</label>
                    @if($entity->logo)
                        <div class="mb-2">
                            <img src="{{ asset('storage/entities/' . $entity->logo) }}" alt="Logo" style="height:50px">
                            <span class="text-muted small ms-2">Current logo</span>
                        </div>
                    @endif
                    <input type="file" name="logo" class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                    <div class="form-text">Leave blank to keep current logo.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $entity->address) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $entity->city) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control" value="{{ old('state', $entity->state) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $entity->pincode) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $entity->phone) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $entity->email) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control" value="{{ old('website', $entity->website) }}">
                </div>
            </div>

            <h6 class="text-primary fw-semibold mb-3 border-bottom pb-2">Signatory Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Signatory Name</label>
                    <input type="text" name="signatory_name" class="form-control"
                           value="{{ old('signatory_name', $entity->signatory_name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Signatory Title</label>
                    <input type="text" name="signatory_title" class="form-control"
                           value="{{ old('signatory_title', $entity->signatory_title) }}">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Update Entity</button>
                <a href="{{ route('entities.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
