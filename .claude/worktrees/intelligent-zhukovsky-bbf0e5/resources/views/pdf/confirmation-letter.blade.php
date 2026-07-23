<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5pt; color: #222; }
    .page { padding: 35px 45px 30px 45px; min-height: 297mm; position: relative; }

    /* Header */
    .header { display: table; width: 100%; margin-bottom: 6px; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 120px; }
    .letter-title { font-size: 22pt; font-weight: bold; color: #111; }
    .logo-img { max-height: 65px; max-width: 110px; }
    .logo-placeholder { font-size: 13pt; font-weight: bold; color: #333; text-align: right; }
    hr.thick { border: none; border-top: 2px solid #222; margin: 8px 0 18px 0; }

    /* Body */
    p { font-size: 10.5pt; line-height: 1.65; margin-bottom: 12px; color: #222; }
    .spacer { height: 80px; }
    .sig-name { font-size: 10.5pt; margin-top: 4px; }

    /* Footer */
    .footer { border-top: 1px solid #aaa; margin-top: 30px; padding-top: 8px; text-align: center; font-size: 9pt; color: #444; position: absolute; bottom: 25px; left: 45px; right: 45px; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    @php $entity = $confirmationLetter->employee->entity; $companyName = $entity?->name ?? 'the Company'; @endphp
    <div class="header">
        <div class="header-left">
            <div class="letter-title">Confirmation Letter</div>
        </div>
        <div class="header-right">
            @if($entity && $entity->logo_base64)
                <img src="{{ $entity->logo_base64 }}" class="logo-img" alt="Logo">
            @elseif($entity)
                <div class="logo-placeholder">{{ $entity->name }}</div>
            @endif
        </div>
    </div>
    <hr class="thick">

    {{-- Blank space (matches template layout) --}}
    <div class="spacer"></div>

    {{-- Date --}}
    <p>{{ $confirmationLetter->confirmation_date->format('F d, Y') }}</p>

    {{-- Salutation --}}
    @php
        $salutation = match($confirmationLetter->employee->gender) {
            'male'   => 'Mr.',
            'female' => 'Mrs.',
            default  => '',
        };
    @endphp
    <p>Dear <strong>{{ trim($salutation . ' ' . $confirmationLetter->employee->full_name) }}</strong>,</p>

    {{-- Body --}}
    <p>Based on your performance, the management is pleased to inform you that you have been confirmed on the rolls of <strong>{{ $companyName }}</strong> w.e.f <strong>{{ $confirmationLetter->confirmation_date->format('d-m-y') }}</strong>. The salary remains the same as given in the offer letter at the time of joining.</p>

    {{-- Closing --}}
    <p>For {{ $companyName }}</p>

    {{-- Signatory --}}
    <br><br>
    <div class="sig-name">{{ $entity?->signatory_name ?? 'Authorized Signatory' }}</div>
    @if($entity?->signatory_title)
        <div style="font-size:9.5pt;color:#555">{{ $entity->signatory_title }}</div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $companyName }}{{ $entity?->address ? $entity->address . ($entity->city ? ', ' . $entity->city : '') . ($entity->state ? ', ' . $entity->state : '') . ($entity->pincode ? ' ' . $entity->pincode : '') : '' }}
    </div>
</div>
</body>
</html>
