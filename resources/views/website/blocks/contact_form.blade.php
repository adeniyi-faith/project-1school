@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec" id="message"><div class="wrap">
    <div class="sec-head">
        @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
        @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif
    </div>
    @if (session('contacted'))
        <p class="live-ok" role="status">{{ session('contacted') }}</p>
    @else
        <form class="live-form" method="post" action="{{ route('site.contact', $school->slug) }}">
            @csrf
            <div class="live-hp" aria-hidden="true"><label>Leave this empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
            <label>Your name<input name="name" required maxlength="150" value="{{ old('name') }}" autocomplete="name"></label>
            <label>Phone<input name="phone" required maxlength="25" value="{{ old('phone') }}" inputmode="tel" autocomplete="tel"></label>
            <label>Email (optional)<input name="email" type="email" maxlength="150" value="{{ old('email') }}" autocomplete="email"></label>
            <label class="live-wide">Your message<textarea name="message" required maxlength="1000" rows="5">{{ old('message') }}</textarea></label>
            @if ($errors->any())<p class="live-err" role="alert">{{ $errors->first() }}</p>@endif
            <button class="btn btn--solid" type="submit">Send message</button>
        </form>
    @endif
</div></section>
