{{-- The pages that drop down under a menu item. Needs $item (with its children). --}}
@if (! empty($item['children']))
    <ul class="menu-sub">
        @foreach ($item['children'] as $child)
            <li><a href="{{ $child['url'] }}" @if ($child['current']) aria-current="page" @endif>{{ $child['label'] }}</a></li>
        @endforeach
    </ul>
@endif
