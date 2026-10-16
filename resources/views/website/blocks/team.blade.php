@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $team = \App\Website\LiveData::team($school);
@endphp
@if ($team->isNotEmpty() || $preview)
<section class="sec sec--soft"><div class="wrap">
    <div class="sec-head">
        @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
        @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif
    </div>
    @if ($team->isEmpty())
        <p>Nobody is shown yet. Open a staff page and tick "Show on the school website".</p>
    @else
        <div class="live-team">
            @foreach ($team as $m)
                <div class="live-person">
                    @if ($m['photo'])<img src="{{ $m['photo'] }}" alt="{{ $m['name'] }}" loading="lazy">@else<span class="live-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($m['name'], 0, 1)) }}</span>@endif
                    <b>{{ $m['name'] }}</b>@if ($m['role'])<span>{{ $m['role'] }}</span>@endif
                </div>
            @endforeach
        </div>
    @endif
</div></section>
@endif
