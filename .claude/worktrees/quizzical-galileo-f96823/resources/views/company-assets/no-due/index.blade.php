@extends('layouts.app')
@section('title','No Due Certificates')
@section('breadcrumb')<li class="breadcrumb-item active">No Due Certificates</li>@endsection

@section('content')
<div class="card page-card">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold">No Due Certificates</h5>
        <a href="{{ route('no-due.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i>Generate Certificate</a>
    </div>
    <div class="card-body">
                @if($certificates->isEmpty())
            <p class="text-muted text-center py-5">No certificates generated yet.</p>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-dark"><tr><th>Employee</th><th>Generated Date</th><th>Status</th><th>Remarks</th><th></th></tr></thead>
                <tbody>
                    @foreach($certificates as $cert)
                    <tr>
                        <td>{{ $cert->employee->full_name }} <small class="text-muted">({{ $cert->employee->employee_code }})</small></td>
                        <td>{{ $cert->generated_date->format('d M Y') }}</td>
                        <td><span class="badge bg-{{ $cert->status === 'approved' ? 'success' : 'warning text-dark' }}">{{ ucfirst($cert->status) }}</span></td>
                        <td>{{ $cert->remarks ?? '-' }}</td>
                        <td><a href="{{ route('no-due.show', $cert) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $certificates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
