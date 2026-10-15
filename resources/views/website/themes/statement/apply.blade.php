@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <p class="sp-kicker">Admissions</p>
    <h1>Apply to {{ $school->name }}</h1>
    <p class="sp-lead">{{ $pageLead }}</p>
</div></section>

<section class="sp-body"><div class="wrap sp-narrow">
    @if (session('applied'))
        <div class="sp-thanks">
            <h2>Thank you. We have your application.</h2>
            <p>{{ session('applied') }}</p>
@include('website.partials.apply-status-link')
            <p><a class="btn btn--solid" href="{{ $homeUrl }}">Back to the school website</a></p>
        </div>
    @else
        <p class="sp-note">Fields marked * are needed.</p>
        @if ($errors->any())<div class="alert alert--bad" role="alert">Please check the form: {{ $errors->first() }}</div>@endif
        <form class="sp-form" method="post" action="{{ route('public.admission.submit', $school) }}">
            @csrf
            <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
            <label class="sp-field"><span class="sr">Child's full name</span><input name="student_name" value="{{ old('student_name') }}" placeholder="Child's full name *" required maxlength="150" autocomplete="off">@error('student_name')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="sp-field"><span class="sr">Class you are applying for</span><input name="class_interested" value="{{ old('class_interested') }}" placeholder="Class you are applying for *" required maxlength="50" list="class-list" autocomplete="off">
                <datalist id="class-list">@foreach ($classes as $c)<option value="{{ $c }}">@endforeach</datalist>@error('class_interested')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="sp-field"><span class="sr">Parent or guardian name</span><input name="guardian_name" value="{{ old('guardian_name') }}" placeholder="Parent or guardian name *" required maxlength="150" autocomplete="name">@error('guardian_name')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="sp-field"><span class="sr">Phone number</span><input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" placeholder="Phone number *" required maxlength="25" autocomplete="tel">@error('guardian_phone')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="sp-field"><span class="sr">Email</span><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" placeholder="Email address (optional)" maxlength="150" autocomplete="email">@error('guardian_email')<small class="err">{{ $message }}</small>@enderror</label>
            @include('website.partials.apply-extra', ['fc' => 'sp-field', 'sr' => true])
            <label class="sp-field"><span class="sr">Anything we should know?</span><textarea name="notes" placeholder="Anything we should know? (optional)" maxlength="1000">{{ old('notes') }}</textarea>@error('notes')<small class="err">{{ $message }}</small>@enderror</label>
            <button class="btn btn--solid" type="submit">Send application</button>
            <p class="sp-fine">Your details go directly to our admissions office.@if ($school->phone) Questions? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>.@endif</p>
        </form>
    @endif
</div></section>

<section class="sp-band"><div class="wrap">
    <p class="sp-kicker">What happens next</p>
    <h2>Three steps to a place</h2>
    <ol class="sp-steps">
        <li><b>1</b><div><h3>We read your application</h3><p>Our admissions office reviews it.</p></div></li>
        <li><b>2</b><div><h3>We contact you</h3><p>By phone or email, to plan a visit or a short assessment.</p></div></li>
        <li><b>3</b><div><h3>You receive an offer</h3><p>With the fees and what to bring.</p></div></li>
    </ol>
</div></section>
@endsection
