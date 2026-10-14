<section class="block"><div class="wrap">
    @if ($b['heading'])<h2>{{ $b['heading'] }}</h2>@endif
    <div>{!! nl2br(e($b['body'])) !!}</div>
</div></section>
