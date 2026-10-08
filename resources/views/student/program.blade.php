@extends('layouts.app')
@section('title',__('ui.programs'))
@section('content')
<a class="back-link" href="{{ route('programs') }}"><i data-lucide="arrow-left"></i>{{ __('ui.programs') }}</a>
<div class="program-header"><img src="{{ asset('images/'.$program->cover.'.jpg') }}" alt="{{ $program->title }}"><div><span class="eyebrow">{{ __('ui.program') }}</span><h1>{{ $program->title }}</h1><p>{{ $program->description }}</p><span class="meta"><i data-lucide="layers"></i>{{ $modules->count() }} {{ __('ui.modules') }}</span></div></div>
@foreach($modules as $module)<section class="section module-section"><div class="section-heading"><div><span class="eyebrow">{{ __('ui.module') }} {{ $loop->iteration }}</span><h2>{{ $module->title }}</h2><p class="muted">{{ $module->description }}</p></div></div><div class="dashboard-columns"><div class="item-list">@foreach($packages->where('module_id',$module->id) as $package)<x-package-row :package="$package"/>@endforeach</div><div>@foreach($materials->where('module_id',$module->id) as $material)<a class="material-link" href="{{ route('materials.show',$material) }}"><span class="item-icon green"><i data-lucide="book-open-text"></i></span><div><strong>{{ $material->title }}</strong><small>{{ $material->reading_minutes }} {{ __('ui.min') }}</small></div><i data-lucide="chevron-right"></i></a>@endforeach</div></div></section>@endforeach
@endsection
