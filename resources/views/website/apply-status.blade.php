@extends('website.layout')

@section('main')
<section class="pagehero"><div class="wrap">
    <p class="hero-tag"><i></i>Admissions</p>
    <h1>Check your application</h1>
    <p>{{ $pageLead }}</p>
</div></section>

<section class="formwrap"><div class="wrap">
    <div class="formgrid">
        <div class="panel">
            @if ($inquiry)
                <h2>{{ $inquiry->student_name }}</h2>
                <p class="lede">Reference {{ $inquiry->reference }} · {{ $inquiry->class_interested }}</p>
                <div class="thanks">
                    <h2>{{ $stage['label'] }}</h2>
                    <p class="lede">{{ $stage['text'] }}</p>
                    @if ($inquiry->status === 'accepted' && ! $inquiry->family_accepted_at)
                        <form method="post" action="{{ route('public.admission.accept', $school) }}">
                            @csrf
                            <input type="hidden" name="reference" value="{{ $inquiry->reference }}">
                            <input type="hidden" name="phone" value="{{ $phone }}">
                            <button class="btn btn--solid" type="submit">Accept this place</button>
                        </form>
                    @endif
                </div>
            @else
                <h2>Find your application</h2>
                @if ($notFound)<div class="alert alert--bad" role="alert">We could not find an application with that reference and phone number. Check them and try again.</div>@endif
                <form method="get" action="{{ route('public.admission.status', $school) }}">
                    <label class="field"><span>Reference <em>*</em></span><input name="reference" value="{{ $reference }}" required maxlength="30" placeholder="APP-2026-0042" autocomplete="off"></label>
                    <label class="field"><span>Phone number you applied with <em>*</em></span><input type="tel" name="phone" value="{{ $phone }}" required maxlength="25" autocomplete="tel"></label>
                    <button class="btn btn--solid btn--block" type="submit">Check status</button>
                </form>
            @endif
        </div>
        <aside class="panel panel--soft">
            <h2>Need help?</h2>
            <p class="t">Your reference was shown after you applied and was emailed to you if you gave an email address.</p>
            @if ($school->phone)<p class="sidelink">Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></p>@endif
            <p class="sidelink"><a href="{{ route('public.admission.show', $school) }}">Start a new application</a></p>
        </aside>
    </div>
</div></section>
@endsection
