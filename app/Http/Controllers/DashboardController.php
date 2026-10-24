<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $events = ($user->isAdmin() ? Event::query()->with('user') : $user->events())
            ->withCount('guests', 'invitations')
            ->latest()
            ->get();

        $totalEvents = $events->count();
        $totalGuests = $events->sum('guests_count');
        $activeEvents = $events->where('status', 'active')->count();

        return view('dashboard', compact('events', 'totalEvents', 'totalGuests', 'activeEvents'));
    }
}
