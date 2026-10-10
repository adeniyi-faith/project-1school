@if($card['preview'])<div class="preview">PREVIEW</div>@endif
<table class="head">
    <tr>
        @if($card['school']['logo'])<td style="width:70px"><img class="logo" src="{{ $card['school']['logo'] }}"></td>@endif
        <td>
            <p class="school">{{ $card['school']['name'] }}</p>
            @if($card['school']['motto'])<div><em>{{ $card['school']['motto'] }}</em></div>@endif
            <div class="muted">{{ $card['school']['address'] }}</div>
            <div class="muted">{{ collect([$card['school']['phone'], $card['school']['email']])->filter()->implode(' · ') }}</div>
        </td>
    </tr>
</table>
<table class="info">
    <tr>
        <td class="k">Name</td><td class="v" colspan="3">{{ $card['student']['name'] }}</td>
        <td class="k">Admission no.</td><td class="v">{{ $card['student']['admission_no'] }}</td>
    </tr>
    <tr>
        <td class="k">Class</td><td class="v">{{ $card['student']['class'] }}{{ $card['student']['section'] ? ' ' . $card['student']['section'] : '' }}</td>
        <td class="k">Session</td><td class="v">{{ $card['session'] ?? $card['year'] ?? '' }}</td>
        <td class="k">Gender / Age</td><td class="v">{{ collect([$card['student']['gender'], $card['student']['age'] ? $card['student']['age'] . ' yrs' : null])->filter()->implode(' · ') ?: '—' }}</td>
    </tr>
</table>
