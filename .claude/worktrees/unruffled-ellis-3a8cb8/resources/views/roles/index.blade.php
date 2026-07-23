@extends('layouts.app')
@section('title','Roles & Permissions')
@section('breadcrumb')
<li class="breadcrumb-item active">Roles &amp; Permissions</li>
@endsection
@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-shield-halved me-2 text-primary"></i>Roles &amp; Permissions</h5>
        <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i><sc class="sc">N</sc>ew Role
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th class="text-center">Users</th>
                    <th class="text-center">Permissions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr>
                    <td>
                        <span class="fw-600">{{ $role->name }}</span>
                        @if($role->is_system)
                            <span class="md-pill md-pill-info ms-1">System</span>
                        @endif
                    </td>
                    <td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px">{{ $role->slug }}</code></td>
                    <td class="text-muted" style="font-size:13px">{{ $role->description ?: '—' }}</td>
                    <td class="text-center">{{ $role->users_count }}</td>
                    <td class="text-center">{{ $role->permissions_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-xs btn-outline-primary">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        @if(!$role->is_system)
                        <button class="btn btn-xs btn-outline-danger btn-delete"
                                data-url="{{ route('roles.destroy', $role) }}">
                            <i class="fa fa-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No roles yet. <a href="{{ route('roles.create') }}">Create one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
window.pageShortcuts = {
    'n': { label: 'New Role', action: function() { window.location = '{{ route('roles.create') }}'; } }
};
</script>
@endpush
