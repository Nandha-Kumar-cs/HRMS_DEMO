@extends('layouts.app')
@section('title', 'Increment Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('increment-letters.index') }}" class="text-decoration-none">Increment Letters</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection
@section('content')
<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('increment-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            <h5 class="mb-0 fw-semibold">Increment Letter — {{ $incrementLetter->employee->full_name }}</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i>Print</button>
            <a href="{{ route('increment-letters.pdf', $incrementLetter) }}" class="btn btn-sm btn-danger" target="_blank"><i class="fa fa-file-pdf me-1"></i>PDF</a>
        </div>
    </div>
</div>
@php
    $entity      = $incrementLetter->employee->entity;
    $companyName = $entity?->name ?? 'the Company';
@endphp
<div class="card page-card" id="printArea">
    <div class="card-body p-5">
        {{-- Header: logo left, ref right --}}
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                @if($entity && $entity->logo)
                    <img src="{{ asset('storage/entities/' . $entity->logo) }}"
                         style="max-height:60px;max-width:160px;display:block;margin-bottom:6px" alt="Logo">
                @endif
                <h5 class="fw-bold mb-0">{{ $companyName }}</h5>
                @if($entity?->full_address)
                    <div class="text-muted small">{{ $entity->full_address }}</div>
                @endif
                @if($entity?->phone)
                    <div class="text-muted small">{{ $entity->phone }}</div>
                @endif
            </div>
            <div class="text-end">
                <div class="text-muted small">Date: {{ $incrementLetter->effective_date->format('d F Y') }}</div>
                <div class="text-muted small">Ref: IL-{{ str_pad($incrementLetter->id, 4, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
        <h4 class="text-center fw-bold mb-4 text-uppercase" style="letter-spacing:2px">SALARY INCREMENT LETTER</h4>
        <p>Dear <strong>{{ $incrementLetter->employee->full_name }}</strong>,</p>
        <p>We are pleased to inform you that the management has decided to revise your salary, effective <strong>{{ $incrementLetter->effective_date->format('d F Y') }}</strong>. This is in recognition of your performance and contribution to the organization.</p>
        <div class="bg-light rounded p-4 mb-4">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small fw-semibold">Employee Code</div><div class="fw-semibold">{{ $incrementLetter->employee->employee_code }}</div></div>
                <div class="col-md-4"><div class="text-muted small fw-semibold">Designation</div><div class="fw-semibold">{{ $incrementLetter->employee->designation?->name ?? 'N/A' }}</div></div>
                <div class="col-md-4"><div class="text-muted small fw-semibold">Department</div><div class="fw-semibold">{{ $incrementLetter->employee->department?->name ?? 'N/A' }}</div></div>
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold">Previous CTC</div>
                    <div class="fw-semibold text-muted text-decoration-line-through">₹{{ number_format($incrementLetter->old_salary, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold">Revised CTC</div>
                    <div class="fw-semibold text-success h5 mb-0">₹{{ number_format($incrementLetter->new_salary, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small fw-semibold">Increment</div>
                    <div class="fw-semibold"><span class="badge bg-success fs-6">{{ $incrementLetter->increment_percentage }}%</span></div>
                </div>
            </div>
        </div>
        <p>We appreciate your dedication and expect continued excellence in your work. Congratulations on this well-deserved recognition.</p>
        <div class="row mt-5">
            <div class="col-md-5"><div class="border-top pt-2"><div class="text-muted small">Employee Acknowledgment</div><div class="fw-semibold">{{ $incrementLetter->employee->full_name }}</div></div></div>
            <div class="col-md-5 ms-auto"><div class="border-top pt-2"><div class="text-muted small">Authorized Signatory</div><div class="fw-semibold">{{ $entity?->signatory_name ?? 'HR Department' }}</div>@if($entity?->signatory_title)<div class="text-muted small">{{ $entity->signatory_title }}</div>@endif</div></div>
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>@media print { #topbar, #sidebar, .page-card:first-child, footer { display: none !important; } #main-content { margin-left: 0 !important; } #printArea { box-shadow: none !important; } }</style>
@endpush
