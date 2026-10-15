@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $events = \App\Website\LiveData::events($school, (int) $b['count']);
@endphp
@if ($events->isNotEmpty() || $preview)
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    @if ($events->isEmpty())
        <p>No upcoming events. Publish an event in News and events and it shows here.</p>
    @else
        <ul class="live-events">
            @foreach ($events as $e)
                <li><a href="{{ $e['url'] }}"><time datetime="{{ $e['date']->toDateString() }}"><b>{{ $e['date']->format('j') }}</b><span>{{ $e['date']->format('M Y') }}</span></time>
                    <span class="live-ev-body"><strong>{{ $e['title'] }}</strong>@if ($e['place'])<small>{{ $e['place'] }}</small>@endif @if ($e['excerpt'])<em>{{ $e['excerpt'] }}</em>@endif</span></a></li>
            @endforeach
        </ul>
    @endif
</div></section>
@endif
