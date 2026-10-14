<section class="block"><div class="wrap">
    @if ($b['heading'])<h2>{{ $b['heading'] }}</h2>@endif
    <div class="grid">
        @foreach ($b['items'] as $item)
            <div class="card"><h3>{{ $item['title'] }}</h3><div>{{ $item['body'] }}</div></div>
        @endforeach
    </div>
</div></section>
