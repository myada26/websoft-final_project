@extends('layouts.app')
@section('title', 'Remittance')
@section('page-title', 'Remittance')

@section('content')
<div>
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px">
        <div>
            <h1 style="font-size:19px;font-weight:700;color:#0f1f17">Remittance</h1>
            <p style="font-size:12.5px;color:#4a6356;margin-top:2px">Batch unremitted transactions → verify → accept (FR-0020/FR-0021)</p>
        </div>
        @if(auth()->user()->canCreateRemittances())
        <form method="POST" action="{{ route('org.remittances.store') }}">
            @csrf
            <button type="submit" class="btn-green" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Create New Remittance
            </button>
        </form>
        @endif
    </div>

    {{-- 3-stage status summary --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px">
        @php
        $stages = [
        ['key'=>'PENDING','label'=>'Pending','count'=>$pendingCount??0,'amount'=>$pendingAmount??0,'color'=>'#d97706'],
        ['key'=>'VERIFIED','label'=>'Verified','count'=>$verifiedCount??0,'amount'=>$verifiedAmount??0,'color'=>'#2563eb'],
        ['key'=>'ACCEPTED','label'=>'Accepted','count'=>$acceptedCount??0,'amount'=>$acceptedAmount??0,'color'=>'#16a34a'],
        ];
        @endphp
        @foreach($stages as $stage)
        <div style="background:white;border:1px solid #dde8e1;border-radius:12px;padding:16px 18px;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="font-size:26px;font-weight:700;line-height:1;color:{{ $stage['color'] }}">{{ $stage['count'] }}</div>
            <div style="font-size:11px;color:#8aa89a;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-top:3px">{{ $stage['label'] }}</div>
            <div style="font-size:13px;color:#4a6356;margin-top:4px;font-weight:500">₱{{ number_format($stage['amount'], 2) }}</div>
        </div>
        @endforeach
    </div>

    {{-- Remittance list --}}
    <div class="card">
        <div style="padding:13px 20px;border-bottom:1px solid #eaf0ec">
            <div style="font-size:14px;font-weight:700">Remittance History</div>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f0f3f1;border-bottom:1px solid #dde8e1">
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Batch ID</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Semester</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Transactions</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Total Amount</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Status / Timeline</th>
                        <th style="padding:9px 13px;text-align:right;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($remittances as $remit)
                    <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                        <td style="padding:10px 13px"><span style="font-family:monospace;font-size:12px;color:#1a7a41;font-weight:700;background:#e6f4ec;padding:2px 6px;border-radius:4px">#{{ str_pad($remit->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                        <td style="padding:10px 13px;font-size:13px;color:#4a6356">{{ $remit->academicYear?->name }}</td>
                        <td style="padding:10px 13px;font-size:13px;font-weight:600">{{ $remit->transactions->count() }}</td>
                        <td style="padding:10px 13px;font-size:13px;font-weight:600">₱{{ number_format($remit->transactions->sum('amount_paid'), 2) }}</td>
                        <td style="padding:10px 13px">
                            {{-- 3-step timeline indicator --}}
                            <div style="display:flex;align-items:center;gap:0">
                                @foreach(['PENDING','VERIFIED','ACCEPTED'] as $s)
                                @if(!$loop->first)<div style="flex:1;height:2px;background:{{ in_array($remit->status, ['VERIFIED','ACCEPTED']) && ($s==='VERIFIED'||($s==='ACCEPTED'&&$remit->status==='ACCEPTED')) ? '#1a7a41' : '#dde8e1' }};margin-top:-13px"></div>@endif
                                <div style="display:flex;flex-direction:column;align-items:center;gap:4px;flex:{{ $loop->last?1:0 }}">
                                    <div style="width:20px;height:20px;border-radius:50%;border:2px solid #dde8e1;background:{{ $remit->status===$s ? '#d4a42a' : (in_array($s,['PENDING'])&&in_array($remit->status,['VERIFIED','ACCEPTED'])||$s==='VERIFIED'&&$remit->status==='ACCEPTED' ? '#1a7a41' : 'white') }};color:{{ in_array($s,['PENDING'])&&in_array($remit->status,['VERIFIED','ACCEPTED'])||$s==='VERIFIED'&&$remit->status==='ACCEPTED' ? 'white' : '#8aa89a' }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;border-color:{{ $remit->status===$s ? '#d4a42a' : 'inherit' }}">{{ $loop->iteration }}</div>
                                    <div style="font-size:10px;color:#8aa89a;text-align:center">{{ ucfirst(strtolower($s)) }}</div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                        <td style="padding:10px 13px;text-align:right">
                            <div style="display:flex;align-items:center;gap:4px;justify-content:flex-end">
                                <a href="{{ route('org.remittances.show', $remit) }}" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:6px;font-size:12px;text-decoration:none;border:1.5px solid #dde8e1;color:#4a6356">View</a>
                                @if(auth()->user()->canReviewRemittances() && $remit->status === 'PENDING')
                                <form method="POST" action="{{ route('org.remittances.verify', $remit) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" style="padding:5px 10px;border-radius:6px;font-size:12px;border:1.5px solid #2563eb;color:#2563eb;background:white;cursor:pointer">Verify</button>
                                </form>
                                @elseif(auth()->user()->canReviewRemittances() && $remit->status === 'VERIFIED')
                                <form method="POST" action="{{ route('org.remittances.accept', $remit) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" style="padding:5px 10px;border-radius:6px;font-size:12px;border:1.5px solid #1a7a41;color:#1a7a41;background:white;cursor:pointer">Accept</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:44px 24px;text-align:center;color:#8aa89a">
                            <div style="font-size:14px;font-weight:600;color:#4a6356;margin-bottom:4px">No remittances created yet</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #eaf0ec;display:flex;justify-content:flex-end;background:#f8fbf9">
            {{ $remittances->links() }}
        </div>
    </div>
</div>
@endsection
