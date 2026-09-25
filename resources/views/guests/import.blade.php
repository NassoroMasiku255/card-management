@extends('layouts.app')

@section('title', 'Import Guests - ' . $event->name)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('events.guests.index', $event) }}" class="text-surface-500 hover:text-surface-700 text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Guests
        </a>
        <h1 class="font-display text-2xl font-bold text-surface-900 mt-2">Import Guests</h1>
        <p class="text-surface-500 text-sm">Upload an Excel or CSV file for {{ $event->name }}</p>
    </div>

    @if(session('import_errors'))
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl mb-6 text-sm">
        <p class="font-medium mb-2">Import warnings:</p>
        <ul class="list-disc list-inside space-y-1">
            @foreach(session('import_errors') as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="card-surface p-8">
        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
            <h3 class="font-medium text-blue-800 mb-2"><i class="fas fa-circle-info mr-1"></i> File Format Instructions</h3>
            <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                <li>Accepted formats: <strong>CSV, XLS, XLSX</strong></li>
                <li>First row must contain column headers</li>
                <li>Required columns: <strong>Full Name</strong>, <strong>Phone Number</strong></li>
                <li>Optional: Amount Contributed, Card Type (single/double), Email, Table Number, Category, Notes</li>
                <li>Phone numbers can be in format: 0712345678 or 255712345678</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('events.guests.import.store', $event) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div>
                <label class="label-field mb-2">Upload File *</label>
                <div class="relative border-2 border-dashed border-surface-300 rounded-xl p-8 text-center hover:border-primary-400 hover:bg-primary-50/30 transition" id="drop-zone">
                    <div class="mb-3">
                        <i class="fas fa-cloud-arrow-up text-4xl text-surface-300"></i>
                    </div>
                    <p class="text-surface-600 mb-1">Drop your file here or click to browse</p>
                    <p class="text-xs text-surface-400">Max file size: 10MB</p>
                    <input type="file" name="file" accept=".csv,.xlsx,.xls" required
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                </div>
                @error('file')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4">
                <a href="{{ route('guests.template') }}" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                    <i class="fas fa-download mr-1"></i> Download Template
                </a>
                <button type="submit" class="btn btn-success px-8 py-3">
                    <i class="fas fa-upload"></i> Import Guests
                </button>
            </div>
        </form>
    </div>

    <!-- Expected Format Table -->
    <div class="card-surface p-6 mt-6">
        <h3 class="font-medium text-surface-800 mb-3">Expected Column Headers</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-surface-500">Column</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-surface-500">Required</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-surface-500">Example</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-100">
                    <tr><td class="px-3 py-2">Full Name</td><td class="px-3 py-2"><span class="text-red-500">Yes</span></td><td class="px-3 py-2 text-surface-500">John Doe</td></tr>
                    <tr><td class="px-3 py-2">Phone Number</td><td class="px-3 py-2"><span class="text-red-500">Yes</span></td><td class="px-3 py-2 text-surface-500">0712345678</td></tr>
                    <tr><td class="px-3 py-2">Amount Contributed</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">50000</td></tr>
                    <tr><td class="px-3 py-2">Card Type</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">single / double</td></tr>
                    <tr><td class="px-3 py-2">Email</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">john@email.com</td></tr>
                    <tr><td class="px-3 py-2">Table Number</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">Table 1</td></tr>
                    <tr><td class="px-3 py-2">Category</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">Family</td></tr>
                    <tr><td class="px-3 py-2">Notes</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-surface-500">VIP guest</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
