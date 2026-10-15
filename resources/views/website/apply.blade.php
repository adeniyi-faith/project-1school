@extends('website.layout')

@section('main')
<section class="pagehero"><div class="wrap">
    <p class="hero-tag"><i></i>Admissions</p>
    <h1>Apply to {{ $school->name }}</h1>
    <p>{{ $pageLead }}</p>
</div></section>

<section class="formwrap"><div class="wrap">
    <div class="formgrid">
        <div class="panel">
            @if (session('applied'))
                <div class="thanks">
                    <span class="card-ico">@include('website.partials.icon', ['name' => 'star'])</span>
                    <h2>Thank you. We have your application.</h2>
                    <p class="lede">{{ session('applied') }}</p>
@include('website.partials.apply-status-link')
                    <p><a class="btn btn--solid" href="{{ $homeUrl }}">Back to the school website</a></p>
                </div>
            @else
                <h2>Admission form</h2>
                <p class="lede">Fields marked <em style="color:var(--link);font-style:normal">*</em> are needed.</p>
                @if ($errors->any())<div class="alert alert--bad" role="alert">Please check the form: {{ $errors->first() }}</div>@endif
                <form method="post" action="{{ route('public.admission.submit', $school) }}">
                    @csrf
                    <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="company_site" tabindex="-1" autocomplete="off"></label></div>
                    <div class="two">
                        <label class="field"><span>Child's full name <em>*</em></span><input name="student_name" value="{{ old('student_name') }}" required maxlength="150" autocomplete="off">@error('student_name')<small class="err">{{ $message }}</small>@enderror</label>
                        <label class="field"><span>Class you are applying for <em>*</em></span><input name="class_interested" value="{{ old('class_interested') }}" required maxlength="50" list="class-list" autocomplete="off">
                            <datalist id="class-list">@foreach ($classes as $c)<option value="{{ $c }}">@endforeach</datalist>@error('class_interested')<small class="err">{{ $message }}</small>@enderror</label>
                    </div>
                    <label class="field"><span>Parent or guardian name <em>*</em></span><input name="guardian_name" value="{{ old('guardian_name') }}" required maxlength="150" autocomplete="name">@error('guardian_name')<small class="err">{{ $message }}</small>@enderror</label>
                    <div class="two">
                        <label class="field"><span>Phone number <em>*</em></span><input type="tel" name="guardian_phone" value="{{ old('guardian_phone') }}" required maxlength="25" autocomplete="tel">@error('guardian_phone')<small class="err">{{ $message }}</small>@enderror</label>
                        <label class="field"><span>Email (optional)</span><input type="email" name="guardian_email" value="{{ old('guardian_email') }}" maxlength="150" autocomplete="email">@error('guardian_email')<small class="err">{{ $message }}</small>@enderror</label>
                    </div>
                    @include('website.partials.apply-extra', ['fc' => 'field', 'sr' => false])
                    <label class="field"><span>Anything we should know? (optional)</span><textarea name="notes" maxlength="1000">{{ old('notes') }}</textarea>@error('notes')<small class="err">{{ $message }}</small>@enderror</label>
                    <button class="btn btn--solid btn--block" type="submit">Send application</button>
                </form>
            @endif
        </div>
        <aside class="panel panel--soft">
            <h2>What happens next</h2>
            <ul class="perks">
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'clipboard'])</span><div><b>We read your application</b><span class="t">Our admissions office reviews it.</span></div></li>
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'chat'])</span><div><b>We contact you</b><span class="t">By phone or email, to plan a visit or a short assessment.</span></div></li>
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'cap'])</span><div><b>You receive an offer</b><span class="t">With the fees and what to bring.</span></div></li>
            </ul>
            @if ($school->phone)<p class="sidelink">Questions? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></p>@endif
        </aside>
    </div>
</div></section>
@endsection
