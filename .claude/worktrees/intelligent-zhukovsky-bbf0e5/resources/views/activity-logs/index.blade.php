@extends('layouts.app')
@section('title', 'Activity Log')
@section('breadcrumb')
    <li class="breadcrumb-item active">Activity Log</li>
@endsection
@section('content')

<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="mb-0 fw-semibold"><i class="fa fa-clock-rotate-left me-2 text-primary"></i>Activity Log</h5>
        <span class="badge bg-secondary fs-6">{{ $logs->total() }} entries</span>
    </div>
    <div class="card-body border-bottom">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="User name or description…" value="{{ request('search') }}">
            </div>
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="fa fa-search me-1"></i>Filter</button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
                <thead class="table-light">
                    <tr>
                        <th style="width:140px">Date & Time</th>
                        <th style="width:130px">User</th>
                        <th style="width:90px">Module</th>
                        <th style="width:90px">Action</th>
                        <th>Description</th>
                        <th style="width:110px">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted small text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $log->user_name }}</div>
                        </td>
                        <td>
                            @php
                                $modulePalette = [
                                    'Employee'           => ['bg'=>'#dbeafe','color'=>'#1d4ed8','border'=>'#93c5fd'],
                                    'SalarySlip'         => ['bg'=>'#dcfce7','color'=>'#15803d','border'=>'#86efac'],
                                    'LeaveRequest'       => ['bg'=>'#e0f2fe','color'=>'#0369a1','border'=>'#7dd3fc'],
                                    'Loan'               => ['bg'=>'#fef9c3','color'=>'#92400e','border'=>'#fde047'],
                                    'Auth'               => ['bg'=>'#f1f5f9','color'=>'#475569','border'=>'#cbd5e1'],
                                    'Increment'          => ['bg'=>'#f0fdf4','color'=>'#166534','border'=>'#86efac'],
                                    'Promotion'          => ['bg'=>'#fdf4ff','color'=>'#7e22ce','border'=>'#d8b4fe'],
                                    'Bonus'              => ['bg'=>'#fff7ed','color'=>'#9a3412','border'=>'#fdba74'],
                                    'Department'         => ['bg'=>'#eff6ff','color'=>'#1e40af','border'=>'#bfdbfe'],
                                    'Designation'        => ['bg'=>'#f0f9ff','color'=>'#075985','border'=>'#7dd3fc'],
                                    'SalaryComponent'    => ['bg'=>'#f7fee7','color'=>'#3f6212','border'=>'#bef264'],
                                    'OfferLetter'        => ['bg'=>'#fefce8','color'=>'#854d0e','border'=>'#fde047'],
                                    'ConfirmationLetter' => ['bg'=>'#fff1f2','color'=>'#9f1239','border'=>'#fda4af'],
                                    'IncrementLetter'    => ['bg'=>'#ecfdf5','color'=>'#065f46','border'=>'#6ee7b7'],
                                    'Document'           => ['bg'=>'#f0f9ff','color'=>'#0369a1','border'=>'#bae6fd'],
                                    'Benefit'            => ['bg'=>'#fdf4ff','color'=>'#6b21a8','border'=>'#e9d5ff'],
                                ];
                                $mp = $modulePalette[$log->module] ?? ['bg'=>'#f3f4f6','color'=>'#374151','border'=>'#d1d5db'];
                            @endphp
                            <span style="display:inline-block;padding:2px 8px;font-size:.72rem;font-weight:600;border-radius:4px;background:{{ $mp['bg'] }};color:{{ $mp['color'] }};border:1px solid {{ $mp['border'] }};white-space:nowrap;">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td>
                            @php
                                $actionColors = [
                                    'created'   => 'success',
                                    'updated'   => 'primary',
                                    'deleted'   => 'danger',
                                    'login'     => 'info',
                                    'logout'    => 'secondary',
                                    'approved'  => 'success',
                                    'rejected'  => 'danger',
                                    'generated' => 'warning',
                                    'repayment' => 'info',
                                    'submitted' => 'primary',
                                ];
                                $ac = $actionColors[$log->action] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $ac }}" style="font-size:.72rem">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td class="small">
                            @php
                                $decoded  = json_decode($log->description, true);
                                $isJson   = is_array($decoded) && isset($decoded['summary']);
                            @endphp
                            @if($isJson)
                                <div class="fw-semibold text-dark mb-1">{{ $decoded['summary'] }}</div>
                                @if(!empty($decoded['changes']))
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($decoded['changes'] as $ch)
                                            <div class="d-flex align-items-center flex-wrap gap-1" style="font-size:.75rem">
                                                <span class="text-muted fw-semibold" style="min-width:120px;white-space:nowrap">{{ $ch['field'] }}:</span>
                                                <span style="background:#fee2e2;color:#991b1b;padding:1px 7px;border-radius:3px;text-decoration:line-through;white-space:nowrap">{{ $ch['from'] }}</span>
                                                <i class="fa fa-arrow-right-long" style="color:#94a3b8;font-size:.65rem"></i>
                                                <span style="background:#dcfce7;color:#166534;padding:1px 7px;border-radius:3px;white-space:nowrap">{{ $ch['to'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                {{ $log->description }}
                            @endif
                        </td>
                        <td class="text-muted small">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No activity recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="p-3">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
