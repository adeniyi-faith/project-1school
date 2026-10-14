<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Login details</title>
<style>
    @page { margin: 10mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
    table.grid { width: 100%; border-collapse: separate; border-spacing: 6px; table-layout: fixed; }
    td.slip { border: 1px dashed #6b7280; padding: 8px 10px; vertical-align: top; height: 118px; }
    .school { font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 0 0 1px; }
    .role { font-size: 9px; color: #4b5563; margin: 0 0 6px; }
    .name { font-size: 11.5px; font-weight: bold; margin: 0; }
    .class { color: #4b5563; margin: 0 0 6px; }
    .k { color: #6b7280; font-size: 8.5px; margin: 4px 0 0; }
    .v { font-family: DejaVu Sans Mono, monospace; font-size: 11px; font-weight: bold; margin: 0; word-wrap: break-word; }
    .foot { font-size: 8px; color: #6b7280; margin-top: 6px; }
</style>
</head>
<body>
@php $pairs = array_chunk($lines, 2); @endphp
<table class="grid">
@foreach ($pairs as $i => $pair)
    <tr>
    @foreach ($pair as $l)
        <td class="slip">
            <p class="school">{{ $school['name'] }}</p>
            <p class="role">{{ $l['role'] }} portal login</p>
            <p class="name">{{ $l['name'] }}</p>
            <p class="class">{{ $l['class'] }}@if ($l['admission_no']) · {{ $l['admission_no'] }}@endif</p>
            <p class="k">Username</p><p class="v">{{ $l['username'] }}</p>
            <p class="k">Password</p><p class="v">{{ $l['password'] }}</p>
            <p class="foot">Sign in at {{ $loginUrl }}. Keep this private.</p>
        </td>
    @endforeach
    @if (count($pair) === 1)<td></td>@endif
    </tr>
    @if (($i + 1) % 6 === 0 && ! $loop->last)
</table>
<div style="page-break-after: always"></div>
<table class="grid">
    @endif
@endforeach
</table>
</body>
</html>
