{{-- A small newsletter sign-up above the footer on every page and every theme. Shown only when the school switched it on
     (Website > Settings), the Newsletter extension is usable and an email service is ready. Posts to the same address as the
     sign-up section, so consent, confirmation and the safety kit are the same. --}}
@php
    $fsuOn = $site->option('footer_signup')
        && ! ($preview ?? false)
        && \App\Website\Extensions::enabled($school->id, 'newsletter')
        && \App\Newsletter\NewsletterSettings::resolve($school->id) !== null;
    $fsuDone = session('newsletter') && session('newsletter_at') === 'footer';
@endphp
@if ($fsuOn)
<section class="fsu" id="footer-signup" aria-labelledby="fsu-h">
    <div class="wrap fsu-in">
        <div class="fsu-text">
            <h2 id="fsu-h">{{ $site->option('footer_signup_text') ?: 'Get our newsletter' }}</h2>
            <p>News, events and admission updates from {{ $school->name }}.</p>
        </div>
        @if ($fsuDone)
            <p class="live-ok fsu-ok" role="status">{{ session('newsletter') }}</p>
        @else
            <form class="fsu-form" method="post" action="{{ route('site.newsletter.subscribe', $school->slug) }}">
                @csrf
                <input type="hidden" name="from" value="footer">
                @include('website.partials.guard', ['school' => $school, 'kind' => 'newsletter'])
                <div class="fsu-row">
                    <label class="fsu-label"><span class="sr-only">Email address</span><input name="email" type="email" required maxlength="150" placeholder="Your email address" autocomplete="email" value="{{ old('from') === 'footer' ? old('email') : '' }}"></label>
                    <button class="btn btn--solid" type="submit">Sign up</button>
                </div>
                <label class="fsu-consent">
                    <input type="checkbox" name="consent" value="1" required @checked(old('from') === 'footer' && old('consent'))>
                    <span>{{ \App\Newsletter\NewsletterService::CONSENT }}@if (! empty($privacyUrl)) <a href="{{ $privacyUrl }}">Privacy policy</a>@endif</span>
                </label>
                @if ($errors->footer->any())<p class="live-err" role="alert">{{ $errors->footer->first() }}</p>@endif
            </form>
        @endif
    </div>
</section>
@endif
