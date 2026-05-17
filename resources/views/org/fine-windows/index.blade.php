@extends('layouts.app')
@section('title', 'Fine Collection')
@section('page-title', 'Fine Collection')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-[22px] font-bold text-green-800">Fine Collection Window</h2>
            <p class="text-[13.5px] text-green-500 font-medium mt-1">
                {{ $organization->name }} - {{ $activeSemester?->name ?? 'No active semester' }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($window?->isOpen())
                <form method="POST" action="{{ route('org.fine-windows.close') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-red-600 hover:bg-red-500 text-white">
                        Close Window
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('org.fine-windows.open') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2 rounded-lg text-[13.5px] font-bold bg-green-600 hover:bg-green-500 text-white" @disabled(!$activeSemester)>
                        Open Window
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-green-200 rounded-xl p-5">
            <div class="text-[11px] font-black uppercase tracking-widest text-green-400">Status</div>
            <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-[12px] font-black {{ $window?->isOpen() ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                {{ $window?->isOpen() ? 'OPEN' : 'CLOSED' }}
            </div>
        </div>
        <div class="bg-white border border-green-200 rounded-xl p-5">
            <div class="text-[11px] font-black uppercase tracking-widest text-green-400">Unpaid Fines</div>
            <div class="mt-2 text-[24px] font-black text-green-800">{{ $stats['unpaid_count'] }}</div>
        </div>
        <div class="bg-white border border-green-200 rounded-xl p-5">
            <div class="text-[11px] font-black uppercase tracking-widest text-green-400">Outstanding</div>
            <div class="mt-2 text-[24px] font-black text-red-700">PHP {{ number_format($stats['unpaid_total'], 2) }}</div>
        </div>
        <div class="bg-white border border-green-200 rounded-xl p-5">
            <div class="text-[11px] font-black uppercase tracking-widest text-green-400">Collected</div>
            <div class="mt-2 text-[24px] font-black text-green-700">PHP {{ number_format($stats['paid_total'], 2) }}</div>
        </div>
    </div>

    <div class="bg-white border border-green-200 rounded-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-[#eaf0ec]">
            <h3 class="text-[16px] font-bold text-green-800">Window Details</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-[13.5px]">
            <div>
                <div class="font-bold text-green-400 mb-1">Opened At</div>
                <div class="text-green-800">{{ $window?->opened_at?->format('M d, Y h:i A') ?? 'Not opened this semester' }}</div>
            </div>
            <div>
                <div class="font-bold text-green-400 mb-1">Closed At</div>
                <div class="text-green-800">{{ $window?->closed_at?->format('M d, Y h:i A') ?? 'Not closed' }}</div>
            </div>
            <div>
                <div class="font-bold text-green-400 mb-1">Settlement Rule</div>
                <div class="text-green-800">Students must settle their full outstanding fine balance in one transaction.</div>
            </div>
            <div>
                <div class="font-bold text-green-400 mb-1">POS Behavior</div>
                <div class="text-green-800">Fine payments are locked while this window is closed.</div>
            </div>
        </div>
    </div>
</div>
@endsection
