@php $d = $card['design']; $s = $card['school']; @endphp
@if($d['show_logo'] && $d['logo_watermark'] && $s['logo'])<img class="watermark" src="{{ $s['logo'] }}">@endif
@if($card['preview'])<div class="preview">PREVIEW</div>@endif
@if($d['template'] === 'modern')
<table class="head center">
    @if($d['show_logo'] && $s['logo'])<tr><td><img class="logo" src="{{ $s['logo'] }}"></td></tr>@endif
    <tr><td>
        <p class="school">{{ $s['name'] }}</p>
        @if($d['show_motto'] && $s['motto'])<div><em>{{ $s['motto'] }}</em></div>@endif
        @if($d['show_address'])<div class="muted">{{ collect([$s['address'], $s['phone'], $s['email']])->filter()->implode(' · ') }}</div>@endif
    </td></tr>
</table>
@else
<table class="head">
    <tr>
        @if($d['show_logo'] && $s['logo'])<td style="width:{{ $d['template'] === 'compact' ? 54 : 70 }}px"><img class="logo" src="{{ $s['logo'] }}"></td>@endif
        <td>
            <p class="school">{{ $s['name'] }}</p>
            @if($d['show_motto'] && $s['motto'])<div><em>{{ $s['motto'] }}</em></div>@endif
            @if($d['show_address'])
                <div class="muted">{{ $s['address'] }}</div>
                <div class="muted">{{ collect([$s['phone'], $s['email']])->filter()->implode(' · ') }}</div>
            @endif
        </td>
    </tr>
</table>
@endif
<table class="info">
    <tr>
        <td class="k">Name</td><td class="v" colspan="3">{{ $card['student']['name'] }}</td>
        <td class="k">Admission no.</td><td class="v">{{ $card['student']['admission_no'] }}</td>
        @if($d['show_photo'])
            <td rowspan="3" style="width:78px; text-align:right">
                @if($card['student']['photo'])<img class="photo" src="{{ $card['student']['photo'] }}">@else<div class="photo"></div>@endif
            </td>
        @endif
    </tr>
    <tr>
        <td class="k">Class</td><td class="v">{{ $card['student']['class'] }}{{ $card['student']['section'] ? ' ' . $card['student']['section'] : '' }}</td>
        <td class="k">Session</td><td class="v">{{ $card['session'] ?? $card['year'] ?? '' }}</td>
        @if($d['show_age'])
            <td class="k">Gender / Age</td><td class="v">{{ collect([$card['student']['gender'], $card['student']['age'] ? $card['student']['age'] . ' yrs' : null])->filter()->implode(' · ') ?: '—' }}</td>
        @else
            <td class="k">Gender</td><td class="v">{{ $card['student']['gender'] ?? '—' }}</td>
        @endif
    </tr>
    @if($d['show_photo'])<tr><td colspan="6"></td></tr>@endif
</table>
