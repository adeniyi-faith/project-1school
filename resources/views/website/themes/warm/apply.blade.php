@extends('website.layout')

@section('main')
@include('website.themes.warm.head', ['kicker' => 'Admissions', 'title' => 'Apply to '.$school->name, 'lead' => $pageLead])
<section class="wa-body"><div class="wrap wa-split">
    <div class="wa-formcol">
    @if (session('applied'))
        <div class="wa-thanks">
            <h2 class="wa-h2">Thank you. We have your application.</h2>
            <p>{{ session('applied') }}</p>
@include('website.partials.apply-status-link')
            <p><a class="btn btn--solid" href="{{ $homeUrl }}">Back to the school website</a></p>
        </div>
    @else
        <p class="wa-note">Fields marked * are needed.</p>
        @if ($errors->any())<div class="alert alert--bad" role="alert">Please check the form: {{ $errors->first() }}</div>@endif
        <form class="wa-form" method="post" action="{{ route('public.admission.submit', $school) }}">
            @csrf
            <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
            <label class="wa-field"><span>Child's full name *</span><input name="student_name" value="{{ old('student_name') }}" required maxlength="150" autocomplete="off">@error('student_name')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="wa-field"><span>Class you are applying for *</span><input name="class_interested" value="{{ old('class_interested') }}" required maxlength="50" list="class-list" autocomplete="off">
                <datalist id="class-list">@foreach ($classes as $c)<option value="{{ $c }}">@endforeach</datalist>@error('class_interested')<small class="err">{{ $message }}</small>@enderror</label>
            <label class="wa-field"><span>Parent or guardian name *</span><input name="guardian_name" value="{{ old('guardian_name') }}" required maxlength="150" autocomplete="name">@error('guardian_name')<small class="err">{{ $message }}</small>@enderror</label>
            <div class="wa-two">
                <label class="wa-field"><span>Phone number *</span><input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" required maxlength="25" autocomplete="tel">@error('guardian_phone')<small class="err">{{ $message }}</small>@enderror</label>
                <label class="wa-field"><span>Email (optional)</span><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" maxlength="150" autocomplete="email">@error('guardian_email')<small class="err">{{ $message }}</small>@enderror</label>
            </div>
            @include('website.partials.apply-extra', ['fc' => 'wa-field', 'sr' => false])
            <label class="wa-field"><span>Anything we should know? (optional)</span><textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea>@error('notes')<small class="err">{{ $message }}</small>@enderror</label>
            <button class="btn btn--solid" type="submit">Send application</button>
            <p class="wa-fine">Your details go directly to our admissions office.@if ($school->phone) Questions? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>.@endif</p>
        </form>
    @endif
    </div>
    <aside class="wa-panel wa-panel--side">
        <h2>What happens next</h2>
        <ul class="wa-rows">
            <li><div><b>We read your application</b><span>Our admissions office reviews it.</span></div></li>
            <li><div><b>We contact you</b><span>By phone or email, to plan a visit or a short assessment.</span></div></li>
            <li><div><b>You receive an offer</b><span>With the fees and what to bring.</span></div></li>
        </ul>
    </aside>
</div></section>
@endsection
