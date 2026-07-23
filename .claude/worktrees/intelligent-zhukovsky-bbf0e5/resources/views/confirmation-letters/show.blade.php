@extends('layouts.app')
@section('title', 'Appointment Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('confirmation-letters.index') }}" class="text-decoration-none">Confirmation Letters</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection
@section('content')
<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('confirmation-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            <h5 class="mb-0 fw-semibold">Appointment Letter — {{ $confirmationLetter->employee->full_name }}</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="fa fa-print me-1"></i>Print</button>
            <a href="{{ route('confirmation-letters.pdf', $confirmationLetter) }}" class="btn btn-sm btn-danger" target="_blank"><i class="fa fa-file-pdf me-1"></i>PDF</a>
        </div>
    </div>
</div>

@php
    $entity      = $confirmationLetter->employee->entity;
    $companyName = $entity?->name ?? 'the Company';
    $salutation  = match($confirmationLetter->employee->gender) { 'male' => 'Mr.', 'female' => 'Mrs.', default => '' };
@endphp

<div class="card page-card" id="printArea">
    <div class="card-body p-5" style="min-height:70vh">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h2 class="fw-bold" style="font-size:1.8rem">Appointment Letter</h2>
            @if($entity && $entity->logo)
                <img src="{{ asset('storage/entities/' . $entity->logo) }}" style="max-height:65px;max-width:110px" alt="Logo">
            @elseif($entity)
                <span class="fw-bold fs-5">{{ $entity->name }}</span>
            @endif
        </div>
        <hr style="border-top:2px solid #222;margin-bottom:24px">

        {{-- Blank space --}}
        <div style="height:80px"></div>

        <p>{{ $confirmationLetter->confirmation_date->format('F d, Y') }}</p>
        <p>Dear <strong>{{ trim($salutation . ' ' . $confirmationLetter->employee->full_name) }}</strong>,</p>

        <p>Based on your performance, the management is pleased to inform you that you have been confirmed on the rolls of <strong>{{ $companyName }}</strong> w.e.f <strong>{{ $confirmationLetter->confirmation_date->format('d-m-y') }}</strong>. The salary remains the same as given in the offer letter at the time of joining.</p>

        <p>For {{ $companyName }}</p>

        <div class="mt-5">
            <p class="mb-1"><strong>{{ $entity?->signatory_name ?? 'Authorized Signatory' }}</strong></p>
            @if($entity?->signatory_title)<p class="text-muted small">{{ $entity->signatory_title }}</p>@endif
        </div>

        <hr class="mt-5">
        <p class="text-center text-muted small mb-0">
            {{ $companyName }}{{ $entity ? ' ' . $entity->full_address : '' }}
        </p>
    </div>
</div>
@endsection
@push('styles')
<style>
@media print {
    #topbar, #sidebar, .page-card:first-child, footer { display: none !important; }
    #main-content { margin-left: 0 !important; }
    #printArea { box-shadow: none !important; }
}
</style>
@endpush
