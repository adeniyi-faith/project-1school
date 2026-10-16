@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $d = \App\Website\LiveData::dates($school);
@endphp
@if ($d['terms'] || $d['holidays'] || $preview)
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    @if (! $d['terms'] && ! $d['holidays'])
        <p>No term dates are set yet. Add them in School Setup &gt; Terms and they show here.</p>
    @else
        <div class="live-grid">
            @if ($d['terms'])
            <div class="card card--plain live-card"><div class="card-body">
                <h3>Terms</h3>
                <table class="live-table">
                    @foreach ($d['terms'] as $t)<tr><td>{{ $t['name'] }}@if ($t['year']) <small>{{ $t['year'] }}</small>@endif @if ($t['now'])<small class="live-now">now</small>@endif</td><td>{{ $t['start']->format('j M Y') }} to {{ $t['end']->format('j M Y') }}</td></tr>@endforeach
                </table>
            </div></div>
            @endif
            @if ($d['holidays'])
            <div class="card card--plain live-card"><div class="card-body">
                <h3>Coming holidays</h3>
                <table class="live-table">
                    @foreach ($d['holidays'] as $h)<tr><td>{{ $h['name'] }}</td><td>{{ $h['date']->format('D j M Y') }}</td></tr>@endforeach
                </table>
            </div></div>
            @endif
        </div>
    @endif
</div></section>
@endif
