<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $events = $user->events()->withCount('guests', 'invitations')->latest()->get();

        $totalEvents = $events->count();
        $totalGuests = $events->sum('guests_count');
        $activeEvents = $events->where('status', 'active')->count();

        return view('dashboard', compact('events', 'totalEvents', 'totalGuests', 'activeEvents'));
    }
}
