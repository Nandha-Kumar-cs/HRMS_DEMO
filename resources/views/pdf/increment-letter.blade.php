<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #222; }
    .page { padding: 28px 40px 20px 40px; }

    /* Header */
    .header { display: table; width: 100%; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; margin-bottom: 12px; }
    .header-left  { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; white-space: nowrap; padding-left: 10px; }
    .company-name { font-size: 16pt; font-weight: bold; color: #3b82f6; }
    .company-sub  { font-size: 8pt; color: #666; margin-top: 2px; }
    .ref          { font-size: 9pt; color: #666; }

    /* Title */
    h1 { text-align: center; font-size: 13pt; text-transform: uppercase; letter-spacing: 2px;
         color: #1e293b; margin: 10px 0; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 5px 0; }

    /* Info table */
    .info-box { background: #f8f9fc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; margin: 10px 0; }
    .info-row { display: table; width: 100%; margin-bottom: 5px; }
    .info-label { display: table-cell; width: 160px; color: #64748b; font-size: 8.5pt; font-weight: bold; vertical-align: middle; }
    .info-value { display: table-cell; font-weight: bold; font-size: 9.5pt; vertical-align: middle; }
    .old-sal { color: #999; text-decoration: line-through; }
    .new-sal { color: #16a34a; font-size: 11pt; }
    .pct-badge { background: #16a34a; color: white; padding: 1px 8px; border-radius: 3px; font-size: 9pt; }

    /* Body text */
    p { font-size: 9.5pt; line-height: 1.55; color: #444; margin-bottom: 8px; }

    /* Signature */
    .signature-row { display: table; width: 100%; margin-top: 28px; }
    .sig-left  { display: table-cell; width: 50%; }
    .sig-right { display: table-cell; width: 50%; text-align: right; }
    .sig-box { display: inline-block; width: 180px; border-top: 1px solid #333; padding-top: 5px; font-size: 8.5pt; }

    /* Footer */
    .footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 7px; font-size: 7.5pt; color: #999; text-align: center; }
</style>
</head>
<body>
@php
    $entity      = $incrementLetter->employee->entity;
    $companyName = $entity?->name ?? 'the Company';
    $address     = $entity?->full_address ?? '';
@endphp
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            @if($entity && $entity->logo_base64)
                <img src="{{ $entity->logo_base64 }}" style="max-height:50px;max-width:130px;display:block;margin-bottom:4px" alt="Logo">
            @else
                <div class="company-name">{{ $companyName }}</div>
            @endif
            @if($address)
                <div class="company-sub">{{ $address }}</div>
            @endif
            @if($entity?->phone)
                <div class="company-sub">{{ $entity->phone }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="ref">Date: {{ $incrementLetter->effective_date->format('d F Y') }}</div>
            <div class="ref">Ref: IL-{{ str_pad($incrementLetter->id, 4, '0', STR_PAD_LEFT) }}</div>
        </div>
    </div>

    <h1>Salary Increment Letter</h1>

    <p>Dear <strong>{{ $incrementLetter->employee->full_name }}</strong>,</p>
    <p>We are pleased to inform you that the management has decided to revise your salary, effective <strong>{{ $incrementLetter->effective_date->format('d F Y') }}</strong>. This reflects your outstanding contribution to the organization.</p>

    <div class="info-box">
        <div class="info-row"><div class="info-label">Employee Code:</div><div class="info-value">{{ $incrementLetter->employee->employee_code }}</div></div>
        <div class="info-row"><div class="info-label">Name:</div><div class="info-value">{{ $incrementLetter->employee->full_name }}</div></div>
        <div class="info-row"><div class="info-label">Designation:</div><div class="info-value">{{ $incrementLetter->employee->designation?->name ?? 'N/A' }}</div></div>
        <div class="info-row"><div class="info-label">Department:</div><div class="info-value">{{ $incrementLetter->employee->department?->name ?? 'N/A' }}</div></div>
        <div class="info-row">
            <div class="info-label">Previous CTC:</div>
            <div class="info-value"><span class="old-sal">₹ {{ number_format($incrementLetter->old_salary, 2) }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Revised CTC:</div>
            <div class="info-value"><span class="new-sal">₹ {{ number_format($incrementLetter->new_salary, 2) }}</span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Increment:</div>
            <div class="info-value"><span class="pct-badge">{{ $incrementLetter->increment_percentage }}%</span></div>
        </div>
        <div class="info-row"><div class="info-label">Effective From:</div><div class="info-value">{{ $incrementLetter->effective_date->format('d F Y') }}</div></div>
    </div>

    <p>We appreciate your dedication and expect continued excellence in your work. Congratulations on this well-deserved recognition.</p>

    <div class="signature-row">
        <div class="sig-left">
            <div class="sig-box">
                <div>{{ $incrementLetter->employee->full_name }}</div>
                <div style="color:#888;font-size:8pt">Employee Acknowledgment</div>
            </div>
        </div>
        <div class="sig-right">
            <div class="sig-box" style="text-align:left">
                <div>{{ $entity?->signatory_name ?? 'HR Department' }}</div>
                <div style="color:#888;font-size:8pt">{{ $entity?->signatory_title ?? 'Authorized Signatory' }}</div>
            </div>
        </div>
    </div>

    <div class="footer">{{ $companyName }}{{ $address ? ' &bull; ' . $address : '' }} &bull; Confidential</div>
</div>
</body>
</html>
