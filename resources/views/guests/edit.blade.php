@extends('layouts.app')

@section('title', 'Edit Guest - ' . $guest->full_name)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Guests
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Edit Guest</h1>
        <p class="text-surface-500 text-sm">ID: {{ $guest->unique_id }}</p>
    </div>

    <div class="card-surface p-8">
        <form method="POST" action="{{ route('events.guests.update', [$event, $guest]) }}" class="space-y-6"
            data-loading-text="Saving guest..." data-loading-hint="Updating the guest details.">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="label-field">Full Name *</label>
                    <input type="text" name="full_name" value="{{ old('full_name', $guest->full_name) }}" required class="input-field">
                </div>
                <div>
                    <label class="label-field">Phone Number *</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number', $guest->phone_number) }}" required class="input-field">
                </div>
                <div>
                    <label class="label-field">Pledged Amount (TZS)</label>
                    <input type="number" name="amount_contributed" value="{{ old('amount_contributed', $guest->amount_contributed) }}" min="0" class="input-field">
                    <p class="text-xs text-surface-400 mt-1">
                        Card type is based on this pledged amount: Double from {{ number_format($event->double_card_minimum) }} TZS, otherwise Single.
                        Changing this does not change recorded payments.
                    </p>
                </div>
                <div>
                    <label class="label-field">Email</label>
                    <input type="email" name="email" value="{{ old('email', $guest->email) }}" class="input-field">
                </div>
                <div>
                    <label class="label-field">Table Number</label>
                    <input type="text" name="table_number" value="{{ old('table_number', $guest->table_number) }}" class="input-field">
                </div>
                <div>
                    <label class="label-field">Category</label>
                    <input type="text" name="category" value="{{ old('category', $guest->category) }}" class="input-field">
                </div>
            </div>
            <div>
                <label class="label-field">Notes</label>
                <textarea name="notes" rows="2" class="input-field">{{ old('notes', $guest->notes) }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-8 py-3">
                    <i class="fas fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-6" data-ajax-region="guest-totals">
        <div class="card-surface p-4">
            <div class="text-xs text-surface-400 uppercase tracking-wide">Pledged</div>
            <div class="text-lg font-bold text-surface-900 mt-1">{{ number_format($guest->pledged_amount) }}</div>
        </div>
        <div class="card-surface p-4">
            <div class="text-xs text-surface-400 uppercase tracking-wide">Paid</div>
            <div class="text-lg font-bold text-green-600 mt-1">{{ number_format($guest->paid_amount) }}</div>
        </div>
        <div class="card-surface p-4">
            <div class="text-xs text-surface-400 uppercase tracking-wide">Balance</div>
            <div class="text-lg font-bold {{ $guest->balance > 0 ? 'text-red-500' : 'text-green-600' }} mt-1">{{ number_format($guest->balance) }}</div>
        </div>
        <div class="card-surface p-4">
            <div class="text-xs text-surface-400 uppercase tracking-wide">Status</div>
            <div class="mt-1"><span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->payment_status_label }}</span></div>
        </div>
    </div>

    <div class="card-surface p-6 md:p-8 mt-6" data-ajax-region="guest-payments">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div>
                <h2 class="font-display text-lg font-bold text-surface-900">Payments</h2>
                <p class="text-sm text-surface-500">Record each full or partial payment for this guest.</p>
            </div>
            <span class="badge {{ $guest->payment_status_badge_class }}">{{ $guest->collection_rate }}% collected</span>
        </div>

        <form method="POST" action="{{ route('events.guests.payments.store', [$event, $guest]) }}"
            class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end" data-ajax
            data-loading-text="Recording payment..." data-loading-hint="Updating the guest balance.">
            @csrf
            <div class="md:col-span-3">
                <label class="label-field">Amount (TZS) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" min="1" step="0.01" required class="input-field" placeholder="e.g. 50000">
            </div>
            <div class="md:col-span-3">
                <label class="label-field">Method *</label>
                <select name="method" required class="input-field">
                    <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="mobile_money" {{ old('method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="bank" {{ old('method') === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ old('method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="other" {{ old('method') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="label-field">Payment Date *</label>
                <input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="input-field">
            </div>
            <div class="md:col-span-3">
                <label class="label-field">Reference</label>
                <input type="text" name="reference" value="{{ old('reference') }}" class="input-field" placeholder="Receipt / transaction no.">
            </div>
            <div class="md:col-span-9">
                <label class="label-field">Notes</label>
                <input type="text" name="notes" value="{{ old('notes') }}" class="input-field" placeholder="Optional payment note">
            </div>
            <div class="md:col-span-3">
                <button type="submit" class="btn btn-primary w-full">
                    <i class="fas fa-plus"></i> Record Payment
                </button>
            </div>
        </form>

        <div class="mt-6 border-t border-surface-100 pt-5">
            @if($guest->payments->isEmpty())
            <p class="text-sm text-surface-400 text-center py-4">No payments recorded yet.</p>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-surface-400 uppercase tracking-wide">
                            <th class="py-2 pr-4">Date</th>
                            <th class="py-2 pr-4">Amount</th>
                            <th class="py-2 pr-4">Method</th>
                            <th class="py-2 pr-4">Reference</th>
                            <th class="py-2 pr-4">Recorded By</th>
                            <th class="py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100">
                        @foreach($guest->payments->sortByDesc('paid_at') as $payment)
                        <tr>
                            <td class="py-3 pr-4">{{ $payment->paid_at->format('d M Y') }}</td>
                            <td class="py-3 pr-4 font-semibold text-surface-900">{{ number_format($payment->amount) }}</td>
                            <td class="py-3 pr-4">{{ $payment->method_label }}</td>
                            <td class="py-3 pr-4 text-surface-500">
                                {{ $payment->reference ?: '—' }}
                                @if($payment->notes)<div class="text-xs text-surface-400">{{ $payment->notes }}</div>@endif
                            </td>
                            <td class="py-3 pr-4 text-surface-500">{{ $payment->user?->name ?? 'System' }}</td>
                            <td class="py-3 text-right">
                                <div class="inline-flex items-center gap-3">
                                    <button type="button" class="text-blue-600 hover:text-blue-700" title="Edit payment"
                                        onclick="openEditPaymentModal(this)"
                                        data-url="{{ route('events.guests.payments.update', [$event, $guest, $payment]) }}"
                                        data-amount="{{ (float) $payment->amount }}"
                                        data-method="{{ $payment->method }}"
                                        data-paid-at="{{ $payment->paid_at->toDateString() }}"
                                        data-reference="{{ $payment->reference }}"
                                        data-notes="{{ $payment->notes }}">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('events.guests.payments.destroy', [$event, $guest, $payment]) }}" data-ajax
                                        onsubmit="return confirm('Remove this payment? The guest balance will be recalculated.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700" title="Remove payment">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div id="editPaymentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4" onclick="if (event.target === this) closeEditPaymentModal()">
    <div class="bg-white rounded-t-2xl sm:rounded-xl shadow-xl w-full max-w-md max-h-[92dvh] flex flex-col overflow-hidden animate-fade-in">
        <div class="px-6 py-4 border-b border-surface-100 flex shrink-0 items-center justify-between">
            <h3 class="font-display font-bold text-lg text-surface-900">Edit Payment</h3>
            <button type="button" onclick="closeEditPaymentModal()" class="text-surface-400 hover:text-surface-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editPaymentForm" method="POST" action="" class="flex min-h-0 flex-col" data-ajax data-ajax-success="closeEditPaymentModal"
            data-loading-text="Saving payment...">
            @csrf @method('PUT')
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <p class="text-sm text-surface-500">Correct a payment for <span class="font-semibold text-surface-900">{{ $guest->full_name }}</span>. Paid and balance are recalculated; no message is sent.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Amount (TZS) *</label>
                        <input type="number" name="amount" id="editPaymentAmount" min="1" step="0.01" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Method *</label>
                        <select name="method" id="editPaymentMethod" required class="input-field">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                            <option value="import" id="editPaymentImportOption" hidden>Imported</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Payment Date *</label>
                        <input type="date" name="paid_at" id="editPaymentDate" max="{{ now()->toDateString() }}" required class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-surface-700 mb-1">Reference</label>
                        <input type="text" name="reference" id="editPaymentReference" class="input-field" placeholder="Receipt no.">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-surface-700 mb-1">Notes</label>
                    <textarea name="notes" id="editPaymentNotes" rows="2" class="input-field" placeholder="Optional payment note"></textarea>
                </div>
            </div>
            <div class="px-5 sm:px-6 py-4 bg-surface-50 border-t border-surface-100 flex shrink-0 justify-end gap-3">
                <button type="button" onclick="closeEditPaymentModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-floppy-disk"></i> Save Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditPaymentModal(button) {
        const form = document.getElementById('editPaymentForm');
        const isImport = button.dataset.method === 'import';

        form.setAttribute('action', button.dataset.url);
        document.getElementById('editPaymentImportOption').hidden = !isImport;
        document.getElementById('editPaymentAmount').value = button.dataset.amount;
        document.getElementById('editPaymentMethod').value = button.dataset.method;
        document.getElementById('editPaymentDate').value = button.dataset.paidAt;
        document.getElementById('editPaymentReference').value = button.dataset.reference || '';
        document.getElementById('editPaymentNotes').value = button.dataset.notes || '';

        document.getElementById('editPaymentModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.getElementById('editPaymentAmount').focus();
    }

    function closeEditPaymentModal() {
        document.getElementById('editPaymentModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
</script>
@endsection
