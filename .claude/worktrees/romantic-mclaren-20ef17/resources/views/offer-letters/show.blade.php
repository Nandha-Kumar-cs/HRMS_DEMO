@extends('layouts.app')
@section('title', 'Offer Letter')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('offer-letters.index') }}" class="text-decoration-none">Offer Letters</a></li>
    <li class="breadcrumb-item active">View</li>
@endsection
@section('content')
<div class="card page-card mb-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('offer-letters.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-left"></i></a>
            <h5 class="mb-0 fw-semibold">Offer Letter — {{ $offerLetter->employee->full_name }}</h5>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-print me-1"></i>Print
            </button>
            <a href="{{ route('offer-letters.pdf', $offerLetter) }}" class="btn btn-sm btn-danger" target="_blank">
                <i class="fa fa-file-pdf me-1"></i>Download PDF
            </a>
        </div>
    </div>
</div>

@php
    $entity      = $offerLetter->employee->entity;
    $companyName = $entity?->name ?? 'the Company';
    $salutation  = match($offerLetter->employee->gender) { 'male' => 'Mr.', 'female' => 'Mrs.', default => '' };
@endphp

{{-- PAGE 1 --}}
<div class="card page-card mb-4" id="printArea">
    <div class="card-body p-5">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h2 class="fw-bold" style="font-size:1.8rem">Offer Letter</h2>
            @if($entity && $entity->logo)
                <img src="{{ asset('storage/entities/' . $entity->logo) }}" style="max-height:65px;max-width:110px" alt="Logo">
            @elseif($entity)
                <span class="fw-bold fs-5">{{ $entity->name }}</span>
            @endif
        </div>
        <hr style="border-top:2px solid #222;margin-bottom:20px">

        <p class="mb-3">{{ $offerLetter->offer_date->format('d-F-Y') }}</p>
        <p class="mb-3">Dear {{ $salutation }} {{ $offerLetter->employee->full_name }}</p>

        <p>With reference to your application and the interviews you had with <strong>{{ $companyName }}</strong> , we are pleased to offer you employment in our company on the following terms and conditions.</p>

        {{-- Terms --}}
        <table class="table table-borderless mb-3" style="font-size:.97rem">
            <tbody>
                <tr><td style="width:22px;padding:3px 6px">1.</td><td style="width:140px;padding:3px 6px">Designation</td><td style="width:10px;padding:3px 0">:</td><td style="padding:3px 8px">{{ $offerLetter->employee->designation?->name ?? 'N/A' }}</td></tr>
                <tr><td style="padding:3px 6px">2.</td><td style="padding:3px 6px">Department</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">{{ $offerLetter->employee->department?->name ?? 'N/A' }}</td></tr>
                <tr><td style="padding:3px 6px">3.</td><td style="padding:3px 6px">Date Of Joining</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">{{ $offerLetter->joining_date->format('d-m-y') }} ( {{ $offerLetter->joining_date->format('l') }} )</td></tr>
                <tr><td style="padding:3px 6px">4.</td><td style="padding:3px 6px">Compensation</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">Rs {{ number_format($offerLetter->salary, 0) }} per month + retirals</td></tr>
                <tr><td style="padding:3px 6px">5.</td><td style="padding:3px 6px">Probation</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">First six months from the date of joining will be treated as probation period. During this period, no increments will apply</td></tr>
                <tr><td style="padding:3px 6px">6.</td><td style="padding:3px 6px">Confirmation</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">After completion of six months, we will evaluate your performance and decide whether to retain your services. Unless the employment is confirmed in writing at the end of the probation period, it should be considered terminated.</td></tr>
                <tr><td style="padding:3px 6px">7.</td><td style="padding:3px 6px">House Of work</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">9.00am to 6.15pm (with weekly off as per company policy)</td></tr>
                <tr><td style="padding:3px 6px">8.</td><td style="padding:3px 6px" style="white-space:nowrap">Notice Of<br>termination</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">During the probation period, your service can be terminated by either side by giving two day's written notice. Upon confirmation, one month's written notice is required from either side. If you are already on an assignment and if your presence in the assignment is necessary as assessed by the management, the management reserves the right to require you to work till the assignment is complete.</td></tr>
                <tr><td style="padding:3px 6px">9.</td><td style="padding:3px 6px">Leave Policy</td><td style="padding:3px 0">:</td><td style="padding:3px 8px">As per the rules of the company, you can avail 6 days casual &amp; 6 days sick leave per year.</td></tr>
            </tbody>
        </table>

        <p>Please sign and return the copy of this letter in token of your acceptance, if the terms and conditions specified above and enclosed are acceptable to you.</p>
        <p>We welcome you to {{ $companyName }} and look forward to your contribution to the success and growth of the Company<br>For {{ $companyName }}</p>

        <div class="mt-4">
            <p class="mb-1"><strong>{{ $entity?->signatory_name ?? 'Authorized Signatory' }}</strong></p>
            @if($entity?->signatory_title)<p class="text-muted small">{{ $entity->signatory_title }}</p>@endif
        </div>

        <p class="mt-4">I agree to the above terms and conditions and will be joining on:</p>
        <div class="d-flex justify-content-between mt-4">
            <strong>[ {{ $offerLetter->employee->full_name }}]</strong>
            <div class="text-end">confirmed Date Of Joining<br>{{ $offerLetter->joining_date->format('d-m-y') }}</div>
        </div>

        <hr class="mt-4">
        <p class="text-center text-muted small mb-0">
            {{ $companyName }}{{ $entity ? ' ' . $entity->full_address : '' }}
        </p>
    </div>
</div>

{{-- PAGE 2 - Salary Breakup --}}
<div class="card page-card" id="printArea2">
    <div class="card-body p-5">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h2 class="fw-bold" style="font-size:1.8rem">Offer Letter</h2>
            @if($entity && $entity->logo)
                <img src="{{ asset('storage/entities/' . $entity->logo) }}" style="max-height:65px;max-width:110px" alt="Logo">
            @elseif($entity)
                <span class="fw-bold fs-5">{{ $entity->name }}</span>
            @endif
        </div>
        <hr style="border-top:2px solid #222;margin-bottom:24px">

        <div class="d-flex justify-content-center">
            <table class="table table-bordered" style="width:60%;font-size:.95rem">
                <thead class="table-light">
                    <tr><th colspan="3" class="text-center">SALARY BREAKUP</th></tr>
                </thead>
                <tbody>
                    @php $i = 1; @endphp
                    @foreach($allowances as $name => $amount)
                    <tr>
                        <td class="text-center text-muted" style="width:35px">{{ $i++ }}</td>
                        <td>{{ $name }}</td>
                        <td class="text-end">{{ number_format($amount, 0) }}</td>
                    </tr>
                    @endforeach
                    @foreach($deductions as $name => $amount)
                    <tr>
                        <td class="text-center text-muted">{{ $i++ }}</td>
                        <td>{{ $name }}</td>
                        <td class="text-end">{{ number_format($amount, 0) }}</td>
                    </tr>
                    @endforeach
                    <tr class="table-light">
                        <td></td>
                        <td><strong>Gross Pay</strong></td>
                        <td class="text-end"><strong>{{ number_format($grossPay, 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-center text-muted">{{ $i++ }}</td>
                        <td><strong>Benefits</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="text-muted">@foreach($deductions as $n => $v) {{ $n }} @endforeach</td>
                        <td></td>
                    </tr>
                    <tr class="table-light">
                        <td class="text-center text-muted">{{ $i++ }}</td>
                        <td><strong>Total Cost to Company</strong></td>
                        <td class="text-end"><strong>{{ number_format($offerLetter->salary, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <p class="fw-semibold mb-2">Note :</p>
            <ol style="font-size:.93rem">
                <li class="mb-1">All payments are subject to Tax deduction at source (TDS). You are responsible for declaring your tax exemptions &amp; tax liabilities</li>
                <li class="mb-1">Take home pay will be Gross Pay - Applicable Statutory deductions(PF, ESI, Professional Tax etc.)</li>
                <li class="mb-1">All reimbursements are at actuals and need to be supported with bills/vouchers whenever available</li>
            </ol>
        </div>

        <hr class="mt-4">
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
    #printArea, #printArea2 { box-shadow: none !important; }
    #printArea { page-break-after: always; }
}
</style>
@endpush
