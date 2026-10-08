@extends('layouts.app')
@section('title',__('ui.materials'))
@section('content')
<x-page-heading :title="__('ui.materials')"/>
<div class="material-grid">@forelse($materials as $material)<article class="material-card"><span class="item-icon green"><i data-lucide="book-open-text"></i></span><span class="eyebrow">{{ $material->module->program->title }}</span><h2>{{ $material->title }}</h2><p class="muted">{{ $material->module->title }}</p><div class="meta"><i data-lucide="clock-3"></i>{{ $material->reading_minutes }} {{ __('ui.min') }} @if($completed->contains($material->id))<x-status status="completed"/>@endif</div><a class="text-link" href="{{ route('materials.show',$material) }}">{{ __('ui.read_material') }}<i data-lucide="arrow-right"></i></a></article>@empty<x-empty :text="__('ui.no_access')"/>@endforelse</div>
@endsection
