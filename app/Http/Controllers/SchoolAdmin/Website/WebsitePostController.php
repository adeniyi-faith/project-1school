<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Models\SchoolSitePage;
use App\Models\SchoolSitePost;
use App\Website\Blocks;
use App\Website\SiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** News stories and events for the public website */
class WebsitePostController extends WebsiteBase
{
    public function index(Request $request): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $status = $request->query('status');
        $category = $request->query('category');
        $search = trim((string) $request->query('q'));

        $posts = SchoolSitePost::with('author:id,name')
            ->when(in_array($status, ['draft', 'published', 'scheduled'], true), function ($q) use ($status) {
                return match ($status) {
                    'draft' => $q->where('status', 'draft'),
                    'published' => $q->live(),
                    'scheduled' => $q->where('status', 'published')->where('published_at', '>', now()),
                };
            })
            ->when(isset(SchoolSitePost::CATEGORIES[$category]), fn ($q) => $q->where('category', $category))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%'))
            ->orderByRaw('published_at is null desc')->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(20)->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'category' => $p->category, 'categoryLabel' => $p->categoryLabel(),
                'status' => $p->status === 'published' && $p->published_at?->isFuture() ? 'scheduled' : $p->status,
                'published_at' => $p->published_at, 'event_date' => $p->event_date?->toDateString(), 'is_featured' => $p->is_featured,
                'cover' => SiteRenderer::image($p->cover_image), 'author' => $p->author?->name, 'updated_at' => $p->updated_at,
                'url' => SiteRenderer::postUrl($school, $p),
            ]);

        return Inertia::render('SchoolAdmin/Website/Posts', $this->shell($school, $site) + [
            'posts' => $posts,
            'filters' => ['status' => $status, 'category' => $category, 'q' => $search],
            'categories' => SchoolSitePost::CATEGORIES,
            'counts' => [
                'all' => SchoolSitePost::count(), 'draft' => SchoolSitePost::where('status', 'draft')->count(),
                'published' => SchoolSitePost::live()->count(), 'scheduled' => SchoolSitePost::where('status', 'published')->where('published_at', '>', now())->count(),
            ],
            'newsUrl' => SiteRenderer::newsUrl($school),
        ]);
    }

    public function create(Request $request): Response
    {
        $category = isset(SchoolSitePost::CATEGORIES[$request->query('category')]) ? $request->query('category') : 'news';

        return $this->editor(new SchoolSitePost(['category' => $category, 'status' => 'draft']));
    }

    public function edit(int $post): Response
    {
        return $this->editor(SchoolSitePost::findOrFail($post));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $post = SchoolSitePost::create($data + ['author_id' => $request->user()->id]);

        return redirect("/school/website/posts/{$post->id}")->with('success', $this->savedMessage($post));
    }

    public function update(Request $request, int $post): RedirectResponse
    {
        $row = SchoolSitePost::findOrFail($post);
        $row->update($this->validated($request, $row));

        return back()->with('success', $this->savedMessage($row));
    }

    public function destroy(int $post): RedirectResponse
    {
        $row = SchoolSitePost::findOrFail($post);
        $row->delete();

        return redirect('/school/website/posts')->with('success', "\"{$row->title}\" deleted.");
    }

    /** The story drawn exactly as typed, nothing saved */
    public function preview(Request $request)
    {
        $school = $this->school();
        $site = $this->site($school);
        $data = $this->rules($request, null, true);

        $post = new SchoolSitePost($this->shape($data, null, $school->id));
        $post->id = (int) $request->input('id') ?: 0;
        $post->published_at ??= now();
        $pages = SchoolSitePage::where('is_published', true)->orderBy('position')->orderBy('id')->get();
        $related = SchoolSitePost::live()->where('id', '!=', $post->id)->orderByDesc('published_at')->limit(3)->get();

        return SiteRenderer::renderPost($school, $site, $pages, $post, $related, true);
    }

    private function editor(SchoolSitePost $post): Response
    {
        $school = $this->school();
        $site = $this->site($school);

        return Inertia::render('SchoolAdmin/Website/PostEdit', $this->shell($school, $site) + [
            'post' => [
                'id' => $post->id, 'title' => $post->title ?? '', 'slug' => $post->slug ?? '', 'category' => $post->category ?? 'news',
                'excerpt' => $post->excerpt ?? '', 'body' => $post->body ?? '', 'cover_image' => $post->cover_image ?? '',
                'status' => $post->status ?? 'draft',
                'published_at' => $post->published_at?->format('Y-m-d\TH:i') ?? '',
                'event_date' => $post->event_date?->toDateString() ?? '', 'event_place' => $post->event_place ?? '',
                'is_featured' => (bool) $post->is_featured, 'seo_title' => $post->seo_title ?? '', 'seo_description' => $post->seo_description ?? '',
                'url' => $post->exists ? SiteRenderer::postUrl($school, $post) : null,
            ],
            'categories' => SchoolSitePost::CATEGORIES,
            'media' => $this->media(),
            'newsBase' => SiteRenderer::newsUrl($school),
            'schoolName' => $school->name,
        ]);
    }

    private function rules(Request $request, ?SchoolSitePost $row, bool $preview = false): array
    {
        return $request->validate([
            'title' => ($preview ? 'nullable' : 'required').'|string|max:160',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]*$/'],
            'category' => ['required', Rule::in(array_keys(SchoolSitePost::CATEGORIES))],
            'excerpt' => 'nullable|string|max:300',
            'body' => 'nullable|string|max:30000',
            'cover_image' => 'nullable|string|max:300',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => 'nullable|date',
            'event_date' => 'nullable|date',
            'event_place' => 'nullable|string|max:160',
            'is_featured' => 'boolean',
            'seo_title' => 'nullable|string|max:80',
            'seo_description' => 'nullable|string|max:200',
        ], ['slug.regex' => 'The web address can only use small letters, numbers and dashes.']);
    }

    private function validated(Request $request, ?SchoolSitePost $row): array
    {
        $data = $this->rules($request, $row);
        $shaped = $this->shape($data, $row, $this->getSchoolId());

        $base = $shaped['slug'] ?: (Str::slug($data['title']) ?: 'story');
        $shaped['slug'] = $this->freeSlug(Str::limit($base, 110, ''), $row?->id);

        return $shaped;
    }

    private function shape(array $data, ?SchoolSitePost $row, int $schoolId): array
    {
        $publishedAt = $data['published_at'] ?? null ? Carbon::parse($data['published_at']) : null;
        if ($data['status'] === 'published' && ! $publishedAt) {
            $publishedAt = $row?->published_at ?? now();
        }

        return [
            'title' => trim((string) ($data['title'] ?? '')) ?: 'Untitled story',
            'slug' => Str::lower(trim((string) ($data['slug'] ?? ''), '-')),
            'category' => $data['category'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'] ?? null,
            'cover_image' => Blocks::imagePath($data['cover_image'] ?? '', $schoolId) ?: null,
            'status' => $data['status'],
            'published_at' => $publishedAt,
            'event_date' => $data['event_date'] ?? null,
            'event_place' => $data['event_place'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ];
    }

    private function freeSlug(string $slug, ?int $ignore): string
    {
        $try = $slug;
        for ($n = 2; SchoolSitePost::withTrashed()->where('slug', $try)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists(); $n++) {
            $try = "{$slug}-{$n}";
        }

        return $try;
    }

    private function savedMessage(SchoolSitePost $post): string
    {
        if ($post->status === 'draft') {
            return 'Saved as a draft. Visitors cannot see it yet.';
        }

        return $post->published_at->isFuture()
            ? 'Saved. It will appear on your website on '.$post->published_at->format('j M Y \a\t g:ia').'.'
            : 'Published. It is on your website now.';
    }
}
