@extends('layouts.app')
@section('title', 'Increment Letters')
@section('breadcrumb')
    <li class="breadcrumb-item active">Increment Letters</li>
@endsection
@section('content')


<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">Increment Letters</h5>
        <a href="{{ route('increment-letters.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Create Increment Letter</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered align-middle">
                <thead class="table-dark">
                    <tr><th>#</th><th>Employee</th><th>Old Salary</th><th>New Salary</th><th>Increment %</th><th>Effective Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td><div class="fw-semibold">{{ $letter->employee->full_name }}</div><small class="text-muted">{{ $letter->employee->employee_code }}</small></td>
                        <td>₹{{ number_format($letter->old_salary, 2) }}</td>
                        <td class="fw-semibold text-success">₹{{ number_format($letter->new_salary, 2) }}</td>
                        <td><span class="badge bg-info">{{ $letter->increment_percentage }}%</span></td>
                        <td>{{ $letter->effective_date->format('d M Y') }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('increment-letters.show', $letter) }}" class="btn btn-sm btn-info text-white" title="View"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('increment-letters.pdf', $letter) }}" class="btn btn-sm btn-danger ms-1" target="_blank" title="Download PDF"><i class="fa fa-file-pdf"></i></a>
                            <form action="{{ route('increment-letters.destroy', $letter) }}" method="POST" class="d-inline ms-1 form-delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"
                                        data-name="{{ $letter->employee->full_name }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No increment letters yet.</td></tr>
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
        title: 'Delete Increment Letter?',
        text: 'Delete the increment letter for ' + name + '? This cannot be undone.',
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
