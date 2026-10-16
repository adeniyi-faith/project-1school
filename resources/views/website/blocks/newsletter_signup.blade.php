@php
    $nlReady = \App\Newsletter\NewsletterSettings::resolve($school->id) !== null;
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
@endphp
@if ($nlReady)
<section class="sec" id="newsletter"><div class="wrap">
    <div class="sec-head">
        @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
        @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif
    </div>
    @if (session('newsletter'))
        <p class="live-ok" role="status">{{ session('newsletter') }}</p>
    @else
        <form class="live-form" method="post" action="{{ route('site.newsletter.subscribe', $school->slug) }}">
            @csrf
            @include('website.partials.guard', ['school' => $school, 'kind' => 'newsletter'])
            <label>Your name (optional)<input name="name" maxlength="100" value="{{ old('name') }}" autocomplete="name"></label>
            <label>Email<input name="email" type="email" required maxlength="150" value="{{ old('email') }}" autocomplete="email"></label>
            @if (($b['interests'] ?? 'yes') === 'yes')
                <fieldset class="live-wide" style="border:0;padding:0;margin:0">
                    <legend style="font-weight:600;margin-bottom:.35rem">I would like to hear about</legend>
                    @foreach (\App\Models\NewsletterSubscriber::INTERESTS as $key => $label)
                        <label style="display:inline-flex;gap:.4rem;align-items:center;margin-right:1rem"><input type="checkbox" name="interests[]" value="{{ $key }}" @checked(in_array($key, (array) old('interests', []), true)) style="width:auto"> {{ $label }}</label>
                    @endforeach
                </fieldset>
            @endif
            <label class="live-wide" style="display:flex;gap:.55rem;align-items:flex-start;font-weight:400">
                <input type="checkbox" name="consent" value="1" required @checked(old('consent')) style="width:auto;margin-top:.25rem">
                <span>{{ \App\Newsletter\NewsletterService::CONSENT }}@if (! empty($privacyUrl)) <a href="{{ $privacyUrl }}">Privacy policy</a>@endif</span>
            </label>
            @if ($errors->any())<p class="live-err" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn--solid" type="submit">{{ $b['button'] ?: 'Sign up' }}</button>
        </form>
    @endif
</div></section>
@endif
