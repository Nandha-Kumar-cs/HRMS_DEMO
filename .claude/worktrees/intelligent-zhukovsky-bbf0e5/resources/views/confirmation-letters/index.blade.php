@extends('layouts.app')
@section('title', 'Confirmation Letters')
@section('breadcrumb')
    <li class="breadcrumb-item active">Confirmation Letters</li>
@endsection
@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
    <i class="fa fa-check-circle me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Confirmation Letters</h5>
        <a href="{{ route('confirmation-letters.create') }}" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i> Create Confirmation Letter
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Employee</th><th>Confirmation Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><div class="fw-semibold">{{ $letter->employee->full_name }}</div><small class="text-muted">{{ $letter->employee->employee_code }}</small></td>
                        <td>{{ $letter->confirmation_date->format('d M Y') }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('confirmation-letters.show', $letter) }}" class="btn btn-sm btn-info text-white" title="View"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('confirmation-letters.pdf', $letter) }}" class="btn btn-sm btn-danger ms-1" target="_blank" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
                            <form action="{{ route('confirmation-letters.destroy', $letter) }}" method="POST" class="d-inline ms-1 form-delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                        data-name="{{ $letter->employee->full_name }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No confirmation letters yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $letters->links() }}
    </div>
</div>
@endsection
@push('scripts')
<script>
$('.form-delete').on('submit', function(e) {
    e.preventDefault();
    var name = $(this).find('[data-name]').data('name');
    Swal.fire({
        title: 'Delete Confirmation Letter?',
        text: 'Delete the confirmation letter for ' + name + '? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
    }).then(function(result) {
        if (result.isConfirmed) e.target.closest('form').submit();
    }.bind(this));
});
</script>
@endpush
