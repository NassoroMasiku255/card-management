<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GuestPayment;
use App\Services\EventBudgetService;
use Illuminate\Support\Collection;

class EventReportController extends Controller
{
    public function __construct(private readonly EventBudgetService $budgetService) {}

    public function show(Event $event)
    {
        $this->authorizeEvent($event);

        $event->load(['invitations.guest']);

        $guests = $event->guests()
            ->with(['invitation', 'payments'])
            ->orderBy('full_name')
            ->get();

        $totalPledged = round($guests->sum(fn ($guest) => $guest->pledged_amount), 2);
        $totalPaid = round($guests->sum(fn ($guest) => (float) $guest->paid_amount), 2);
        $outstanding = round(max(0, $totalPledged - $totalPaid), 2);
        $overpaid = round(max(0, $totalPaid - $totalPledged), 2);
        $collectionRate = $totalPledged > 0
            ? (int) min(100, round(($totalPaid / $totalPledged) * 100))
            : ($totalPaid > 0 ? 100 : 0);

        $statusCounts = [
            'paid' => 0,
            'partial' => 0,
            'unpaid' => 0,
            'overpaid' => 0,
        ];

        foreach ($guests as $guest) {
            $statusCounts[$guest->payment_status]++;
        }

        $singles = $guests->where('card_type', 'single')->count();
        $doubles = $guests->where('card_type', 'double')->count();
        $estimatedAttendance = $guests->sum(fn ($guest) => $guest->estimated_attendees);
        $confirmedAttendance = $guests
            ->filter(fn ($guest) => $guest->invitation?->rsvp_status === 'attending')
            ->sum(fn ($guest) => $guest->estimated_attendees);
        $pendingAttendance = $guests
            ->filter(fn ($guest) => ! $guest->invitation || $guest->invitation->rsvp_status === 'pending')
            ->sum(fn ($guest) => $guest->estimated_attendees);

        $outstandingGuests = $guests
            ->filter(fn ($guest) => $guest->balance > 0)
            ->sortByDesc('balance')
            ->take(15)
            ->values();

        $categoryBreakdown = $guests
            ->groupBy(fn ($guest) => trim((string) ($guest->category ?: 'Uncategorized')))
            ->map(function (Collection $group, string $category) {
                $pledged = round($group->sum(fn ($guest) => $guest->pledged_amount), 2);
                $paid = round($group->sum(fn ($guest) => (float) $guest->paid_amount), 2);

                return [
                    'category' => $category,
                    'guests' => $group->count(),
                    'pledged' => $pledged,
                    'paid' => $paid,
                    'balance' => round(max(0, $pledged - $paid), 2),
                ];
            })
            ->sortByDesc('pledged')
            ->values();

        $recentPayments = GuestPayment::where('event_id', $event->id)
            ->with(['guest', 'user'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $budget = $this->budgetService->summary($event);
        $budgetPosition = [
            'totalBudget' => $budget['totalBudget'],
            'vendorPaid' => $budget['totalPaid'],
            'vendorOutstanding' => $budget['outstanding'],
            'fundingGap' => round(max(0, $budget['totalBudget'] - $totalPledged), 2),
            'projectedBalance' => round($totalPledged - $budget['totalBudget'], 2),
            'availableAfterVendors' => round($totalPaid - $budget['totalPaid'], 2),
            'fundingCoverage' => $budget['totalBudget'] > 0
                ? (int) min(100, round(($totalPledged / $budget['totalBudget']) * 100))
                : 0,
            'estimatedGuests' => $budget['estimatedGuests'],
            'costPerGuest' => $budget['costPerGuest'],
            'paidRate' => $budget['paidRate'],
        ];

        $stats = compact(
            'totalPledged',
            'totalPaid',
            'outstanding',
            'overpaid',
            'collectionRate',
            'statusCounts',
            'singles',
            'doubles',
            'estimatedAttendance',
            'confirmedAttendance',
            'pendingAttendance'
        );

        return view('events.report', compact(
            'event',
            'stats',
            'guests',
            'outstandingGuests',
            'categoryBreakdown',
            'recentPayments',
            'budget',
            'budgetPosition'
        ));
    }
}
