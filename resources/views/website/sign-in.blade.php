@extends('website.layout')

@section('main')
<section class="pagehero"><div class="wrap">
    <p class="hero-tag"><i></i>Sign in</p>
    <h1>Welcome back to {{ $school->name }}</h1>
    <p>{{ $pageLead }}</p>
</div></section>

<section class="formwrap"><div class="wrap">
    <div class="formgrid">
        <div class="panel">
            <h2>Sign in</h2>
            <div class="roles" role="radiogroup" aria-label="I am a">
                <label><input type="radio" name="who" value="parent" checked><span>Parent</span></label>
                <label><input type="radio" name="who" value="student"><span>Student</span></label>
                <label><input type="radio" name="who" value="staff"><span>Staff</span></label>
            </div>
            <p class="role-note" id="role-note">Use the email and password the school gave you. You will see your child's results, fees and notices.</p>
            @if ($errors->any())<div class="alert alert--bad" role="alert">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ url('/login') }}">
                @csrf
                <label class="field"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required autocomplete="username"></label>
                <label class="field"><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label>
                <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
                <button class="btn btn--solid btn--block" type="submit">Sign in</button>
            </form>
            <p class="sidelink">Forgot your password or have no account yet? Please ask the school office. @if ($school->phone)Phone: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>@endif</p>
        </div>
        <aside class="panel panel--soft">
            <h2>What you can do</h2>
            <ul class="perks">
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'users'])</span><div><b>Parents</b><span class="t">See results and attendance, download report cards and pay fees.</span></div></li>
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'book'])</span><div><b>Students</b><span class="t">Check results, take computer tests and read notices.</span></div></li>
                <li><span class="card-ico">@include('website.partials.icon', ['name' => 'clipboard'])</span><div><b>Teachers and staff</b><span class="t">Enter scores, take attendance and run the school day.</span></div></li>
            </ul>
        </aside>
    </div>
</div></section>
<script>
(function(){
  var notes={parent:"Use the email and password the school gave you. You will see your child's results, fees and notices.",student:"Use your school email and password. You will see your results, tests and notices.",staff:"Use your staff email and password to open your school tools."};
  document.querySelectorAll('input[name=who]').forEach(function(r){r.addEventListener('change',function(){document.getElementById('role-note').textContent=notes[r.value];});});
})();
</script>
@endsection
