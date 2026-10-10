<!doctype html>
<html><head><meta charset="utf-8">
<style>
    @page { margin: 28px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #111827; margin: 0; }
    .slip { border: 1.5px dashed #6b7280; padding: 14px 18px; margin-bottom: 18px; page-break-inside: avoid; }
    .school { font-size: 15px; font-weight: bold; margin: 0; }
    .muted { color: #4b5563; font-size: 9.5px; }
    .title { margin: 8px 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 3px 0; vertical-align: top; }
    td.k { width: 30%; color: #374151; }
    .code { font-family: 'DejaVu Sans Mono', monospace; font-size: 18px; font-weight: bold; letter-spacing: 2px; }
    .box { border: 1px solid #111827; padding: 6px 10px; display: inline-block; }
    .steps { margin: 10px 0 0; padding-left: 16px; font-size: 9.5px; color: #374151; }
</style>
</head>
<body>
@foreach ($candidates as $c)
    <div class="slip">
        <p class="school">{{ $school?->name }}</p>
        <p class="muted">{{ collect([$school?->address, $school?->city, $school?->phone])->filter()->join(' · ') }}</p>
        <p class="title">Entrance examination slip</p>
        <table>
            <tr><td class="k">Applicant</td><td><strong>{{ $c->inquiry?->student_name }}</strong>@if ($c->inquiry?->class_interested) &nbsp;({{ $c->inquiry->class_interested }})@endif</td></tr>
            <tr><td class="k">Exam</td><td>{{ $test->title }}</td></tr>
            <tr><td class="k">Date and time</td><td>{{ $test->opens_at->timezone($school?->timezone ?: config('app.timezone'))->format('l j F Y, g:i a') }} &nbsp;·&nbsp; {{ $test->duration_minutes }} minutes</td></tr>
            <tr>
                <td class="k" style="padding-top: 8px;">Exam number</td>
                <td style="padding-top: 8px;"><span class="box code">{{ $c->exam_number }}</span></td>
            </tr>
            <tr>
                <td class="k" style="padding-top: 8px;">PIN</td>
                <td style="padding-top: 8px;"><span class="box code">{{ $c->pin }}</span></td>
            </tr>
        </table>
        <ol class="steps">
            <li>Bring this slip to the exam. Keep the PIN private.</li>
            <li>On the exam computer, open <strong>{{ $examUrl }}</strong> and type the exam number and PIN.</li>
            <li>Read the instructions, then press Start. The timer starts then and cannot be paused.</li>
        </ol>
    </div>
@endforeach
</body></html>
