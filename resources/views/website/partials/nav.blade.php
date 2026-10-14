<nav class="menu" id="menu" aria-label="Main">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a class="menu-signin" href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a>
            @if ($site->option('apply_button'))<a class="btn btn--solid" href="{{ route('public.admission.show', $school) }}">Apply online</a>@endif
        </nav>
