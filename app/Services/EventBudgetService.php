<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventBudgetItem;
use Illuminate\Support\Collection;

class EventBudgetService
{
    public function summary(Event $event): array
    {
        $items = $event->budgetItems()
            ->orderBy('sort_order')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        $totalBudget = round((float) $items->sum(fn (EventBudgetItem $item) => (float) $item->total_price), 2);
        $totalPaid = round((float) $items->sum(fn (EventBudgetItem $item) => (float) $item->paid_amount), 2);
        $outstanding = round(max(0, $totalBudget - $totalPaid), 2);
        $overpaid = round(max(0, $totalPaid - $totalBudget), 2);
        $paidRate = $totalBudget > 0
            ? (int) min(100, round(($totalPaid / $totalBudget) * 100))
            : ($totalPaid > 0 ? 100 : 0);

        $estimatedGuests = (int) ($event->estimated_guests ?? 0);
        $costPerGuest = $estimatedGuests > 0 ? round($totalBudget / $estimatedGuests, 2) : 0;
        $paidPerGuest = $estimatedGuests > 0 ? round($totalPaid / $estimatedGuests, 2) : 0;

        $statusCounts = [
            'unpaid' => 0,
            'partial' => 0,
            'paid' => 0,
            'overpaid' => 0,
        ];

        foreach ($items as $item) {
            $statusCounts[$item->status]++;
        }

        $categoryBreakdown = $items
            ->groupBy(fn (EventBudgetItem $item) => $item->category_label)
            ->map(function (Collection $group, string $category) {
                $total = round((float) $group->sum(fn (EventBudgetItem $item) => (float) $item->total_price), 2);
                $paid = round((float) $group->sum(fn (EventBudgetItem $item) => (float) $item->paid_amount), 2);

                return [
                    'category' => $category,
                    'items' => $group->count(),
                    'total' => $total,
                    'paid' => $paid,
                    'balance' => round(max(0, $total - $paid), 2),
                    'paid_rate' => $total > 0 ? (int) min(100, round(($paid / $total) * 100)) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $overdueItems = $items
            ->filter(fn (EventBudgetItem $item) => $item->is_overdue)
            ->sortBy(fn (EventBudgetItem $item) => $item->due_date)
            ->values();

        $upcomingItems = $items
            ->filter(fn (EventBudgetItem $item) => $item->due_date !== null
                && $item->due_date->between(today(), today()->addDays(30))
                && $item->balance > 0)
            ->sortBy(fn (EventBudgetItem $item) => $item->due_date)
            ->values();

        $largestBalances = $items
            ->filter(fn (EventBudgetItem $item) => $item->balance > 0)
            ->sortByDesc(fn (EventBudgetItem $item) => $item->balance)
            ->take(5)
            ->values();

        $vendorCount = $items
            ->pluck('vendor_name')
            ->filter()
            ->map(fn (string $vendor) => mb_strtolower(trim($vendor)))
            ->unique()
            ->count();

        return [
            'items' => $items,
            'totalBudget' => $totalBudget,
            'totalPaid' => $totalPaid,
            'outstanding' => $outstanding,
            'overpaid' => $overpaid,
            'paidRate' => $paidRate,
            'estimatedGuests' => $estimatedGuests,
            'costPerGuest' => $costPerGuest,
            'paidPerGuest' => $paidPerGuest,
            'statusCounts' => $statusCounts,
            'categoryBreakdown' => $categoryBreakdown,
            'overdueItems' => $overdueItems,
            'upcomingItems' => $upcomingItems,
            'largestBalances' => $largestBalances,
            'overdueTotal' => round((float) $overdueItems->sum(fn (EventBudgetItem $item) => $item->balance), 2),
            'vendorCount' => $vendorCount,
            'itemCount' => $items->count(),
        ];
    }
}
