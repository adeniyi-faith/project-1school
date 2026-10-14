@if (trim((string) $site->option('announcement')) !== '')
<div class="notice" role="region" aria-label="Announcement"><div class="wrap"><p>{{ $site->option('announcement') }}</p></div></div>
@endif
