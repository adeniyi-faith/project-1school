@php
    // One design drives every card in the file: colours, size and layout come from the school's settings
    $d = $cards[0]['design'];
    $t = $d['template'];
    $fs = $d['font_size'] - ($t === 'compact' ? 1 : 0);
    $pad = $t === 'compact' ? '2px 3px' : '3px 4px';
@endphp
<style>
    @page { margin: {{ $t === 'compact' ? '20px 22px' : '28px 30px' }}; }
    body { font-family: DejaVu Sans, sans-serif; font-size: {{ $fs }}px; color: #1f2937; }
    .card { page-break-after: always; position: relative; }
    .card:last-child { page-break-after: auto; }
    .muted { color: #6b7280; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 8px; @if($t === 'modern') border-bottom: 1px solid {{ $d['accent'] }}; @else border-bottom: 2px solid {{ $d['primary'] }}; @endif padding-bottom: 6px; }
    .head td { vertical-align: middle; }
    .head.center td { text-align: center; }
    .logo { width: {{ $t === 'compact' ? 48 : 62 }}px; height: {{ $t === 'compact' ? 48 : 62 }}px; }
    .school { font-size: {{ $fs + ($t === 'compact' ? 5 : 8) }}px; font-weight: bold; color: {{ $d['primary'] }}; margin: 0; @if($t === 'modern') letter-spacing: 0.5px; @endif }
    .title { text-align: center; font-size: {{ $fs + 2 }}px; font-weight: bold; letter-spacing: 1px; margin: 6px 0 8px; text-transform: uppercase;
        @if($t === 'modern') color: #fff; background: {{ $d['primary'] }}; padding: 4px; border-radius: 4px; @else color: {{ $d['primary'] }}; @endif }
    .note { text-align: center; color: #b91c1c; font-size: {{ $fs - 1 }}px; margin: -4px 0 8px; }
    table.grid { width: 100%; border-collapse: collapse; margin-bottom: {{ $t === 'compact' ? 5 : 8 }}px; }
    table.grid th { padding: {{ $pad }}; font-size: {{ $fs - 1 }}px; text-align: center;
        @if($t === 'modern') background: #f3f4f6; color: {{ $d['primary'] }}; border-bottom: 2px solid {{ $d['accent'] }}; @else background: {{ $d['primary'] }}; color: #fff; @endif }
    table.grid td { padding: {{ $pad }}; text-align: center;
        @if($t === 'modern') border-bottom: 1px solid #e5e7eb; @else border: 1px solid #d1d5db; @endif }
    @if($t === 'modern') table.grid tr:nth-child(even) td { background: #fafafa; } @endif
    table.grid td.left, table.grid th.left { text-align: left; }
    table.info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.info td { padding: 2px 4px; vertical-align: top; }
    table.info td.k { color: #6b7280; width: 11%; white-space: nowrap; }
    table.info td.v { font-weight: bold; width: 20%; white-space: nowrap; }
    .photo { width: 72px; height: 84px; border: 1px solid #d1d5db; }
    .box { border: 1px solid #d1d5db; padding: {{ $t === 'compact' ? '3px 5px' : '5px 7px' }}; margin-bottom: 6px; @if($t === 'modern') border-left: 3px solid {{ $d['accent'] }}; border-top: 0; border-right: 0; border-bottom: 0; background: #f9fafb; @endif }
    .box h4 { margin: 0 0 3px; font-size: {{ $fs - 1 }}px; text-transform: uppercase; color: {{ $d['primary'] }}; }
    .cols { width: 100%; border-collapse: collapse; }
    .cols > tbody > tr > td { vertical-align: top; padding: 0 3px; }
    .cols > tbody > tr > td:first-child { padding-left: 0; }
    .cols > tbody > tr > td:last-child { padding-right: 0; }
    .preview { position: absolute; top: 320px; left: 120px; font-size: 90px; color: #fca5a5; opacity: 0.35; transform: rotate(-30deg); white-space: nowrap; }
    .watermark { position: absolute; top: 260px; left: 50%; margin-left: -150px; width: 300px; opacity: 0.07; }
    .key { font-size: {{ $fs - 2 }}px; color: #4b5563; margin-top: 4px; }
    .footer { font-size: {{ $fs - 2 }}px; color: #6b7280; text-align: center; margin-top: 6px; }
    .signs { width: 100%; border-collapse: collapse; margin-top: {{ $t === 'compact' ? 6 : 12 }}px; }
    .signs td { vertical-align: bottom; text-align: center; padding: 0 6px; }
    .sigimg { height: 34px; max-width: 140px; }
    .stamp { height: 70px; }
    .line { border-top: 1px solid #9ca3af; padding-top: 2px; font-size: {{ $fs - 2 }}px; color: #374151; }
    .owed { color: #b91c1c; font-weight: bold; }
</style>
