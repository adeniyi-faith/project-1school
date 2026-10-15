@extends('website.layout')

@section('main')
    @if ($needsHiddenH1)<h1 class="sr">{{ $page->title }}</h1>@endif
    @forelse ($blocks as $block)
        @includeFirst(['website.themes.'.\App\Website\Themes::key($theme).'.blocks.'.$block['type'], 'website.blocks.'.$block['type'], 'siteblocks::'.$block['type'].'.view', 'website.blocks.unknown'], ['b' => $block])
    @empty
        <section class="sec"><div class="wrap"><p>This page has no content yet.</p></div></section>
    @endforelse
@endsection
