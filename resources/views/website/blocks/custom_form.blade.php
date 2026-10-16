@php
    $siteForm = ($b['form'] ?? '') !== '' ? \App\Models\SiteForm::withoutGlobalScopes()->where('school_id', $school->id)->where('active', true)->find((int) ltrim((string) $b['form'], 'f')) : null;
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $heading = ($b['heading'] ?? '') !== '' ? $b['heading'] : ($siteForm?->name ?? '');
    $intro = ($b['intro'] ?? '') !== '' ? $b['intro'] : ($siteForm?->intro ?? '');
@endphp
@if ($siteForm)
<section class="sec" id="form-section-{{ $siteForm->id }}"><div class="wrap">
    <div class="sec-head">
        @if ($heading)<{{ $tag }}>{{ $heading }}</{{ $tag }}>@endif
        @if ($intro)<p>{{ $intro }}</p>@endif
    </div>
    @include('website.partials.site-form', ['form' => $siteForm])
</div></section>
@endif
