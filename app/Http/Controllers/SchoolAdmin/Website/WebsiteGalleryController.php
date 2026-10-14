<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSiteAlbum;
use App\Models\SchoolSiteAlbumPhoto;
use App\Models\SchoolSiteMedia;
use App\Website\SiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/** Photo albums for the school website: sports days, graduations, trips. Pictures come from the picture library */
class WebsiteGalleryController extends WebsiteBase
{
    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);

        $albums = SchoolSiteAlbum::withCount('photos')->with('cover:id,path')->orderBy('position')->orderByDesc('id')->get()
            ->map(fn ($a) => [
                'id' => $a->id, 'title' => $a->title, 'description' => $a->description, 'event_date' => $a->event_date?->toDateString(),
                'is_published' => $a->is_published, 'photos' => $a->photos_count, 'cover' => $a->cover?->path ?? $a->photos()->with('media:id,path')->first()?->media?->path,
                'url' => SiteRenderer::homeUrl($school).'/gallery/'.$a->slug,
            ]);

        return Inertia::render('SchoolAdmin/Website/Gallery', $this->shell($school, $site) + ['albums' => $albums]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $album = SchoolSiteAlbum::create($data + [
            'slug' => $this->freeSlug(Str::limit(Str::slug($data['title']) ?: 'album', 110, '')),
            'position' => (int) SchoolSiteAlbum::max('position') + 1,
        ]);

        return redirect()->route('school.website.gallery.edit', $album->id)->with('success', 'Album created. Now add some photos.');
    }

    public function edit(int $album): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $row = SchoolSiteAlbum::with('photos.media:id,path,name,alt,width,height,size')->findOrFail($album);

        return Inertia::render('SchoolAdmin/Website/AlbumEdit', $this->shell($school, $site) + [
            'album' => [
                'id' => $row->id, 'title' => $row->title, 'description' => $row->description ?? '', 'event_date' => $row->event_date?->toDateString() ?? '',
                'is_published' => $row->is_published, 'cover_media_id' => $row->cover_media_id, 'url' => SiteRenderer::homeUrl($school).'/gallery/'.$row->slug,
            ],
            'photos' => $row->photos->filter(fn ($p) => $p->media)->map(fn ($p) => [
                'id' => $p->id, 'media_id' => $p->media_id, 'path' => $p->media->path, 'caption' => $p->caption ?? '', 'alt' => $p->media->alt,
            ])->values(),
            'media' => $this->media(),
        ]);
    }

    public function update(Request $request, int $album): RedirectResponse
    {
        $row = SchoolSiteAlbum::findOrFail($album);
        $row->update($this->validated($request));

        return back()->with('success', 'Album saved.');
    }

    public function destroy(int $album): RedirectResponse
    {
        $row = SchoolSiteAlbum::findOrFail($album);
        SchoolSiteAlbumPhoto::where('album_id', $row->id)->delete();
        $row->delete();

        return redirect()->route('school.website.gallery.index')->with('success', 'Album deleted. The pictures are still in your picture library.');
    }

    /** Put pictures from the library into the album (the ones already in it are skipped) */
    public function addPhotos(Request $request, int $album): RedirectResponse
    {
        $row = SchoolSiteAlbum::findOrFail($album);
        $ids = $request->validate(['media_ids' => 'required|array|min:1|max:60', 'media_ids.*' => 'integer'])['media_ids'];

        $valid = SchoolSiteMedia::whereIn('id', $ids)->pluck('id')->all(); // only this school's pictures
        $have = SchoolSiteAlbumPhoto::where('album_id', $row->id)->pluck('media_id')->all();
        $next = (int) SchoolSiteAlbumPhoto::where('album_id', $row->id)->max('position');
        $added = 0;
        foreach ($ids as $id) {
            if (in_array($id, $valid, true) && ! in_array($id, $have, true)) {
                SchoolSiteAlbumPhoto::create(['album_id' => $row->id, 'media_id' => $id, 'position' => ++$next]);
                $have[] = $id;
                $added++;
            }
        }
        if (! $row->cover_media_id && $have) {
            $row->update(['cover_media_id' => $have[0]]);
        }

        return back()->with('success', $added === 1 ? '1 photo added.' : "{$added} photos added.");
    }

    public function updatePhoto(Request $request, int $album, int $photo): RedirectResponse
    {
        $row = SchoolSiteAlbumPhoto::where('album_id', $album)->findOrFail($photo);
        $row->update(['caption' => $request->validate(['caption' => 'nullable|string|max:200'])['caption'] ?: null]);

        return back()->with('success', 'Caption saved.');
    }

    public function removePhoto(int $album, int $photo): RedirectResponse
    {
        $row = SchoolSiteAlbumPhoto::where('album_id', $album)->findOrFail($photo);
        $parent = SchoolSiteAlbum::findOrFail($album);
        $row->delete();
        if ($parent->cover_media_id === $row->media_id) {
            $parent->update(['cover_media_id' => SchoolSiteAlbumPhoto::where('album_id', $album)->orderBy('position')->value('media_id')]);
        }

        return back()->with('success', 'Photo taken out of the album. It is still in your picture library.');
    }

    public function cover(Request $request, int $album): RedirectResponse
    {
        $row = SchoolSiteAlbum::findOrFail($album);
        $mediaId = $request->validate(['media_id' => 'required|integer'])['media_id'];
        abort_unless(SchoolSiteAlbumPhoto::where('album_id', $row->id)->where('media_id', $mediaId)->exists(), 404);
        $row->update(['cover_media_id' => $mediaId]);

        return back()->with('success', 'Cover photo changed.');
    }

    /** The new order of the photos, as a list of album-photo ids */
    public function reorder(Request $request, int $album): RedirectResponse
    {
        $order = $request->validate(['order' => 'required|array|max:200', 'order.*' => 'integer'])['order'];
        foreach (array_values($order) as $i => $id) {
            SchoolSiteAlbumPhoto::where('album_id', $album)->where('id', $id)->update(['position' => $i + 1]);
        }

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'description' => 'nullable|string|max:1000',
            'event_date' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        return ['title' => $data['title'], 'description' => $data['description'] ?? null, 'event_date' => $data['event_date'] ?? null, 'is_published' => (bool) ($data['is_published'] ?? true)];
    }

    private function freeSlug(string $slug): string
    {
        $try = $slug;
        for ($n = 2; SchoolSiteAlbum::withTrashed()->where('slug', $try)->exists(); $n++) {
            $try = "{$slug}-{$n}";
        }

        return $try;
    }
}
