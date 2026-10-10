<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h2 { margin-bottom: 4px; }
    p  { margin: 0 0 12px; color: #555; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #4f46e5; color: #fff; padding: 6px 8px; text-align: left; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) td { background: #f9fafb; }
    .paid     { color: #16a34a; font-weight: 600; }
    .pending  { color: #d97706; font-weight: 600; }
    .partial  { color: #2563eb; font-weight: 600; }
    .total-row { font-weight: 700; background: #f0fdf4; }
</style>
</head>
<body>
<h2>Finance Report — Fee Payments</h2>
<p>Period: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} to {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
   &nbsp;|&nbsp; Total received: {{ number_format($payments->where('reversed', false)->sum('amount'), 2) }}</p>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student</th>
            <th>Admission No</th>
            <th>Fee</th>
            <th>Receipt</th>
            <th>Method</th>
            <th>Amount</th>
            <th>Paid On</th>
        </tr>
    </thead>
    <tbody>
        @foreach($payments as $i => $p)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $p['student'] }}</td>
            <td>{{ $p['admission_no'] }}</td>
            <td>{{ $p['fee'] }}</td>
            <td>{{ $p['receipt'] }}</td>
            <td>{{ ucwords(str_replace('_', ' ', (string) $p['method'])) }}</td>
            <td>{{ number_format($p['amount'], 2) }}{{ $p['reversed'] ? ' (reversed)' : '' }}</td>
            <td>{{ $p['date'] ? \Carbon\Carbon::parse($p['date'])->format('d M Y') : '—' }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="6">Total received (reversed payments left out)</td>
            <td>{{ number_format($payments->where('reversed', false)->sum('amount'), 2) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
</body>
</html>
