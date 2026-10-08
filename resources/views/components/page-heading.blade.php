@props(['title','subtitle'=>null])
<div class="page-heading"><div><h1>{{ $title }}</h1>@if($subtitle)<p>{{ $subtitle }}</p>@endif</div><div class="heading-actions">{{ $slot }}</div></div>
