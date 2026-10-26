<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventBudgetItem;
use App\Services\EventBudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventBudgetController extends Controller
{
    public function __construct(private readonly EventBudgetService $budgetService) {}

    public function index(Event $event)
    {
        $this->authorizeEvent($event);
        $event->loadMissing('guests');

        $budget = $this->budgetService->summary($event);
        $totalPledged = round((float) $event->guests->sum(fn ($guest) => (float) $guest->amount_contributed), 2);
        $totalCollected = round((float) $event->guests->sum(fn ($guest) => (float) $guest->paid_amount), 2);
        $fundingGap = round(max(0, $budget['totalBudget'] - $totalPledged), 2);
        $projectedBalance = round($totalPledged - $budget['totalBudget'], 2);
        $availableAfterVendors = round($totalCollected - $budget['totalPaid'], 2);
        $fundingCoverage = $budget['totalBudget'] > 0
            ? (int) min(100, round(($totalPledged / $budget['totalBudget']) * 100))
            : 0;
        $categories = EventBudgetItem::CATEGORIES;

        return view('events.budget.index', compact(
            'event',
            'budget',
            'totalPledged',
            'totalCollected',
            'fundingGap',
            'projectedBalance',
            'availableAfterVendors',
            'fundingCoverage',
            'categories'
        ));
    }

    public function report(Event $event)
    {
        $this->authorizeEvent($event);
        $event->loadMissing('guests');

        $budget = $this->budgetService->summary($event);
        $totalPledged = round((float) $event->guests->sum(fn ($guest) => (float) $guest->amount_contributed), 2);
        $totalCollected = round((float) $event->guests->sum(fn ($guest) => (float) $guest->paid_amount), 2);
        $fundingGap = round(max(0, $budget['totalBudget'] - $totalPledged), 2);
        $projectedBalance = round($totalPledged - $budget['totalBudget'], 2);
        $availableAfterVendors = round($totalCollected - $budget['totalPaid'], 2);
        $fundingCoverage = $budget['totalBudget'] > 0
            ? (int) min(100, round(($totalPledged / $budget['totalBudget']) * 100))
            : 0;

        return view('events.budget.report', compact(
            'event',
            'budget',
            'totalPledged',
            'totalCollected',
            'fundingGap',
            'projectedBalance',
            'availableAfterVendors',
            'fundingCoverage'
        ));
    }

    public function store(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $validated = $this->validatedData($request);
        $validated['event_id'] = $event->id;
        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $validated['sort_order'] = ((int) $event->budgetItems()->max('sort_order')) + 1;

        $item = EventBudgetItem::create($validated);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'budget_item_added',
            'description' => "Budget service '{$item->service_name}' was added.",
            'metadata' => ['budget_item_id' => $item->id],
        ]);

        return redirect()
            ->route('events.budget.index', $event)
            ->with('success', 'Budget service added successfully!');
    }

    public function update(Request $request, Event $event, EventBudgetItem $budgetItem)
    {
        $this->authorizeEvent($event);
        $this->ensureItemBelongsToEvent($event, $budgetItem);

        $validated = $this->validatedData($request);
        $validated['paid_amount'] = $validated['paid_amount'] ?? 0;
        $budgetItem->update($validated);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'budget_item_updated',
            'description' => "Budget service '{$budgetItem->service_name}' was updated.",
            'metadata' => ['budget_item_id' => $budgetItem->id],
        ]);

        return redirect()
            ->route('events.budget.index', $event)
            ->with('success', 'Budget service updated successfully!');
    }

    public function destroy(Event $event, EventBudgetItem $budgetItem)
    {
        $this->authorizeEvent($event);
        $this->ensureItemBelongsToEvent($event, $budgetItem);

        $name = $budgetItem->service_name;
        $budgetItem->delete();

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'budget_item_removed',
            'description' => "Budget service '{$name}' was removed.",
        ]);

        return redirect()
            ->route('events.budget.index', $event)
            ->with('success', 'Budget service removed successfully!');
    }

    public function updateEstimatedGuests(Request $request, Event $event)
    {
        $this->authorizeEvent($event);

        $validated = $request->validate([
            'estimated_guests' => 'nullable|integer|min:1|max:100000',
        ]);

        $event->update([
            'estimated_guests' => $validated['estimated_guests'] ?? null,
        ]);

        ActivityLog::create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'action' => 'budget_guests_updated',
            'description' => 'Estimated guest count was updated to '
                .($event->estimated_guests ? number_format($event->estimated_guests) : 'not set').'.',
        ]);

        return redirect()
            ->route('events.budget.index', $event)
            ->with('success', 'Estimated guest count updated!');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'category' => 'nullable|string|max:100',
            'service_name' => 'required|string|max:255',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_contact_name' => 'nullable|string|max:255',
            'vendor_phone' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'total_price' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function ensureItemBelongsToEvent(Event $event, EventBudgetItem $budgetItem): void
    {
        abort_unless($budgetItem->event_id === $event->id, 404);
    }
}
