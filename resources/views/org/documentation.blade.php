@extends('layouts.app')
@section('title', 'Documentation')
@section('page-title', 'Documentation')

@section('content')
<div class="max-w-6xl mx-auto pb-10">
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-[22px] font-bold text-[#0f1f17]">Documentation</h2>
            <p class="text-[13.5px] text-[#4a6356] mt-1 font-medium">Operational references and process guides for organization users</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        @foreach([
            ['title' => 'Collection Workflow', 'body' => 'Create transactions, issue official receipts, and keep each payment tied to the active semester.'],
            ['title' => 'Void Requests', 'body' => 'Submit correction requests with a clear reason and supporting proof when a receipt must be reviewed.'],
            ['title' => 'Remittance', 'body' => 'Generate batches, review collector breakdowns, and monitor verification status.'],
        ] as $doc)
            <div class="bg-white rounded-2xl border border-[#dde8e1] shadow-sm p-6">
                <div class="w-11 h-11 rounded-xl bg-[#e6f4ec] text-[#1a7a41] flex items-center justify-center mb-5">
                    @include('partials.ui-icon', ['name' => 'book-open', 'class' => 'w-5 h-5'])
                </div>
                <h3 class="text-[16px] font-bold text-[#0f1f17] mb-2">{{ $doc['title'] }}</h3>
                <p class="text-[13.5px] leading-relaxed text-[#4a6356] font-medium">{{ $doc['body'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 bg-white rounded-2xl border border-[#dde8e1] shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-[#eaf0ec] bg-[#f8fbf9]">
            <h3 class="text-[15px] font-bold text-[#0f1f17]">Quick Reference</h3>
            <p class="text-[12.5px] text-[#8aa89a] font-medium mt-0.5">Prototype-aligned documentation area</p>
        </div>
        <div class="divide-y divide-[#eaf0ec]">
            @foreach([
                'Use Create Transaction for point-of-sale fee collection.',
                'Use Fee Profiles to standardize recurring organization fees.',
                'Use Audit Logs to review activity history for accountability.',
            ] as $item)
                <div class="px-6 py-4 flex items-center gap-3 text-[13.5px] font-semibold text-[#4a6356]">
                    <span class="w-6 h-6 rounded-full bg-[#e6f4ec] text-[#1a7a41] flex items-center justify-center text-[12px] font-black">✓</span>
                    {{ $item }}
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
