{{-- Breadcrumb component --}}
{{-- Usage: @include('partials.breadcrumb', ['crumbs' => [['label'=>'Colleges','url'=>route(...)], ['label'=>'Edit']]]) --}}
<div style="display:flex;align-items:center;gap:5px;font-size:12px;color:#8aa89a;margin-bottom:16px">
    @foreach($crumbs ?? [] as $i => $crumb)
        @if($i > 0)
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity:.5">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        @endif
        @if(isset($crumb['url']) && $i < count($crumbs ?? []) - 1)
            <a href="{{ $crumb['url'] }}" style="color:#8aa89a;text-decoration:none;transition:color .15s" onmouseover="this.style.color='#1a7a41'" onmouseout="this.style.color='#8aa89a'">
                {{ $crumb['label'] }}
            </a>
        @else
            <span style="color:#0f1f17;font-weight:600">{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</div>
