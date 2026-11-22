@php
    $sectionLinks = [
        'overview' => ['Overview', 'fas fa-house', route('events.show', $event)],
        'guests' => ['Guests', 'fas fa-users', route('events.guests.index', $event)],
        'invitations' => ['Invitations', 'fas fa-paper-plane', route('events.invitations.index', $event)],
        'report' => ['Report', 'fas fa-chart-pie', route('events.report', $event)],
        'messages' => ['Messages', 'fas fa-tower-broadcast', route('events.messages.report', $event)],
        'budget' => ['Budget', 'fas fa-calculator', route('events.budget.index', $event)],
    ];
    $activeSection = $active ?? null;
@endphp
<nav class="flex flex-wrap gap-1 border-t border-surface-100 px-3 py-2 sm:px-4 print:hidden" aria-label="Event sections">
    @foreach($sectionLinks as $key => [$label, $icon, $url])
    <a href="{{ $url }}" @if($key === $activeSection) aria-current="page" @endif
        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-[13px] font-medium transition {{ $key === $activeSection ? 'bg-primary-50 text-primary-700' : 'text-surface-600 hover:bg-primary-50 hover:text-primary-700' }}">
        <i class="{{ $icon }} text-xs {{ $key === $activeSection ? 'text-primary-600' : 'text-surface-400' }}"></i> {{ $label }}
    </a>
    @endforeach
</nav>
