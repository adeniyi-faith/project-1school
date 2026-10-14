{{-- The search box that drops down under a header. Needs $homeUrl. --}}
<div class="sbar" id="sbar" hidden>
    <form class="wrap sbar-in" role="search" method="get" action="{{ $homeUrl }}/search">
        <label class="sr" for="sbar-q">Search this site</label>
        <input id="sbar-q" type="search" name="q" value="{{ $query ?? '' }}" placeholder="Search pages, news and photos" maxlength="80" autocomplete="off">
        <button type="submit">Search</button>
    </form>
</div>
