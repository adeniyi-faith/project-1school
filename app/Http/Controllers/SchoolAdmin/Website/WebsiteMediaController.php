<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSiteAlbumPhoto;
use App\Models\SchoolSiteMedia;
use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use App\Support\ImageShrinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** The website's picture library: every picture uploaded once, reused on any page or story */
class WebsiteMediaController extends WebsiteBase
{
    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $used = $this->usedPaths();

        return Inertia::render('SchoolAdmin/Website/Media', $this->shell($school, $site) + [
            'media' => collect($this->media())->map(fn ($m) => $m + ['used' => in_array($m['path'], $used, true)])->values(),
        ]);
    }

    /** One picture, from the library page or from a picture box in an editor. Answers with JSON so the editor keeps what is typed */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240']);
        $sid = $this->getSchoolId();
        $file = $request->file('image');
        $path = ImageShrinker::store($file, "schools/{$sid}/website", 'public', 1800);

        $size = @getimagesize(Storage::disk('public')->path($path)) ?: [null, null];
        $media = SchoolSiteMedia::create([
            'path' => $path,
            'name' => mb_substr(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Picture', 0, 200),
            'size' => Storage::disk('public')->size($path),
            'width' => $size[0], 'height' => $size[1],
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json(['id' => $media->id, 'path' => $path, 'url' => $media->url, 'name' => $media->name, 'alt' => null, 'width' => $media->width, 'height' => $media->height, 'size' => $media->size]);
    }

    public function update(Request $request, int $media): RedirectResponse
    {
        $row = SchoolSiteMedia::findOrFail($media);
        $row->update($request->validate(['name' => 'required|string|max:200', 'alt' => 'nullable|string|max:200']));

        return back()->with('success', 'Picture details saved.');
    }

    public function destroy(int $media): RedirectResponse
    {
        $row = SchoolSiteMedia::findOrFail($media);
        if (in_array($row->path, $this->usedPaths(), true)) {
            return back()->withErrors(['media' => 'This picture is used on a page or story. Take it off there first, then delete it.']);
        }
        Storage::disk('public')->delete($row->path);
        $row->delete();

        return back()->with('success', 'Picture deleted.');
    }

    /** Every picture a page section or a story is showing */
    private function usedPaths(): array
    {
        $paths = SchoolSitePost::whereNotNull('cover_image')->pluck('cover_image')->all();
        $walk = function ($value) use (&$walk, &$paths) {
            if (is_array($value)) {
                array_walk($value, $walk);
            } elseif (is_string($value) && str_contains($value, '/website/')) {
                $paths[] = $value;
            }
        };
        SchoolSitePage::get(['blocks'])->each(fn ($p) => $walk($p->blocks ?? []));
        // pictures that sit in a gallery album
        $inAlbums = SchoolSiteAlbumPhoto::pluck('media_id');
        if ($inAlbums->isNotEmpty()) {
            $paths = array_merge($paths, SchoolSiteMedia::whereIn('id', $inAlbums)->pluck('path')->all());
        }

        return array_values(array_unique($paths));
    }
}
