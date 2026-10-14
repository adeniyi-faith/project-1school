<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSiteMedia;
use App\Website\SiteRenderer;
use App\Website\SiteStarter;

/** What every website screen needs: the school, its site (made on first visit) and the picture library */
abstract class WebsiteBase extends Controller
{
    protected function school(): School
    {
        return School::findOrFail($this->getSchoolId());
    }

    protected function site(?School $school = null): SchoolSite
    {
        return SiteStarter::ensure($school ?? $this->school());
    }

    /** Shared by every website screen: the address, whether it is live, and the sub-menu */
    protected function shell(School $school, SchoolSite $site): array
    {
        return [
            'shell' => [
                'publicUrl' => SiteRenderer::homeUrl($school),
                'enabled' => $site->enabled,
                'canEdit' => (bool) request()->user()->can('settings.edit'),
            ],
        ];
    }

    /** The picture library, for the picture chooser in the editors */
    protected function media(): array
    {
        return SchoolSiteMedia::latest()->limit(300)->get(['id', 'path', 'name', 'alt', 'width', 'height', 'size'])->toArray();
    }
}
