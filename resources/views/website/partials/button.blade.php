@php
    $href = match ($kind ?? 'none') {
        'apply' => route('public.admission.show', $school),
        'login' => route('login'),
        'contact' => $contactUrl,
        default => null,
    };
    $label = ['apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Contact us'][$kind ?? 'none'] ?? '';
@endphp
@if ($href)<a class="btn btn--{{ $variant }}" href="{{ $href }}">{{ $label }}</a>@endif
