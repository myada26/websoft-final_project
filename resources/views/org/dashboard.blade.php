@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div>
    {{-- Page header --}}
    <div style="margin-bottom:18px">
        <h1 style="font-size:19px;font-weight:700;color:#0f1f17">{{ auth()->user()->organization->name ?? 'Organization' }} Dashboard</h1>
        <p style="font-size:12.5px;color:#4a6356;margin-top:2px">{{ $activeSemester?->name ?? 'No active semester' }} · Financial overview</p>
    </div>

    {{-- Stat cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:18px">
        @php
        $stats = [
        ['label'=>'Transactions Today', 'value'=>$todayCount ?? 0, 'sub'=>'Completed receipts', 'icon_class'=>'si-green', 'icon_path'=>'
        <line x1="12" y1="1" x2="12" y2="23" />
        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />'],
        ['label'=>'Total Collected', 'value'=>'₱'.number_format($totalCollected ?? 0, 2), 'sub'=>'This semester', 'icon_class'=>'si-gold', 'icon_path'=>'
        <line x1="12" y1="1" x2="12" y2="23" />
        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />'],
        ['label'=>'Enrolled Students', 'value'=>$enrolledCount ?? 0, 'sub'=>'In active semester', 'icon_class'=>'si-purple', 'icon_path'=>'
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
        <circle cx="9" cy="7" r="4" />'],
        ['label'=>'Pending Voids', 'value'=>$pendingVoidCount ?? 0, 'sub'=>'Awaiting approval', 'icon_class'=>'si-red', 'icon_path'=>'
        <circle cx="12" cy="12" r="10" />
        <line x1="15" y1="9" x2="9" y2="15" />
        <line x1="9" y1="9" x2="15" y2="15" />'],
        ];
        @endphp
        @foreach($stats as $s)
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:18px 20px;box-shadow:0 1px 2px rgba(0,0,0,.06)">
            <div style="float:right;width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;
                @if($s['icon_class']==='si-green') background:#e6f4ec;color:#1a7a41
                @elseif($s['icon_class']==='si-gold') background:#fdf7e3;color:#d4a42a
                @elseif($s['icon_class']==='si-purple') background:#f5f3ff;color:#7c3aed
                @else background:#fef2f2;color:#dc2626 @endif">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $s['icon_path'] !!}</svg>
            </div>
            <div style="font-size:11.5px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;clear:both">{{ $s['label'] }}</div>
            <div style="font-size:24px;font-weight:700;line-height:1;color:#0f1f17">{{ $s['value'] }}</div>
            <div style="font-size:12px;color:#4a6356;margin-top:4px">{{ $s['sub'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Charts row --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px">
        {{-- Monthly bar chart --}}
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:0">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-bottom:1px solid #eaf0ec">
                <div>
                    <div style="font-size:14px;font-weight:700">Monthly Collection</div>
                    <div style="font-size:12px;color:#8aa89a">Last 6 months</div>
                </div>
            </div>
            <div style="padding:20px">
                <canvas id="collectionChart" height="200"></canvas>
            </div>
        </div>
        {{-- Cash vs GCash donut --}}
        <div style="background:white;border-radius:12px;border:1px solid #dde8e1;padding:0">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-bottom:1px solid #eaf0ec">
                <div>
                    <div style="font-size:14px;font-weight:700">Payment Methods</div>
                    <div style="font-size:12px;color:#8aa89a">Cash vs GCash this semester</div>
                </div>
            </div>
            <div style="padding:20px;display:flex;align-items:center;justify-content:center">
                <canvas id="paymentChart" width="200" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Recent transactions --}}
    <div style="background:white;border-radius:12px;border:1px solid #dde8e1">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:13px 20px;border-bottom:1px solid #eaf0ec">
            <div>
                <div style="font-size:14px;font-weight:700">Recent Transactions</div>
                <div style="font-size:12px;color:#8aa89a">Latest 10 receipts</div>
            </div>
            @if(auth()->user()->canCreateTransactions())
            <a href="{{ route('org.transactions.create') }}" class="btn-green" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                New Transaction
            </a>
            @endif
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f0f3f1;border-bottom:1px solid #dde8e1">
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">OR No.</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Student</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Type</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Amount</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Method</th>
                        <th style="padding:9px 13px;text-align:left;font-size:11px;font-weight:700;color:#8aa89a;text-transform:uppercase;letter-spacing:.05em">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions ?? [] as $tx)
                    <tr style="border-bottom:1px solid #eaf0ec" onmouseover="this.style.background='#f8fbf9'" onmouseout="this.style.background=''">
                        <td style="padding:10px 13px"><span style="font-family:monospace;font-size:12px;color:#1a7a41;font-weight:700;background:#e6f4ec;padding:2px 6px;border-radius:4px">{{ $tx->or_number }}</span></td>
                        <td style="padding:10px 13px;font-size:13px;font-weight:600">{{ $tx->student?->full_name }}</td>
                        <td style="padding:10px 13px">
                            <span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:600;{{ $tx->transaction_type === 'FEE' ? 'background:#dbeafe;color:#1d4ed8' : 'background:#fce7f3;color:#be185d' }}">{{ $tx->transaction_type }}</span>
                        </td>
                        <td style="padding:10px 13px;font-size:13px;font-weight:600;color:#0f1f17">₱{{ number_format($tx->amount, 2) }}</td>
                        <td style="padding:10px 13px">
                            <span style="display:inline-block;padding:2px 8px;border-radius:20px;font-size:11.5px;font-weight:600;{{ $tx->payment_method === 'GCASH' ? 'background:#eff6ff;color:#2563eb' : 'background:#f3f4f6;color:#374151' }}">{{ $tx->payment_method }}</span>
                        </td>
                        <td style="padding:10px 13px;font-size:12.5px;color:#8aa89a">{{ $tx->created_at->format('M d, H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding:30px;text-align:center;font-size:13px;color:#8aa89a">No transactions yet this semester.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Monthly collection bar chart
    new Chart(document.getElementById('collectionChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!},
            datasets: [{
                label: 'Collected (₱)',
                data: {!! json_encode($chartData ?? [0, 0, 0, 0, 0, 0]) !!},
                backgroundColor: '#27a05a',
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    // Payment method donut
    new Chart(document.getElementById('paymentChart'), {
        type: 'doughnut',
        data: {
            labels: ['Cash', 'GCash'],
            datasets: [{
                data: {!! json_encode([$cashAmount ?? 0, $gcashAmount ?? 0]) !!},
                backgroundColor: ['#27a05a', '#2563eb'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush
