@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $files = collect($b['items'] ?? [])->filter(fn ($i) => $i['title'] !== '' && $i['link'] !== ''); @endphp
@if ($files->isNotEmpty() || $preview)
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    @if ($files->isEmpty())
        <p>No files added yet.</p>
    @else
        <ul class="live-files">
            @foreach ($files as $f)<li><a href="{{ $f['link'] }}" target="_blank" rel="noopener"><span>{{ $f['title'] }}</span><small>Download &darr;</small></a></li>@endforeach
        </ul>
    @endif
</div></section>
@endif
