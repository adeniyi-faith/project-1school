<!doctype html>
<html><head><meta charset="utf-8">
@php
    // Card size is the bank-card size (CR80, 85.6 x 54 mm) that ID card holders, lanyards and PVC printers use
    $W = $landscape ? 85.6 : 54; $H = $landscape ? 54 : 85.6;
    $single = $layout === 'single';
    // A4 sheets: landscape cards 2 across x 5 down, portrait cards 3 across x 3 down
    $cols = $landscape ? 2 : 3; $rows = $landscape ? 5 : 3; $gap = $landscape ? 3 : 6;
    $left = (210 - ($cols * $W + ($cols - 1) * $gap)) / 2; $top = (297 - ($rows * $H + ($rows - 1) * $gap)) / 2;
    $perPage = $single ? 1 : $cols * $rows;
    $pages = array_chunk($cards, $perPage);
    $back = $show['back'];
    $screen = $screen ?? false;
    // one card per page: the page box is a hair smaller than the paper, or the PDF maker's rounding spills a sliver onto a blank extra page
    $pageW = $single ? $W - 0.4 : 210; $pageH = $single ? $H - 0.6 : 297;
    $mm = fn ($v) => round($v, 2).'mm';
    $esc = fn ($v) => e((string) $v);
    $rowsFor = function (array $c) use ($show) {
        $r = [];
        if ($show['class'] && $c['class']) $r[$c['class_label'] ?? 'Class'] = e($c['class']);
        if ($show['house'] && $c['house']) $r['House'] = '<span class="dot" style="background:'.e($c['house']['colour']).'"></span>'.e($c['house']['name']);
        if ($show['gender'] && $c['gender']) $r['Gender'] = e($c['gender']);
        if ($show['date_of_birth'] && $c['date_of_birth']) $r['Born'] = e($c['date_of_birth']);
        if ($show['blood_group'] && $c['blood_group']) $r['Blood'] = e($c['blood_group']);
        if ($show['admission_date'] && $c['admission_date']) $r[$c['admission_label'] ?? 'Admitted'] = e($c['admission_date']);
        if ($show['guardian_phone'] && $c['guardian_phone']) $r[$c['phone_label'] ?? 'Parent'] = e($c['guardian_phone']);
        if ($show['address'] && $c['address']) $r['Address'] = e(\Illuminate\Support\Str::limit($c['address'], 40));
        return array_slice($r, 0, 6, true);
    };
@endphp
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #111827; }
    .page { position: relative; width: {{ $mm($pageW) }}; height: {{ $mm($pageH) }}; overflow: hidden; page-break-after: always; }
    .page:last-child { page-break-after: auto; }
    .card { position: absolute; width: {{ $mm($W) }}; height: {{ $mm($H) }}; overflow: hidden; background: #fff; border-radius: 2.5mm; }
    .cut { border: 0.2mm dashed #9ca3af; }
    .abs { position: absolute; }
    .head { left: 0; right: 0; top: 0; }
    .classic .head, .band .head { background: {{ $color }}; color: #fff; }
    .modern .head { background: #fff; color: {{ $color }}; border-bottom: 0.5mm solid {{ $accent }}; }
    .modern .bar { left: 0; top: 0; bottom: 0; width: 2.2mm; background: {{ $color }}; }
    .logo { object-fit: contain; }
    .school { font-weight: bold; text-transform: uppercase; line-height: 1.15; }
    .motto { font-style: italic; opacity: 0.9; line-height: 1.2; }
    .title { left: 0; right: 0; text-align: center; font-weight: bold; letter-spacing: 0.4mm; color: #fff; background: {{ $accent }}; }
    .modern .title { background: none; color: {{ $accent }}; }
    .photo { border: 0.5mm solid {{ $color }}; background: #eef2ff; text-align: center; overflow: hidden; }
    .modern .photo { border-radius: 2mm; }
    .band .photo { border: 0.8mm solid #fff; }
    .photo img { width: 100%; height: 100%; object-fit: cover; }
    .initials { font-weight: bold; color: {{ $color }}; }
    .name { font-weight: bold; text-transform: uppercase; color: {{ $color }}; line-height: 1.1; }
    .muted { color: #4b5563; }
    table.facts { border-collapse: collapse; }
    table.facts td { padding: 0.25mm 0; vertical-align: top; }
    table.facts td.k { color: #6b7280; padding-right: 1.2mm; white-space: nowrap; }
    table.facts td.v { font-weight: bold; }
    .dot { display: inline-block; width: 1.8mm; height: 1.8mm; border-radius: 1mm; margin-right: 0.8mm; }
    .qr { background: #fff; }
    .sign img { max-height: 5mm; max-width: 18mm; }
    .sign .line { border-top: 0.2mm solid #6b7280; font-size: 4.5pt; color: #4b5563; padding-top: 0.3mm; }
    .stripe { left: 0; right: 0; bottom: 0; height: 1.8mm; }
    .backtext { font-size: 5.6pt; line-height: 1.35; color: #374151; }
    .watermark { opacity: 0.07; }
    .sample { color: #dc2626; font-weight: bold; }
    @if ($screen)
        body { background: #e5e7eb; padding: 12px; }
        #sheet .page { margin: 0 auto 14px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
    @endif
</style>
</head><body><div id="sheet">
@foreach ($pages as $chunk)
    {{-- Fronts --}}
    <div class="page">
        @foreach ($chunk as $i => $c)
            @php
                $col = $i % $cols; $row = intdiv($i, $cols);
                $x = $single ? 0 : $left + $col * ($W + $gap); $y = $single ? 0 : $top + $row * ($H + $gap);
                $facts = $rowsFor($c);
                $stripe = $show['house'] && $c['house'] ? $c['house']['colour'] : $accent;
                $long = mb_strlen($c['name']) > ($landscape ? 26 : 20);
            @endphp
            <div class="card {{ $template }} {{ $single ? '' : 'cut' }}" style="left: {{ $mm($x) }}; top: {{ $mm($y) }};">
                @if ($template === 'modern')<div class="abs bar"></div>@endif
                @if ($landscape)
                    @php $hh = $template === 'band' ? 15 : 12.5; @endphp
                    <div class="abs head" style="height: {{ $mm($hh) }};"></div>
                    @if ($show['logo'] && $school['logo'])<img class="abs logo" src="{{ $school['logo'] }}" style="left: 4mm; top: 1.8mm; width: 9mm; height: 9mm;">@endif
                    <div class="abs school" style="left: {{ $show['logo'] && $school['logo'] ? '15mm' : '4mm' }}; right: 3mm; top: 2.2mm; font-size: 7pt; {{ $template === 'modern' ? '' : 'color:#fff;' }}">{{ $school['name'] }}</div>
                    @if ($school['motto'])<div class="abs motto" style="left: {{ $show['logo'] && $school['logo'] ? '15mm' : '4mm' }}; right: 3mm; top: 6.6mm; font-size: 4.8pt; {{ $template === 'modern' ? 'color:'.$color : 'color:#fff' }};">{{ $school['motto'] }}</div>@endif
                    <div class="abs title" style="top: {{ $mm($hh) }}; height: 3.6mm; line-height: 3.6mm; font-size: 5.4pt;">{{ $design->title }}</div>
                    @php $pt = $hh + 5; @endphp
                    <div class="abs photo" style="left: 4mm; top: {{ $mm($pt) }}; width: 19mm; height: 23mm;">
                        @if ($c['photo'])<img src="{{ $c['photo'] }}">@else<div class="initials" style="font-size: 14pt; padding-top: 6mm;">{{ $c['initials'] }}</div>@endif
                    </div>
                    <div class="abs name" style="left: 26mm; right: 21mm; top: {{ $mm($pt) }}; font-size: {{ $long ? '6.4pt' : '7.6pt' }};">{{ $c['name'] }}</div>
                    <div class="abs muted" style="left: 26mm; right: 21mm; top: {{ $mm($pt + ($long ? 6 : 3.8)) }}; font-size: 5.4pt;">{{ $c['admission_no'] }}</div>
                    <table class="abs facts" style="left: 26mm; top: {{ $mm($pt + ($long ? 9.2 : 7)) }}; font-size: 5.2pt; width: 37mm;">
                        @foreach ($facts as $k => $v)<tr><td class="k">{{ $k }}</td><td class="v">{!! $v !!}</td></tr>@endforeach
                    </table>
                    @if ($show['qr'] && $c['qr'])<img class="abs qr" src="{{ $c['qr'] }}" style="right: 3mm; top: {{ $mm($pt) }}; width: 16mm; height: 16mm;">@endif
                    @if ($show['valid_until'] && $c['valid_until'])<div class="abs" style="right: 2mm; width: 20mm; text-align: center; top: {{ $mm($pt + 16.6) }}; font-size: 4.6pt;">Valid until<br><b>{{ $c['valid_until'] }}</b></div>@endif
                    @if ($show['signature'] && $signer)
                        <div class="abs sign" style="right: 3mm; width: 17mm; top: {{ $mm($H - 9.6) }}; text-align: center;">
                            @if ($signer['signature'])<img src="{{ $signer['signature'] }}">@else<div style="height: 5mm;"></div>@endif
                            <div class="line">{{ $signer['label'] }}</div>
                        </div>
                    @endif
                    <div class="abs muted" style="left: 4mm; top: {{ $mm($H - 6) }}; font-size: 4.6pt;">{{ $c['card_no'] }} @if ($c['sample'])<span class="sample">SAMPLE</span>@endif</div>
                @else
                    @php $hh = $template === 'band' ? 30 : 16.5; @endphp
                    <div class="abs head" style="height: {{ $mm($hh) }};"></div>
                    @if ($show['logo'] && $school['logo'])<img class="abs logo" src="{{ $school['logo'] }}" style="left: 3.5mm; top: 2.2mm; width: 9.5mm; height: 9.5mm;">@endif
                    <div class="abs school" style="left: {{ $show['logo'] && $school['logo'] ? '14.5mm' : '3.5mm' }}; right: 2.5mm; top: 2.6mm; font-size: 6.2pt; {{ $template === 'modern' ? '' : 'color:#fff;' }}">{{ $school['name'] }}</div>
                    @if ($school['motto'])<div class="abs motto" style="left: {{ $show['logo'] && $school['logo'] ? '14.5mm' : '3.5mm' }}; right: 2.5mm; top: 9.6mm; font-size: 4.6pt; {{ $template === 'modern' ? 'color:'.$color : 'color:#fff' }};">{{ $school['motto'] }}</div>@endif
                    @php $tt = $template === 'band' ? 13 : $hh; @endphp
                    <div class="abs title" style="top: {{ $mm($tt) }}; height: 3.8mm; line-height: 3.8mm; font-size: 5.4pt; {{ $template === 'band' ? 'background:none;' : '' }}">{{ $design->title }}</div>
                    @php $pt = $template === 'band' ? 18.5 : $hh + 5.5; @endphp
                    <div class="abs photo" style="left: 16mm; top: {{ $mm($pt) }}; width: 22mm; height: 26mm;">
                        @if ($c['photo'])<img src="{{ $c['photo'] }}">@else<div class="initials" style="font-size: 16pt; padding-top: 7.5mm;">{{ $c['initials'] }}</div>@endif
                    </div>
                    @php $nt = $pt + 27.5; @endphp
                    <div class="abs name" style="left: 2.5mm; right: 2.5mm; text-align: center; top: {{ $mm($nt) }}; font-size: {{ $long ? '6.2pt' : '7.6pt' }};">{{ $c['name'] }}</div>
                    <div class="abs muted" style="left: 2.5mm; right: 2.5mm; text-align: center; top: {{ $mm($nt + ($long ? 5.6 : 3.6)) }}; font-size: 5.4pt;">{{ $c['admission_no'] }}</div>
                    @php $ft = $nt + ($long ? 8.8 : 6.8); @endphp
                    <table class="abs facts" style="left: 4mm; top: {{ $mm($ft) }}; font-size: 5pt; width: {{ $show['qr'] ? '31mm' : '46mm' }};">
                        @foreach ($facts as $k => $v)<tr><td class="k">{{ $k }}</td><td class="v">{!! $v !!}</td></tr>@endforeach
                    </table>
                    @if ($show['qr'] && $c['qr'])<img class="abs qr" src="{{ $c['qr'] }}" style="right: 3mm; top: {{ $mm($ft) }}; width: 15mm; height: 15mm;">@endif
                    @if ($show['valid_until'] && $c['valid_until'])<div class="abs" style="right: 1.5mm; width: 18mm; text-align: center; top: {{ $mm($ft + 15.5) }}; font-size: 4.4pt;">Valid until <b>{{ $c['valid_until'] }}</b></div>@endif
                    @if ($show['signature'] && $signer)
                        <div class="abs sign" style="left: 4mm; width: 18mm; top: {{ $mm($H - 10) }}; text-align: center;">
                            @if ($signer['signature'])<img src="{{ $signer['signature'] }}">@else<div style="height: 5mm;"></div>@endif
                            <div class="line">{{ $signer['label'] }}</div>
                        </div>
                    @endif
                    <div class="abs muted" style="right: 3mm; top: {{ $mm($H - 5.2) }}; font-size: 4.4pt;">{{ $c['card_no'] }} @if ($c['sample'])<span class="sample">SAMPLE</span>@endif</div>
                @endif
                <div class="abs stripe" style="background: {{ $stripe }};"></div>
            </div>
        @endforeach
    </div>

    {{-- Backs: on sheets the columns are mirrored, so printing both sides (flip on the long edge) lines each back up with its front --}}
    @if ($back)
        <div class="page">
            @foreach ($chunk as $i => $c)
                @php
                    $col = $cols - 1 - ($i % $cols); $row = intdiv($i, $cols);
                    $x = $single ? 0 : $left + $col * ($W + $gap); $y = $single ? 0 : $top + $row * ($H + $gap);
                @endphp
                <div class="card {{ $single ? '' : 'cut' }}" style="left: {{ $mm($x) }}; top: {{ $mm($y) }}; border: 0.4mm solid {{ $color }};">
                    @if ($school['logo'])<img class="abs watermark" src="{{ $school['logo'] }}" style="left: {{ $mm($W / 2 - 15) }}; top: {{ $mm($H / 2 - 15) }}; width: 30mm; height: 30mm;">@endif
                    <div class="abs" style="left: 4mm; right: 4mm; top: 3.5mm; text-align: center; font-size: 6.4pt; font-weight: bold; color: {{ $color }}; text-transform: uppercase;">{{ $school['name'] }}</div>
                    <div class="abs backtext" style="left: 4mm; right: 4mm; top: 9mm;">
                        @foreach (preg_split('/\R+/', trim((string) $design->back_text)) as $para)
                            @if (trim($para) !== '')<p style="margin: 0 0 1.4mm;">{{ $para }}</p>@endif
                        @endforeach
                    </div>
                    <div class="abs" style="left: 4mm; right: 4mm; bottom: 4mm; text-align: center; font-size: 5pt; color: #374151; line-height: 1.35;">
                        @if ($school['address']){{ $school['address'] }}<br>@endif
                        {{ collect([$school['phone'], $school['email']])->filter()->implode(' · ') }}<br>
                        <b>{{ $c['card_no'] }}</b>@if ($c['valid_until']) · valid until {{ $c['valid_until'] }}@endif
                    </div>
                    <div class="abs stripe" style="background: {{ $color }}; height: 1.2mm;"></div>
                </div>
            @endforeach
        </div>
    @endif
@endforeach
</div></body></html>
