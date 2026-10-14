@extends('website.layout')

@section('main')
    @if ($needsHiddenH1)<h1 class="sr">{{ $page->title }}</h1>@endif
    @forelse ($blocks as $block)
        @includeIf('website.blocks.'.$block['type'], ['b' => $block])
    @empty
        <section class="sec"><div class="wrap"><p>This page has no content yet.</p></div></section>
    @endforelse
@endsection
