<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\Announcement;
use App\Models\HostelRoom;
use App\Models\InventoryItem;
use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\VisitorLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The home page for staff who do not get the school dashboard (front desk, drivers, hostel wardens,
 * store keepers). It shows only the parts of the school their permissions cover.
 */
class StaffHomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $today = now()->toDateString();
        $panels = [];

        if ($user->can('students.view') && $user->can('students.create')) {
            $panels['front_desk'] = [
                'visitors_today' => VisitorLog::whereDate('time_in', $today)->count(),
                'still_inside' => VisitorLog::whereDate('time_in', $today)->whereNull('time_out')->count(),
                'open_inquiries' => AdmissionInquiry::whereIn('status', ['new', 'follow_up'])->count(),
                'inside' => VisitorLog::whereDate('time_in', $today)->whereNull('time_out')->latest('time_in')->limit(8)
                    ->get(['id', 'name', 'person_to_meet', 'time_in'])
                    ->map(fn (VisitorLog $v) => ['id' => $v->id, 'name' => $v->name, 'meeting' => $v->person_to_meet, 'since' => $v->time_in?->format('g:i a')]),
            ];
        }

        if ($user->can('transport.view')) {
            $panels['transport'] = [
                'vehicles' => Vehicle::where('status', 'active')->count(),
                'routes' => TransportRoute::with('vehicle:id,name,registration_no,driver_name,driver_phone')->withCount('students')
                    ->where('is_active', true)->orderBy('name')->get()
                    ->map(fn (TransportRoute $r) => [
                        'id' => $r->id, 'name' => $r->name, 'from' => $r->start_point, 'to' => $r->end_point,
                        'stops' => count($r->stops ?? []), 'students' => $r->students_count,
                        'vehicle' => $r->vehicle ? trim(($r->vehicle->name ?? '') . ' ' . $r->vehicle->registration_no) : null,
                        'driver' => $r->vehicle?->driver_name,
                    ]),
            ];
        }

        if ($user->can('hostel.view')) {
            $rooms = HostelRoom::get(['capacity', 'occupied', 'status']);
            $panels['hostel'] = [
                'beds' => (int) $rooms->sum('capacity'),
                'occupied' => (int) $rooms->sum('occupied'),
                'rooms_free' => $rooms->where('status', 'available')->count(),
                'maintenance' => $rooms->where('status', 'maintenance')->count(),
            ];
        }

        if ($user->can('inventory.view')) {
            $low = InventoryItem::where('is_active', true)->whereColumn('current_stock', '<=', 'minimum_stock');
            $panels['store'] = [
                'items' => InventoryItem::where('is_active', true)->count(),
                'low_count' => (clone $low)->count(),
                'low' => (clone $low)->orderBy('current_stock')->limit(8)->get(['id', 'name', 'unit', 'current_stock', 'minimum_stock'])
                    ->map(fn (InventoryItem $i) => ['id' => $i->id, 'name' => $i->name, 'stock' => (float) $i->current_stock, 'minimum' => (float) $i->minimum_stock, 'unit' => $i->unit]),
            ];
        }

        $announcements = $user->can('announcements.view')
            ? Announcement::whereNotNull('published_at')->where('published_at', '<=', now())
                ->where(fn ($q) => $q->where('audience', 'all')->orWhere(fn ($r) => $r->where('audience', 'role')->whereIn('target_role', $user->getRoleNames())))
                ->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(3)->get(['id', 'title', 'body', 'published_at'])
                ->map(fn (Announcement $a) => ['id' => $a->id, 'title' => $a->title, 'body' => $a->body, 'date' => $a->published_at?->format('j M Y')])
            : [];

        return Inertia::render('SchoolAdmin/StaffHome', [
            'name' => $user->name,
            'panels' => $panels,
            'announcements' => $announcements,
        ]);
    }
}
