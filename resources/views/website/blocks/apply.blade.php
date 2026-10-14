<section class="block"><div class="wrap" style="text-align:center">
    @if ($b['heading'])<h2>{{ $b['heading'] }}</h2>@endif
    @if ($b['body'])<p>{{ $b['body'] }}</p>@endif
    <a class="btn" href="{{ route('public.admission.show', $school) }}">Apply online</a>
</div></section>
