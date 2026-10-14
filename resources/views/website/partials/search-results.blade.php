@if ($query === '')
    <p>Type a few words above to search our pages, news and photo albums.</p>
@elseif ($tooShort)
    <p>Please type at least two letters.</p>
@elseif ($results->isEmpty())
    <p>Nothing found for <b>&ldquo;{{ $query }}&rdquo;</b>. Try a different word, or check the spelling.</p>
@else
    <p class="result-count">{{ $results->count() }} {{ $results->count() === 1 ? 'result' : 'results' }} for <b>&ldquo;{{ $query }}&rdquo;</b></p>
    <ul class="results">
        @foreach ($results as $r)
            <li class="result {{ $r['image'] ? 'result-pic' : '' }}">
                @if ($r['image'])<img src="{{ $r['image'] }}" alt="" loading="lazy" decoding="async" width="352" height="220">@endif
                <div>
                    <span class="tag">{{ $r['kind'] }}</span>
                    <h2><a href="{{ $r['url'] }}">{{ $r['title'] }}</a></h2>
                    @if ((string) $r['snippet'] !== '')<p>{{ $r['snippet'] }}</p>@endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
