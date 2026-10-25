<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h2 { margin: 0 0 2px; }
    p { margin: 0 0 10px; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th { background: #4f46e5; color: #fff; padding: 5px 7px; text-align: left; }
    td { padding: 4px 7px; border-bottom: 1px solid #e5e7eb; }
    .r { text-align: right; }
    .total td { font-weight: 700; background: #f0fdf4; }
    .rev td { color: #999; text-decoration: line-through; }
    .sign { margin-top: 30px; }
    .sign span { display: inline-block; width: 45%; border-top: 1px solid #555; padding-top: 3px; margin-right: 4%; }
</style>
</head>
<body>
<h2>{{ $school }} — Today's Collections</h2>
<p>Money received on {{ \Carbon\Carbon::parse($date)->format('l, j F Y') }} (from invoices). Printed by {{ $printedBy }} on {{ now()->format('j M Y, H:i') }}.</p>

<table>
    <thead><tr><th>How it was paid</th><th class="r">Payments</th><th class="r">Amount (₦)</th></tr></thead>
    <tbody>
    @forelse($by_method as $m)
        <tr><td>{{ $m['label'] }}</td><td class="r">{{ $m['count'] }}</td><td class="r">{{ number_format($m['total'], 2) }}</td></tr>
    @empty
        <tr><td colspan="3">No payments were recorded on this day.</td></tr>
    @endforelse
        <tr class="total"><td>Total received</td><td class="r">{{ $count }}</td><td class="r">{{ number_format($total, 2) }}</td></tr>
    </tbody>
</table>

@if(count($payments))
<table>
    <thead><tr><th>Receipt</th><th>Student</th><th>Fee</th><th>Method</th><th>Taken by</th><th class="r">Amount (₦)</th></tr></thead>
    <tbody>
    @foreach($payments as $p)
        <tr class="{{ $p['reversed'] ? 'rev' : '' }}">
            <td>{{ $p['receipt'] }}</td>
            <td>{{ $p['student'] }}<br><span style="color:#777">{{ $p['class'] }} · {{ $p['admission_no'] }}</span></td>
            <td>{{ $p['fee'] }}</td>
            <td>{{ $p['method_label'] }}</td>
            <td>{{ $p['by'] ?? 'Online' }}</td>
            <td class="r">{{ number_format($p['amount'], 2) }}{{ $p['reversed'] ? ' (reversed)' : '' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@if($reversed_count)
<p>{{ $reversed_count }} payment{{ $reversed_count === 1 ? ' was' : 's were' }} reversed and {{ $reversed_count === 1 ? 'is' : 'are' }} not counted in the totals above.</p>
@endif
@endif

<div class="sign"><span>Prepared by</span><span>Checked by</span></div>
</body>
</html>
