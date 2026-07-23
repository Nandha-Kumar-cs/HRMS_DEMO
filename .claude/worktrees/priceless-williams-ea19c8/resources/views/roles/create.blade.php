@extends('layouts.app')
@section('title','New Role')
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('roles.index') }}" class="text-decoration-none">Roles</a></li>
<li class="breadcrumb-item active">New Role</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">New Role</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. HR Manager">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" id="slugField" class="form-control" value="{{ old('slug') }}" required placeholder="e.g. hr-manager">
                    <div class="form-text">Lowercase letters, numbers, hyphens only</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="Brief description">
                </div>
            </div>

            {{-- Permissions by module --}}
            <h6 class="fw-semibold mb-3" style="font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:var(--md-text-secondary)">
                Permissions
            </h6>
            @foreach($permissions as $module => $perms)
            @php $moduleLabel = config('magdyn.modules.'.$module, ucfirst($module)); @endphp
            <div class="mb-3 p-3" style="background:var(--md-surface-2);border-radius:var(--md-radius);border:1px solid var(--md-border)">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <strong style="font-size:13px">{{ $moduleLabel }}</strong>
                    <button type="button" class="btn btn-xs btn-outline-secondary toggle-module"
                            data-module="{{ $module }}">Select all</button>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($perms as $perm)
                    <div class="form-check">
                        <input class="form-check-input perm-check-{{ $module }}" type="checkbox"
                               name="permissions[]" value="{{ $perm->id }}"
                               id="perm{{ $perm->id }}"
                               {{ in_array($perm->id, old('permissions', [])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm{{ $perm->id }}" style="font-size:13px">
                            {{ ucfirst($perm->feature) }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            @if(!$permissions->count())
            <p class="text-muted small">No permissions defined yet. Seed them first via <code>php artisan db:seed --class=PermissionSeeder</code>.</p>
            @endif

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Create Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
// Auto-generate slug from name
$('input[name="name"]').on('input', function() {
    if (!$('#slugField').data('manual')) {
        $('#slugField').val($(this).val().toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,''));
    }
});
$('#slugField').on('input', function() { $(this).data('manual', true); });

// Toggle all checkboxes in a module
$('.toggle-module').on('click', function() {
    var module = $(this).data('module');
    var checks = $('.perm-check-' + module);
    var allChecked = checks.filter(':checked').length === checks.length;
    checks.prop('checked', !allChecked);
    $(this).text(allChecked ? 'Select all' : 'Deselect all');
});
</script>
@endpush
