@props(['label','value','icon','tone'=>'blue','suffix'=>null])
<div class="stat"><div class="stat-top"><span>{{ $label }}</span><span class="stat-icon {{ $tone }}"><i data-lucide="{{ $icon }}"></i></span></div><div class="stat-number">{{ $value }}@if($suffix)<small>{{ $suffix }}</small>@endif</div></div>
