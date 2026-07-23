<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 13pt;
        color: #000;
        background: #fff;
        padding: 40px 50px 80px 50px;
        line-height: 1.6;
    }

    /* ── Header ── */
    .header {
        display: table;
        width: 100%;
        border-bottom: 2px solid #000;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }
    .header-logo {
        display: table-cell;
        width: 110px;
        vertical-align: middle;
    }
    .header-logo img {
        max-width: 100px;
        max-height: 70px;
    }
    .header-company {
        display: table-cell;
        vertical-align: middle;
        text-align: center;
        padding: 0 10px;
    }
    .company-name {
        font-size: 20pt;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .company-sub {
        font-size: 12pt;
        font-style: italic;
        font-weight: bold;
    }

    /* ── Meta row ── */
    .meta {
        text-align: right;
        font-size: 11pt;
        margin-bottom: 6px;
    }
    .meta .ref { font-weight: bold; text-decoration: underline; }

    /* ── Title ── */
    .circular-title {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        font-size: 14pt;
        letter-spacing: 2px;
        margin: 16px 0 20px 0;
    }

    /* ── Body ── */
    .body-text p {
        margin-bottom: 14px;
        text-align: justify;
    }

    /* ── Signature ── */
    .sign-block {
        margin-top: 40px;
        text-align: center;
    }
    .sign-block .by-order {
        font-size: 12pt;
        letter-spacing: 2px;
        margin-bottom: 30px;
    }
    .sign-block .for-company {
        font-weight: bold;
        font-size: 12pt;
        text-transform: uppercase;
        margin-bottom: 50px;
    }
    .sign-block .authorised {
        font-size: 11pt;
    }

    /* ── Acknowledgement Section ── */
    .ack-section {
        margin-top: 36px;
        page-break-before: auto;
    }
    .ack-title {
        font-size: 12pt;
        font-weight: bold;
        text-decoration: underline;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        text-align: center;
    }
    .ack-note {
        font-size: 10pt;
        font-style: italic;
        text-align: center;
        margin-bottom: 12px;
        color: #333;
    }

    /* ── Employee Acknowledgement Table ── */
    .ack-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .ack-table thead tr {
        background: #f0f0f0;
    }
    .ack-table th {
        border: 1px solid #555;
        padding: 5px 7px;
        text-align: center;
        font-weight: bold;
        font-size: 10pt;
        white-space: nowrap;
    }
    .ack-table td {
        border: 1px solid #aaa;
        padding: 4px 7px;
        vertical-align: middle;
        font-size: 10pt;
    }
    .ack-table td.sno {
        text-align: center;
        width: 38px;
        color: #444;
    }
    .ack-table td.emp-name {
        width: 34%;
        font-weight: 500;
    }
    .ack-table td.emp-code {
        width: 14%;
        text-align: center;
        color: #444;
    }
    .ack-table td.emp-dept {
        width: 22%;
        color: #444;
    }
    .ack-table td.sign-cell {
        width: 22%;
        height: 28px;
    }

    /* ── Footer ── */
    .footer {
        position: fixed;
        bottom: 25px;
        left: 50px;
        right: 50px;
        border-top: 1.5px solid #000;
        padding-top: 8px;
        font-size: 8.5pt;
        text-align: center;
        color: #222;
        line-height: 1.5;
    }
</style>
</head>
<body>

{{-- ── Header ── --}}
<div class="header">
    <div class="header-logo">
        @if($entity && $entity->logo_base64)
            <img src="{{ $entity->logo_base64 }}" alt="Logo">
        @endif
    </div>
    <div class="header-company">
        <div class="company-name">{{ $entity->name ?? 'Company Name' }}</div>
        @if(str_contains(strtolower($entity->name ?? ''), 'private') || str_contains(strtolower($entity->name ?? ''), 'pvt'))
            {{-- name already has Private/Pvt, no sub-line needed --}}
        @else
            <div class="company-sub">Private Limited</div>
        @endif
    </div>
</div>

{{-- ── Ref & Date ── --}}
<div class="meta">
    <span class="ref">{{ $holiday->circular_ref }}</span>
</div>
<div class="meta">
    {{ $holiday->date->format('jS F Y') }}
</div>

{{-- ── Title ── --}}
<div class="circular-title">C I R C U L A R</div>

{{-- ── Body ── --}}
<div class="body-text">
    <p>
        This is to inform all employees that
        <strong>{{ $holiday->date->format('l') }}, {{ $holiday->date->format('jS F Y') }}</strong>,
        will be a <strong>working day</strong>
        @if($holiday->working_day_reason)
            due to {{ $holiday->working_day_reason }}.
        @else
            as per management decision.
        @endif
    </p>
    <p>
        Kindly note that this working day will be <strong>compensated with a holiday on another day</strong>.
    </p>
    <p>
        All employees are requested to take note of the above and attend duty accordingly.
    </p>
</div>

{{-- ── Signature ── --}}
<div class="sign-block">
    <div class="by-order">- &nbsp; BY ORDER &nbsp; -</div>
    <div class="for-company">For {{ strtoupper($entity->name ?? 'Company') }}</div>
    <div class="authorised">Authorised Signatory</div>
</div>

{{-- ── Employee Acknowledgement ── --}}
@if($employees->count())
<div class="ack-section">
    <div class="ack-title">Employee Acknowledgement</div>
    <div class="ack-note">I hereby acknowledge receipt of this circular and confirm my attendance on the above-mentioned working day.</div>

    <table class="ack-table">
        <thead>
            <tr>
                <th style="width:38px">Sr.</th>
                <th style="width:34%">Employee Name</th>
                <th style="width:14%">Emp. Code</th>
                <th style="width:22%">Department</th>
                <th style="width:22%">Signature</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $i => $emp)
            <tr>
                <td class="sno">{{ $i + 1 }}</td>
                <td class="emp-name">{{ $emp->full_name }}</td>
                <td class="emp-code">{{ $emp->employee_code }}</td>
                <td class="emp-dept">{{ $emp->department?->name ?? '—' }}</td>
                <td class="sign-cell">&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Footer ── --}}
<div class="footer">
    @php
        $addr = collect([
            $entity->address ?? null,
            $entity->city    ?? null,
            $entity->state   ?? null,
            $entity->pincode ?? null,
        ])->filter()->implode(', ');
        $contacts = collect([
            $entity->phone   ? 'Ph: ' . $entity->phone   : null,
            $entity->email   ? 'Email: ' . $entity->email : null,
            $entity->website ?? null,
        ])->filter()->implode('  |  ');
    @endphp
    @if($addr)
        <div>{{ $addr }}</div>
    @endif
    @if($contacts)
        <div>{{ $contacts }}</div>
    @endif
</div>

</body>
</html>
