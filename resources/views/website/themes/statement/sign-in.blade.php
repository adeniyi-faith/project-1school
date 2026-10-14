@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <p class="sp-kicker">Members' area</p>
    <h1>Sign in to {{ $school->name }}</h1>
    <p class="sp-lead">{{ $pageLead }}</p>
</div></section>

<section class="sp-body"><div class="wrap sp-narrow">
    <div class="sp-who" role="radiogroup" aria-label="I am a">
        <label><input type="radio" name="who" value="parent" checked><span>Parent</span></label>
        <label><input type="radio" name="who" value="student"><span>Student</span></label>
        <label><input type="radio" name="who" value="staff"><span>Staff</span></label>
    </div>
    <p class="sp-note" id="role-note">Use the email and password the school gave you. You will see your child's results, fees and notices.</p>
    @if ($errors->any())<div class="alert alert--bad" role="alert">{{ $errors->first() }}</div>@endif
    <form class="sp-form" method="post" action="{{ url('/login') }}">
        @csrf
        <label class="sp-field"><span class="sr">Email</span><input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" required autocomplete="username"></label>
        <label class="sp-field"><span class="sr">Password</span><input type="password" name="password" placeholder="Password" required autocomplete="current-password"></label>
        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
        <button class="btn btn--solid" type="submit">Sign in</button>
        <p class="sp-fine">Forgot your password or have no account yet? Please ask the school office.@if ($school->phone) Phone: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>@endif</p>
    </form>
</div></section>

<section class="sp-band"><div class="wrap">
    <p class="sp-kicker">One sign-in</p>
    <h2>What you can do here</h2>
    <ol class="sp-steps">
        <li><b>I</b><div><h3>Parents</h3><p>See results and attendance, download report cards and pay fees.</p></div></li>
        <li><b>II</b><div><h3>Students</h3><p>Check results, take computer tests and read notices.</p></div></li>
        <li><b>III</b><div><h3>Teachers and staff</h3><p>Enter scores, take attendance and run the school day.</p></div></li>
    </ol>
</div></section>
<script>
(function(){
  var notes={parent:"Use the email and password the school gave you. You will see your child's results, fees and notices.",student:"Use your school email and password. You will see your results, tests and notices.",staff:"Use your staff email and password to open your school tools."};
  document.querySelectorAll('input[name=who]').forEach(function(r){r.addEventListener('change',function(){document.getElementById('role-note').textContent=notes[r.value];});});
})();
</script>
@endsection
