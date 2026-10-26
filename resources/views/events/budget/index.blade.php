@extends('layouts.app')

@section('title', 'Budget & Vendors - ' . $event->name)

@section('content')
@php
    $accent = $event->card_accent_color ?: '#d4af37';
    $money = function ($amount) {
        return number_format((float) $amount);
    };
    $budgetItemsJson = $budget['items']->mapWithKeys(function ($item) use ($event) {
        return [$item->id => [
            'id' => $item->id,
            'category' => $item->category,
            'service_name' => $item->service_name,
            'vendor_name' => $item->vendor_name,
            'vendor_contact_name' => $item->vendor_contact_name,
            'vendor_phone' => $item->vendor_phone,
            'vendor_email' => $item->vendor_email,
            'total_price' => (float) $item->total_price,
            'paid_amount' => (float) $item->paid_amount,
            'due_date' => $item->due_date?->format('Y-m-d'),
            'notes' => $item->notes,
            'update_url' => route('events.budget.update', [$event, $item]),
        ]];
    });
    $categoriesUsed = $budget['items']->pluck('category_label')->unique()->sort()->values();
@endphp

<!-- Hero -->
<div class="mb-5 print:hidden">
    <div class="relative overflow-hidden rounded-2xl p-5 text-white shadow-soft-lg sm:p-7"
        style="background: linear-gradient(125deg, #17130a 0%, #3f2d13 48%, {{ $accent }} 145%);">
        <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/4 h-56 w-56 rounded-full bg-gold-300/20 blur-3xl"></div>

        <div class="relative">
            <a href="{{ route('events.show', $event) }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-white/60 transition hover:text-white">
                <i class="fas fa-arrow-left text-[10px]"></i> Back to {{ $event->name }}
            </a>

            <div class="mt-4 flex flex-wrap items-start justify-between gap-5">
                <div class="flex min-w-0 items-start gap-3.5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-gold-200 backdrop-blur">
                        <i class="fas fa-calculator text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-gold-300">Event financial planning</p>
                        <h1 class="mt-1 font-display text-2xl font-bold text-white sm:text-3xl">Budget &amp; Vendors</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-white/55">
                            <span><i class="fas fa-calendar-days mr-1.5 text-white/35"></i>{{ $event->event_date->format('l, d M Y') }}</span>
                            <span><i class="fas fa-location-dot mr-1.5 text-white/35"></i>{{ $event->location }}</span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gold-300/20 bg-gold-400/10 px-2.5 py-1 font-semibold text-gold-200">
                                <i class="fas fa-coins text-[9px]"></i>{{ $budget['itemCount'] }} services &middot; {{ $budget['vendorCount'] }} vendors
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <a href="{{ route('events.budget.report', $event) }}" class="btn flex-1 border border-white/20 bg-white/10 text-white hover:bg-white/20 sm:flex-none">
                        <i class="fas fa-chart-pie"></i> Budget report
                    </a>
                    <button type="button" onclick="openBudgetModal()" class="btn flex-1 bg-white text-surface-900 hover:bg-surface-100 sm:flex-none">
                        <i class="fas fa-plus"></i> Add service
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mb-5 hidden print:block">
    <h1 class="text-xl font-bold text-surface-900">{{ $event->name }} - Budget &amp; Vendors</h1>
    <p class="mt-1 text-sm text-surface-500">{{ $event->event_date->format('d M Y') }} &middot; {{ $event->location }}</p>
</div>

@if($errors->any())
<div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 print:hidden">
    <p class="font-semibold"><i class="fas fa-circle-exclamation mr-1"></i>Please fix the budget details below:</p>
    <ul class="mt-1 list-inside list-disc space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<!-- KPIs -->
<div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-6">
    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Total budget</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ $money($budget['totalBudget']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['itemCount'] }} budget services</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gold-50 text-gold-600"><i class="fas fa-sack-dollar text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-green-600/70">Paid to vendors</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-green-600 sm:text-3xl">{{ $money($budget['totalPaid']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['paidRate'] }}% of total budget</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600"><i class="fas fa-circle-check text-xs"></i></span>
        </div>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-green-50">
            <div class="h-full rounded-full bg-green-500" style="width: {{ $budget['paidRate'] }}%;"></div>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-red-500/80">Outstanding</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-red-500 sm:text-3xl">{{ $money($budget['outstanding']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['statusCounts']['unpaid'] }} unpaid &middot; {{ $budget['statusCounts']['partial'] }} partial</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-500"><i class="fas fa-hourglass-half text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-surface-400">Cost per guest</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-surface-900 sm:text-3xl">{{ $budget['estimatedGuests'] > 0 ? $money($budget['costPerGuest']) : '&mdash;' }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['estimatedGuests'] > 0 ? number_format($budget['estimatedGuests']).' estimated guests' : 'Set expected guests below' }}</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><i class="fas fa-user-group text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-purple-600/70">Vendors</p>
                <p class="mt-2 text-2xl font-bold tracking-tight text-purple-600 sm:text-3xl">{{ number_format($budget['vendorCount']) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $budget['statusCounts']['paid'] }} fully paid</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600"><i class="fas fa-handshake text-xs"></i></span>
        </div>
    </div>

    <div class="card-surface card-surface-hover p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.14em] {{ $projectedBalance >= 0 ? 'text-green-600/70' : 'text-amber-600/80' }}">{{ $projectedBalance >= 0 ? 'Projected surplus' : 'Funding gap' }}</p>
                <p class="mt-2 text-2xl font-bold tracking-tight {{ $projectedBalance >= 0 ? 'text-green-600' : 'text-amber-600' }} sm:text-3xl">{{ $money(abs($projectedBalance)) }}</p>
                <p class="mt-1 text-[10px] text-surface-400">{{ $money($totalPledged) }} pledged by guests</p>
            </div>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $projectedBalance >= 0 ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-600' }}"><i class="fas fa-scale-balanced text-xs"></i></span>
        </div>
    </div>
</div>

<!-- Funding + guest planning + categories -->
<div class="mb-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div class="card-surface overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between border-b border-surface-100 px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-gold-400 to-amber-600 text-white shadow-soft"><i class="fas fa-scale-balanced text-sm"></i></div>
                <div>
                    <h2 class="text-sm font-semibold text-surface-900">Funding position</h2>
                    <p class="text-[11px] text-surface-400">Guest contributions against vendor costs</p>
                </div>
            </div>
            <span class="badge {{ $projectedBalance >= 0 ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">{{ $fundingCoverage }}% covered</span>
        </div>
        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-4">
            <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3.5">
                <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Guest pledges</p>
                <p class="mt-1 text-sm font-bold text-surface-900">{{ $money($totalPledged) }}</p>
            </div>
            <div class="rounded-xl border border-green-100 bg-green-50/60 p-3.5">
                <p class="text-[9px] font-bold uppercase tracking-wider text-green-600/70">Collected</p>
                <p class="mt-1 text-sm font-bold text-green-700">{{ $money($totalCollected) }}</p>
            </div>
            <div class="rounded-xl border border-surface-100 bg-surface-50/60 p-3.5">
                <p class="text-[9px] font-bold uppercase tracking-wider text-surface-400">Event budget</p>
                <p class="mt-1 text-sm font-bold text-surface-900">{{ $money($budget['totalBudget']) }}</p>
            </div>
            <div class="rounded-xl border border-gold-100 bg-gold-50/60 p-3.5">
                <p class="text-[9px] font-bold uppercase tracking-wider text-gold-700">Cash after vendors</p>
                <p class="mt-1 text-sm font-bold {{ $availableAfterVendors >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $money($availableAfterVendors) }}</p>
            </div>
        </div>
        <div class="space-y-3 border-t border-surface-100 px-5 py-4">
            <div>
                <div class="flex items-center justify-between text-[11px]"><span class="text-surface-500">Budget covered by pledges</span><span class="font-bold text-surface-800">{{ $fundingCoverage }}%</span></div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-gradient-to-r from-gold-500 to-amber-300" style="width: {{ $fundingCoverage }}%;"></div></div>
            </div>
            <div>
                <div class="flex items-center justify-between text-[11px]"><span class="text-surface-500">Vendor payments completed</span><span class="font-bold text-surface-800">{{ $budget['paidRate'] }}%</span></div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full bg-gradient-to-r from-green-500 to-emerald-300" style="width: {{ $budget['paidRate'] }}%;"></div></div>
            </div>
        </div>
    </div>

    <div class="space-y-5">
        <!-- Guest planning -->
        <div class="card-surface p-5">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><i class="fas fa-user-group text-xs"></i></div>
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">Guest planning</h3>
                    <p class="text-[10px] text-surface-400">Approximate attendance</p>
                </div>
            </div>
            <form method="POST" action="{{ route('events.budget.estimated-guests', $event) }}" class="mt-4">
                @csrf
                @method('PUT')
                <label class="label-field">Expected number of guests</label>
                <div class="flex gap-2">
                    <input type="number" name="estimated_guests" value="{{ old('estimated_guests', $event->estimated_guests) }}" min="1" max="100000" class="input-field" placeholder="e.g., 250">
                    <button type="submit" class="btn btn-primary shrink-0" title="Save expected guests"><i class="fas fa-floppy-disk"></i></button>
                </div>
                <p class="mt-2 text-[10px] leading-relaxed text-surface-400">Used to calculate cost per guest and compare spending with expected attendance.</p>
            </form>
        </div>

        <!-- Category breakdown -->
        <div class="card-surface overflow-hidden">
            <div class="flex items-center gap-2.5 border-b border-surface-100 px-5 py-4">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 text-purple-600"><i class="fas fa-layer-group text-xs"></i></div>
                <div>
                    <h3 class="text-sm font-semibold text-surface-900">By category</h3>
                    <p class="text-[10px] text-surface-400">Where the budget goes</p>
                </div>
            </div>
            <div class="max-h-64 space-y-3 overflow-y-auto p-4">
                @forelse($budget['categoryBreakdown'] as $category)
                <div>
                    <div class="flex items-center justify-between gap-2 text-[11px]">
                        <span class="truncate font-medium text-surface-600">{{ $category['category'] }}</span>
                        <span class="shrink-0 font-bold text-surface-800">{{ $money($category['total']) }}</span>
                    </div>
                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-100">
                        <div class="h-full rounded-full bg-gradient-to-r from-purple-500 to-fuchsia-400" style="width: {{ $budget['totalBudget'] > 0 ? round(($category['total'] / $budget['totalBudget']) * 100) : 0 }}%;"></div>
                    </div>
                </div>
                @empty
                <p class="py-6 text-center text-xs text-surface-400">Categories will appear once you add services.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Quick add -->
<div class="mb-4 print:hidden">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="font-display text-lg font-bold text-surface-900">Budget services &amp; vendors</h2>
            <p class="mt-0.5 text-xs text-surface-400">Track every service, vendor, cost and advance payment in one place</p>
        </div>
        <span id="budgetResultCount" class="rounded-full bg-surface-100 px-3 py-1 text-[11px] font-semibold text-surface-500">{{ $budget['itemCount'] }} services</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-[10px] font-bold uppercase tracking-wider text-surface-400">Quick add:</span>
        @foreach([['Hall', 'Venue'], ['Catering', 'Catering'], ['MC', 'MC'], ['Photography', 'Photography'], ['Decoration', 'Decoration']] as [$service, $category])
        <button type="button" onclick="openBudgetModal(null, @js(['service_name' => $service, 'category' => $category]))"
            class="rounded-full border border-surface-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-surface-600 transition hover:border-gold-300 hover:bg-gold-50 hover:text-gold-700">
            <i class="fas fa-plus mr-1 text-[9px]"></i>{{ $service }}
        </button>
        @endforeach
    </div>
</div>

<!-- Search + filter -->
<div class="card-surface mb-4 p-4 print:hidden">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="relative flex-1">
            <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-surface-400"></i>
            <input type="text" id="budgetSearch" placeholder="Search service, vendor, contact or notes..." class="input-field pl-10" autocomplete="off">
        </div>
        <select id="budgetCategoryFilter" class="input-field lg:max-w-xs">
            <option value="all">All categories</option>
            @foreach($categoriesUsed as $category)
            <option value="{{ strtolower($category) }}">{{ $category }}</option>
            @endforeach
        </select>
    </div>
</div>

<!-- Budget table -->
<div class="card-surface mb-6 overflow-hidden">
    @if($budget['items']->isEmpty())
    <div class="px-6 py-16 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-gold-50 to-amber-100 text-gold-400">
            <i class="fas fa-calculator text-xl"></i>
        </div>
        <h3 class="mt-4 font-display text-lg font-bold text-surface-800">Build your event budget</h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-surface-400">Add halls, catering, MC, photography and every other service. Track vendor balances and advance payments automatically.</p>
        <button type="button" onclick="openBudgetModal()" class="btn btn-primary mt-5"><i class="fas fa-plus"></i> Add first service</button>
    </div>
    @else
    <div class="overflow-x-auto responsive-table-wrap">
        <table class="w-full responsive-table">
            <thead class="bg-surface-50/80">
                <tr class="text-left text-[10px] font-bold uppercase tracking-[0.1em] text-surface-400">
                    <th class="px-5 py-3">Service</th>
                    <th class="px-5 py-3">Vendor &amp; contact</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3 text-right">Paid</th>
                    <th class="px-5 py-3 text-right">Balance</th>
                    <th class="px-5 py-3">Due / status</th>
                    <th class="px-5 py-3 text-right print:hidden">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100" id="budgetRows">
                @foreach($budget['items'] as $item)
                @php
                    $progress = (float) $item->total_price > 0 ? min(100, (int) round(((float) $item->paid_amount / (float) $item->total_price) * 100)) : ((float) $item->paid_amount > 0 ? 100 : 0);
                    $searchBlob = strtolower(implode(' ', array_filter([$item->category_label, $item->service_name, $item->vendor_name, $item->vendor_contact_name, $item->vendor_phone, $item->vendor_email, $item->notes])));
                @endphp
                <tr class="budget-row group transition hover:bg-gold-50/20" data-category="{{ strtolower($item->category_label) }}" data-search="{{ $searchBlob }}">
                    <td class="px-5 py-3.5" data-label="Service">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gold-50 text-gold-600"><i class="fas fa-concierge-bell text-[11px]"></i></div>
                            <div class="min-w-0">
                                <div class="font-semibold text-surface-900">{{ $item->service_name }}</div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                    <span class="badge bg-surface-100 text-surface-500">{{ $item->category_label }}</span>
                                    @if($item->notes)<span class="max-w-[180px] truncate text-[10px] text-surface-400" title="{{ $item->notes }}">{{ $item->notes }}</span>@endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5" data-label="Vendor">
                        <div class="text-xs font-semibold text-surface-700">{{ $item->vendor_name ?: 'Vendor not set' }}</div>
                        @if($item->vendor_contact_name)<div class="text-[10px] text-surface-400">{{ $item->vendor_contact_name }}</div>@endif
                        <div class="mt-1 flex flex-wrap items-center gap-1.5 print:hidden">
                            @if($item->vendor_phone)
                            <a href="tel:{{ $item->vendor_phone }}" class="inline-flex items-center gap-1 rounded-md bg-surface-50 px-1.5 py-0.5 text-[9px] font-medium text-surface-500 hover:text-primary-600" title="Call vendor"><i class="fas fa-phone text-[8px]"></i>{{ $item->vendor_phone }}</a>
                            <a href="https://wa.me/{{ $item->formatted_vendor_phone }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-md bg-green-50 px-1.5 py-0.5 text-[9px] font-medium text-green-600 hover:text-green-700" title="WhatsApp vendor"><i class="fab fa-whatsapp text-[8px]"></i>Chat</a>
                            @endif
                            @if($item->vendor_email)
                            <a href="mailto:{{ $item->vendor_email }}" class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-1.5 py-0.5 text-[9px] font-medium text-blue-600 hover:text-blue-700" title="Email vendor"><i class="fas fa-envelope text-[8px]"></i>Email</a>
                            @endif
                            @if(! $item->vendor_phone && ! $item->vendor_email)
                            <span class="text-[9px] text-surface-300">No contact added</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-right text-xs font-semibold text-surface-700" data-label="Total">{{ $money($item->total_price) }}</td>
                    <td class="px-5 py-3.5 text-right" data-label="Paid">
                        <div class="text-xs font-bold text-green-600">{{ $money($item->paid_amount) }}</div>
                        <div class="mt-1 ml-auto h-1 w-16 overflow-hidden rounded-full bg-surface-100"><div class="h-full rounded-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-amber-400' }}" style="width: {{ $progress }}%;"></div></div>
                    </td>
                    <td class="px-5 py-3.5 text-right text-xs font-bold {{ $item->balance > 0 ? 'text-red-500' : 'text-surface-400' }}" data-label="Balance">{{ $money($item->balance) }}</td>
                    <td class="px-5 py-3.5" data-label="Due / status">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="badge {{ $item->status_badge_class }}">{{ $item->status_label }}</span>
                            @if($item->is_overdue)
                            <span class="badge bg-red-50 text-red-600"><i class="fas fa-clock mr-1 text-[8px]"></i>Overdue</span>
                            @endif
                        </div>
                        <div class="mt-1 text-[10px] {{ $item->is_overdue ? 'font-semibold text-red-500' : 'text-surface-400' }}">
                            {{ $item->due_date ? 'Due '.$item->due_date->format('d M Y') : 'No due date' }}
                        </div>
                    </td>
                    <td class="px-5 py-3.5 mobile-actions print:hidden" data-label="">
                        <div class="flex items-center justify-end gap-1">
                            <button type="button" onclick="openBudgetModal({{ $item->id }})" class="icon-action text-blue-600 hover:bg-blue-50" title="Edit service"><i class="fas fa-pen text-xs"></i></button>
                            <form method="POST" action="{{ route('events.budget.destroy', [$event, $item]) }}" onsubmit="return confirm('Remove {{ addslashes($item->service_name) }} from the budget?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-action is-danger text-red-500 hover:bg-red-50" title="Remove service"><i class="fas fa-trash text-xs"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div id="budgetNoResults" class="hidden px-6 py-12 text-center">
        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 text-surface-400"><i class="fas fa-magnifying-glass"></i></div>
        <p class="mt-2 text-sm font-medium text-surface-600">No services match your search.</p>
    </div>
    @endif
</div>

<!-- Budget modal -->
<div id="budgetModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-surface-900/50 p-4 backdrop-blur-sm print:hidden" role="dialog" aria-modal="true" aria-labelledby="budgetModalTitle">
    <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-surface-100 bg-white px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gold-50 text-gold-600"><i class="fas fa-concierge-bell text-sm"></i></div>
                <div>
                    <h3 id="budgetModalTitle" class="font-display text-lg font-bold text-surface-900">Add budget service</h3>
                    <p class="text-[11px] text-surface-400">Service, vendor, cost and advance payment</p>
                </div>
            </div>
            <button type="button" onclick="closeBudgetModal()" class="icon-action text-surface-400 hover:text-surface-700" aria-label="Close"><i class="fas fa-xmark"></i></button>
        </div>

        <form id="budgetForm" method="POST" action="{{ route('events.budget.store', $event) }}" class="space-y-5 p-5 sm:p-6">
            @csrf
            <input type="hidden" name="_method" id="budgetMethod" value="PUT" disabled>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-field">Category</label>
                    <select name="category" id="budgetCategory" class="input-field">
                        @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label-field">Service name *</label>
                    <input type="text" name="service_name" id="budgetServiceName" required class="input-field" placeholder="e.g., Wedding Hall">
                </div>
                <div>
                    <label class="label-field">Vendor name</label>
                    <input type="text" name="vendor_name" id="budgetVendorName" class="input-field" placeholder="e.g., Serena Hotel">
                </div>
                <div>
                    <label class="label-field">Contact person</label>
                    <input type="text" name="vendor_contact_name" id="budgetContactName" class="input-field" placeholder="e.g., Mr. John">
                </div>
                <div>
                    <label class="label-field">Vendor phone</label>
                    <input type="text" name="vendor_phone" id="budgetVendorPhone" class="input-field" placeholder="e.g., 0712 345 678">
                </div>
                <div>
                    <label class="label-field">Vendor email</label>
                    <input type="email" name="vendor_email" id="budgetVendorEmail" class="input-field" placeholder="vendor@example.com">
                </div>
                <div>
                    <label class="label-field">Total price (TZS) *</label>
                    <input type="number" name="total_price" id="budgetTotalPrice" required min="0" step="0.01" class="input-field" placeholder="0">
                </div>
                <div>
                    <label class="label-field">Advance / paid so far (TZS)</label>
                    <input type="number" name="paid_amount" id="budgetPaidAmount" min="0" step="0.01" class="input-field" placeholder="0">
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Payment due date</label>
                    <input type="date" name="due_date" id="budgetDueDate" class="input-field">
                </div>
                <div class="sm:col-span-2">
                    <label class="label-field">Notes / communication</label>
                    <textarea name="notes" id="budgetNotes" rows="3" class="input-field" placeholder="Agreed terms, contact notes, delivery details..."></textarea>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-surface-100 pt-4">
                <button type="button" onclick="closeBudgetModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" id="budgetSubmit" class="btn btn-primary"><i class="fas fa-plus"></i> Add service</button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="budgetItemsData">{!! $budgetItemsJson->toJson() !!}</script>

<style>
    @media print {
        body { background: #fff !important; }
        .card-surface { box-shadow: none !important; border: 1px solid #e5e7eb !important; break-inside: avoid; }
        table { font-size: 10px !important; }
        th, td { padding: 5px 8px !important; }
    }
</style>
@endsection

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('budgetModal');
        const form = document.getElementById('budgetForm');
        const methodInput = document.getElementById('budgetMethod');
        const title = document.getElementById('budgetModalTitle');
        const submit = document.getElementById('budgetSubmit');
        const storeUrl = @js(route('events.budget.store', $event));
        const items = JSON.parse(document.getElementById('budgetItemsData').textContent || '{}');

        const fields = {
            category: document.getElementById('budgetCategory'),
            service_name: document.getElementById('budgetServiceName'),
            vendor_name: document.getElementById('budgetVendorName'),
            vendor_contact_name: document.getElementById('budgetContactName'),
            vendor_phone: document.getElementById('budgetVendorPhone'),
            vendor_email: document.getElementById('budgetVendorEmail'),
            total_price: document.getElementById('budgetTotalPrice'),
            paid_amount: document.getElementById('budgetPaidAmount'),
            due_date: document.getElementById('budgetDueDate'),
            notes: document.getElementById('budgetNotes'),
        };

        window.openBudgetModal = function (itemId, preset) {
            form.reset();
            methodInput.disabled = true;
            form.action = storeUrl;
            title.textContent = 'Add budget service';
            submit.innerHTML = '<i class="fas fa-plus"></i> Add service';

            if (itemId && items[itemId]) {
                const item = items[itemId];
                form.action = item.update_url;
                methodInput.disabled = false;
                methodInput.value = 'PUT';
                title.textContent = 'Edit budget service';
                submit.innerHTML = '<i class="fas fa-floppy-disk"></i> Save changes';

                Object.keys(fields).forEach(function (key) {
                    if (fields[key]) fields[key].value = item[key] ?? '';
                });
            } else if (preset) {
                if (preset.service_name) fields.service_name.value = preset.service_name;
                if (preset.category) fields.category.value = preset.category;
                fields.total_price.value = '';
                fields.paid_amount.value = '';
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(function () { fields.service_name.focus(); }, 50);
        };

        window.closeBudgetModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeBudgetModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeBudgetModal();
        });

        const search = document.getElementById('budgetSearch');
        const categoryFilter = document.getElementById('budgetCategoryFilter');
        const rows = Array.from(document.querySelectorAll('.budget-row'));
        const noResults = document.getElementById('budgetNoResults');
        const resultCount = document.getElementById('budgetResultCount');

        function filterRows() {
            const term = (search?.value || '').trim().toLowerCase();
            const category = categoryFilter?.value || 'all';
            let visible = 0;

            rows.forEach(function (row) {
                const matchesTerm = !term || row.dataset.search.includes(term);
                const matchesCategory = category === 'all' || row.dataset.category === category;
                const show = matchesTerm && matchesCategory;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (noResults) noResults.classList.toggle('hidden', visible !== 0);
            if (resultCount) resultCount.textContent = visible + (visible === 1 ? ' service' : ' services');
        }

        search?.addEventListener('input', filterRows);
        categoryFilter?.addEventListener('change', filterRows);
    })();
</script>
@endpush
