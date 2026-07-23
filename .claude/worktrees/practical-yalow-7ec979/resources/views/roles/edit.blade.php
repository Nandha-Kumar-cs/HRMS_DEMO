@extends('layouts.app')
@section('title','Edit Role — '.$role->name)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('roles.index') }}" class="text-decoration-none">Roles</a></li>
<li class="breadcrumb-item active">{{ $role->name }}</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold">Edit Role — {{ $role->name }}</h5>
        @if($role->is_system)<span class="md-pill md-pill-info ms-2">System Role</span>@endif
    </div>
    <div class="card-body">
        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf @method('PUT')
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control bg-light" value="{{ $role->slug }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $role->description) }}">
                </div>
            </div>

            {{-- Permissions --}}
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
                               {{ in_array($perm->id, $rolePerms) ? 'checked' : '' }}>
                        <label class="form-check-label" for="perm{{ $perm->id }}" style="font-size:13px">
                            {{ ucfirst($perm->feature) }}
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            {{-- Notification preferences --}}
            <h6 class="fw-semibold mb-3 mt-4" style="font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:var(--md-text-secondary)">
                Notification Preferences
            </h6>
            <div class="table-responsive">
                <table class="table" style="font-size:13px">
                    <thead>
                        <tr>
                            <th>Module / Event</th>
                            <th class="text-center">In-App</th>
                            <th class="text-center">Push</th>
                            <th class="text-center">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notifEvents as $module => $events)
                        @foreach($events as $event => $label)
                        @php $pref = $notifPrefs->get("{$module}.{$event}"); @endphp
                        <tr>
                            <td>
                                <span class="text-muted" style="font-size:11px">{{ ucfirst($module) }}</span><br>
                                {{ $label }}
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="notif[{{ $module }}][{{ $event }}][in_app]"
                                       {{ $pref?->in_app ? 'checked' : '' }}>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="notif[{{ $module }}][{{ $event }}][push]"
                                       {{ $pref?->push ? 'checked' : '' }}>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="notif[{{ $module }}][{{ $event }}][email]"
                                       {{ $pref?->email ? 'checked' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i>Save Changes</button>
                <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('.toggle-module').on('click', function() {
    var module = $(this).data('module');
    var checks = $('.perm-check-' + module);
    var allChecked = checks.filter(':checked').length === checks.length;
    checks.prop('checked', !allChecked);
    $(this).text(allChecked ? 'Select all' : 'Deselect all');
});
</script>
@endpush
