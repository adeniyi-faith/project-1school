@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $classes = \App\Website\LiveData::fees($school);
@endphp
@if ($classes->isNotEmpty() || $preview)
<section class="sec"><div class="wrap">
    <div class="sec-head">
        @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
        @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif
    </div>
    @if ($classes->isEmpty())
        <p>No fees are set up yet. Add them in Fees &gt; Fee structures and they show here.</p>
    @else
        <div class="live-grid">
            @foreach ($classes as $c)
                <div class="card card--plain live-card"><div class="card-body">
                    <h3>{{ $c['class'] }}</h3>
                    <table class="live-table">
                        @foreach ($c['lines'] as $l)<tr><td>{{ $l['name'] }} <small>{{ $l['when'] }}</small></td><td>&#8358;{{ number_format($l['amount']) }}</td></tr>@endforeach
                        @if ($c['term_total'] > 0)<tr class="live-total"><td>Each term</td><td>&#8358;{{ number_format($c['term_total']) }}</td></tr>@endif
                    </table>
                </div></div>
            @endforeach
        </div>
        @if ($b['note'])<p class="live-note">{{ $b['note'] }}</p>@endif
    @endif
</div></section>
@endif
