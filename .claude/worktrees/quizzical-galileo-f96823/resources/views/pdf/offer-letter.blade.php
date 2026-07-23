<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5pt; color: #222; }
    .page { padding: 35px 45px 30px 45px; min-height: 297mm; position: relative; }
    .page-break { page-break-after: always; }

    /* Header */
    .header { display: table; width: 100%; margin-bottom: 6px; }
    .header-left { display: table-cell; vertical-align: middle; }
    .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 120px; }
    .letter-title { font-size: 22pt; font-weight: bold; color: #111; }
    .logo-img { max-height: 65px; max-width: 110px; }
    .logo-placeholder { font-size: 13pt; font-weight: bold; color: #333; text-align: right; }
    hr.thick { border: none; border-top: 2px solid #222; margin: 8px 0 18px 0; }

    /* Body */
    p { font-size: 10.5pt; line-height: 1.65; margin-bottom: 10px; color: #222; }
    .date { font-size: 10.5pt; margin-bottom: 14px; }
    .salutation { margin-bottom: 12px; }

    /* Terms table */
    table.terms { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.terms td { font-size: 10.5pt; padding: 4px 6px; vertical-align: top; line-height: 1.55; }
    table.terms td.num { width: 22px; white-space: nowrap; }
    table.terms td.label { width: 130px; font-weight: normal; white-space: nowrap; }
    table.terms td.colon { width: 10px; }
    table.terms td.value { }

    /* Signature area */
    .sig-section { margin-top: 30px; }
    .sig-name { font-weight: bold; font-size: 10.5pt; margin-top: 4px; }

    /* Candidate acceptance */
    .acceptance { margin-top: 20px; font-size: 10.5pt; }
    .acceptance-row { display: table; width: 100%; margin-top: 30px; }
    .acceptance-left { display: table-cell; vertical-align: bottom; font-weight: bold; font-size: 11pt; }
    .acceptance-right { display: table-cell; vertical-align: bottom; text-align: right; font-size: 10.5pt; }

    /* Footer */
    .footer { border-top: 1px solid #aaa; margin-top: 30px; padding-top: 8px; text-align: center; font-size: 9pt; color: #444; }

    /* Page 2 - Salary Breakup */
    table.salary { width: 60%; margin: 0 auto 20px auto; border-collapse: collapse; font-size: 10.5pt; }
    table.salary th { background: #fff; text-align: center; font-weight: bold; padding: 7px 10px; border: 1px solid #555; }
    table.salary td { border: 1px solid #aaa; padding: 5px 8px; }
    table.salary td.sl { width: 30px; text-align: center; color: #666; }
    table.salary td.comp { }
    table.salary td.amt { text-align: right; }
    table.salary tr.total td { font-weight: bold; }
    table.salary tr.section-label td { background: #f5f5f5; font-weight: bold; }
    .notes { margin-top: 10px; font-size: 10pt; }
    .notes p { font-size: 10pt; margin-bottom: 5px; }
    ol.notes-list { margin-left: 20px; }
    ol.notes-list li { font-size: 10pt; margin-bottom: 5px; line-height: 1.55; }
</style>
</head>
<body>

{{-- ===================== PAGE 1 : OFFER LETTER ===================== --}}
<div class="page page-break">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="letter-title">Offer Letter</div>
        </div>
        <div class="header-right">
            @php $entity = $offerLetter->employee->entity; @endphp
            @if($entity && $entity->logo_base64)
                <img src="{{ $entity->logo_base64 }}" class="logo-img" alt="Logo">
            @elseif($entity)
                <div class="logo-placeholder">{{ $entity->name }}</div>
            @endif
        </div>
    </div>
    <hr class="thick">

    {{-- Date --}}
    <div class="date">{{ $offerLetter->offer_date->format('d-F-Y') }}</div>

    {{-- Salutation --}}
    @php
        $salutation = match($offerLetter->employee->gender) {
            'male'   => 'Mr.',
            'female' => 'Mrs.',
            default  => '',
        };
        $companyName = $entity?->name ?? 'the Company';
    @endphp
    <div class="salutation">Dear {{ $salutation }} {{ $offerLetter->employee->full_name }}</div>

    {{-- Opening --}}
    <p>With reference to your application and the interviews you had with <strong>{{ $companyName }}</strong> , we are pleased to offer you employment in our company on the following terms and conditions.</p>

    {{-- Terms table --}}
    <table class="terms">
        <tr>
            <td class="num">1.</td>
            <td class="label">Designation</td>
            <td class="colon">:</td>
            <td class="value">{{ $offerLetter->employee->designation?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="num">2.</td>
            <td class="label">Department</td>
            <td class="colon">:</td>
            <td class="value">{{ $offerLetter->employee->department?->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="num">3.</td>
            <td class="label">Date Of Joining</td>
            <td class="colon">:</td>
            <td class="value">{{ $offerLetter->joining_date->format('d-m-y') }} ( {{ $offerLetter->joining_date->format('l') }} )</td>
        </tr>
        <tr>
            <td class="num">4.</td>
            <td class="label">Compensation</td>
            <td class="colon">:</td>
            <td class="value">Rs {{ number_format($offerLetter->salary, 0) }} per month + retirals</td>
        </tr>
        <tr>
            <td class="num">5.</td>
            <td class="label">Probation</td>
            <td class="colon">:</td>
            <td class="value">First six months from the date of joining will be treated as probation period. During this period, no increments will apply</td>
        </tr>
        <tr>
            <td class="num">6.</td>
            <td class="label">Confirmation</td>
            <td class="colon">:</td>
            <td class="value">After completion of six months, we will evaluate your performance and decide whether to retain your services. Unless the employment is confirmed in writing at the end of the probation period, it should be considered terminated.</td>
        </tr>
        <tr>
            <td class="num">7.</td>
            <td class="label">House Of work</td>
            <td class="colon">:</td>
            <td class="value">9.00am to 6.15pm (with weekly off as per company policy)</td>
        </tr>
        <tr>
            <td class="num">8.</td>
            <td class="label">Notice Of<br>termination</td>
            <td class="colon">:</td>
            <td class="value">During the probation period, your service can be terminated by either side by giving two day's written notice. Upon confirmation, one month's written notice is required from either side. If you are already on an assignment and if your presence in the assignment is necessary as assessed by the management, the management reserves the right to require you to work till the assignment is complete.</td>
        </tr>
        <tr>
            <td class="num">9.</td>
            <td class="label">Leave Policy</td>
            <td class="colon">:</td>
            <td class="value">As per the rules of the company, you can avail 6 days casual &amp; 6 days sick leave per year.</td>
        </tr>
    </table>

    <p>Please sign and return the copy of this letter in token of your acceptance, if the terms and conditions specified above and enclosed are acceptable to you.</p>

    <p>We welcome you to {{ $companyName }} and look forward to your contribution to the success and growth of the Company<br>
    For {{ $companyName }}</p>

    {{-- Signatory --}}
    <div class="sig-section">
        <br><br>
        <div class="sig-name">{{ $entity?->signatory_name ?? 'Authorized Signatory' }}</div>
        @if($entity?->signatory_title)
            <div style="font-size:9.5pt;color:#555">{{ $entity->signatory_title }}</div>
        @endif
    </div>

    {{-- Candidate acceptance --}}
    <div class="acceptance">
        <p>I agree to the above terms and conditions and will be joining on:</p>
        <div class="acceptance-row">
            <div class="acceptance-left">[ {{ $offerLetter->employee->full_name }}]</div>
            <div class="acceptance-right">
                confirmed Date Of Joining<br>
                {{ $offerLetter->joining_date->format('d-m-y') }}
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        {{ $companyName }}{{ $entity?->address ? $entity->address . ($entity->city ? ', ' . $entity->city : '') . ($entity->state ? ', ' . $entity->state : '') . ($entity->pincode ? ' ' . $entity->pincode : '') : '' }}
    </div>
</div>

{{-- ===================== PAGE 2 : SALARY BREAKUP ===================== --}}
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="letter-title">Offer Letter</div>
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

    <br>

    {{-- Salary Breakup Table --}}
    <table class="salary">
        <thead>
            <tr><th colspan="3">SALARY BREAKUP</th></tr>
        </thead>
        <tbody>
            @php $i = 1; @endphp
            @foreach($allowances as $name => $amount)
            <tr>
                <td class="sl">{{ $i++ }}</td>
                <td class="comp">{{ $name }}</td>
                <td class="amt">{{ number_format($amount, 0) }}</td>
            </tr>
            @endforeach
            @foreach($deductions as $name => $amount)
            <tr>
                <td class="sl">{{ $i++ }}</td>
                <td class="comp">{{ $name }}</td>
                <td class="amt">{{ number_format($amount, 0) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td class="sl"></td>
                <td class="comp"><strong>Gross Pay</strong></td>
                <td class="amt"><strong>{{ number_format($grossPay, 0) }}</strong></td>
            </tr>
            <tr>
                <td class="sl">{{ $i++ }}</td>
                <td class="comp"><strong>Benefits</strong></td>
                <td class="amt"></td>
            </tr>
            <tr>
                <td class="sl"></td>
                <td class="comp" style="color:#666">@foreach($deductions as $name => $v) {{ $name }} @endforeach</td>
                <td class="amt"></td>
            </tr>
            <tr class="total">
                <td class="sl">{{ $i++ }}</td>
                <td class="comp"><strong>Total Cost to Company</strong></td>
                <td class="amt"><strong>{{ number_format($offerLetter->salary, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    {{-- Notes --}}
    <div class="notes">
        <p><strong>Note :</strong></p>
        <ol class="notes-list">
            <li>All payments are subject to Tax deduction at source (TDS). You are responsible for declaring your tax exemptions &amp; tax liabilities</li>
            <li>Take home pay will be Gross Pay - Applicable Statutory deductions(PF, ESI, Professional Tax etc.)</li>
            <li>All reimbursements are at actuals and need to be supported with bills/vouchers whenever available</li>
        </ol>
    </div>

    {{-- Footer --}}
    <div class="footer">
        {{ $companyName }}{{ $entity?->address ? $entity->address . ($entity->city ? ', ' . $entity->city : '') . ($entity->state ? ', ' . $entity->state : '') . ($entity->pincode ? ' ' . $entity->pincode : '') : '' }}
    </div>
</div>

</body>
</html>
