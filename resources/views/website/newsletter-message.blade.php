@extends('website.layout')

@section('main')
<section class="pagehero"><div class="wrap">
    <p class="hero-tag"><i></i>Newsletter</p>
    <h1>{{ $pageTitle }}</h1>
</div></section>

<section class="formwrap"><div class="wrap">
    <div class="panel" style="max-width:36rem">
        <p>{{ $messageText }}</p>
        @if ($button)
            <form method="post" action="{{ $button['action'] }}" style="margin-top:1rem">
                @csrf
                <button class="btn btn--solid" type="submit">{{ $button['label'] }}</button>
            </form>
        @endif
        <p class="sidelink"><a href="{{ \App\Website\SiteRenderer::homeUrl($school) }}">Go to the {{ $school->name }} website</a></p>
    </div>
</div></section>
@endsection
