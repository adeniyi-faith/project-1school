@extends('website.layout')

@section('main')
<section class="pagehero"><div class="wrap">
    <h1>{{ $siteForm->name }}</h1>
    @if ($siteForm->intro)<p>{{ $siteForm->intro }}</p>@endif
</div></section>

<section class="sec"><div class="wrap">
    @include('website.partials.site-form', ['form' => $siteForm, 'offer' => $offerNewsletter])
</div></section>
@endsection
