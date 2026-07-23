@extends('layouts.app')
@section('title','New Training Module')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('training.index') }}" class="text-decoration-none">Training</a></li>
<li class="breadcrumb-item active">New Module</li>
@endsection
@section('content')
<div class="card page-card" style="max-width:700px">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('training.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">New Training Module</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('training.module.store') }}" method="POST">
            @csrf
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="mb-3">
                <label class="form-label">Module Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required
                       placeholder="e.g. HR Onboarding">
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="What will learners get from this module?">{{ old('description') }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Role Access</label>
                <div class="d-flex flex-wrap gap-3 mt-1">
                    @php $defaultRoles = ['admin','manager','staff','employee']; @endphp
                    @foreach($defaultRoles as $r)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="role_access[]"
                               value="{{ $r }}" id="ra_{{ $r }}"
                               {{ in_array($r, old('role_access', [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="ra_{{ $r }}">{{ ucfirst($r) }}</label>
                    </div>
                    @endforeach
                </div>
                <div class="form-text">Leave all unchecked to allow all roles.</div>
            </div>
            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_published" id="published" role="switch"
                           {{ old('is_published') ? 'checked' : '' }}>
                    <label class="form-check-label" for="published">Publish immediately</label>
                </div>
                <div class="form-text">Unpublished modules are only visible to admins.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-arrow-right me-1"></i>Create &amp; Add Lessons</button>
                <a href="{{ route('training.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
