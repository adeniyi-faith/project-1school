@extends('website.layout')

@section('main')
@include('website.themes.modern.head', ['kicker' => "Members' area", 'title' => 'Sign in to '.$school->name, 'lead' => $pageLead])
<section class="mo-body"><div class="wrap mo-split">
    <div class="mo-formcol">
        <div class="mo-who" role="radiogroup" aria-label="I am a">
            <label><input type="radio" name="who" value="parent" checked><span>Parent</span></label>
            <label><input type="radio" name="who" value="student"><span>Student</span></label>
            <label><input type="radio" name="who" value="staff"><span>Staff</span></label>
        </div>
        <p class="mo-note" id="role-note">Use the email and password the school gave you. You will see your child's results, fees and notices.</p>
        @if ($errors->any())<div class="alert alert--bad" role="alert">{{ $errors->first() }}</div>@endif
        <form class="mo-form" method="post" action="{{ url('/login') }}">
            @csrf
            <label class="mo-field"><span>Email address</span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
            <label class="mo-field"><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label>
            <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
            <button class="btn btn--solid" type="submit">Sign in</button>
            <p class="mo-fine">Forgot your password or have no account yet? Please ask the school office.@if ($school->phone) Phone: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>@endif</p>
        </form>
    </div>
    <aside class="mo-panel mo-panel--side">
        <h2>One sign-in</h2>
        <ul class="mo-rows">
            <li><div><b>Parents</b><span>See results and attendance, download report cards and pay fees.</span></div></li>
            <li><div><b>Students</b><span>Check results, take computer tests and read notices.</span></div></li>
            <li><div><b>Teachers and staff</b><span>Enter scores, take attendance and run the school day.</span></div></li>
        </ul>
    </aside>
</div></section>
<script>
(function(){
  var notes={parent:"Use the email and password the school gave you. You will see your child's results, fees and notices.",student:"Use your school email and password. You will see your results, tests and notices.",staff:"Use your staff email and password to open your school tools."};
  document.querySelectorAll('input[name=who]').forEach(function(r){r.addEventListener('change',function(){document.getElementById('role-note').textContent=notes[r.value];});});
})();
</script>
@endsection
