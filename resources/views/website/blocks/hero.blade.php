<section class="hero">
    <div class="wrap">
        <h1>{{ $b['heading'] }}</h1>
        @if ($b['subheading'])<p>{{ $b['subheading'] }}</p>@endif
        @if ($b['button'] === 'apply')<a class="btn" href="{{ route('public.admission.show', $school) }}">Apply online</a>
        @elseif ($b['button'] === 'login')<a class="btn" href="{{ route('login') }}">Sign in</a>@endif
    </div>
</section>
