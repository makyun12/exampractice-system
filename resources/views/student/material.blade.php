@extends('layouts.app')
@section('title',__('ui.materials'))
@section('content')
<a class="back-link" href="{{ route('materials') }}"><i data-lucide="arrow-left"></i>{{ __('ui.materials') }}</a>
<article class="reader"><span class="eyebrow">{{ $material->module->program->title }} / {{ $material->module->title }}</span><h1>{{ $material->title }}</h1><div class="meta"><i data-lucide="clock-3"></i>{{ $material->reading_minutes }} {{ __('ui.min') }} @if($completed)<x-status status="completed"/>@endif</div><div class="prose-content">{!! \Illuminate\Support\Str::markdown($material->content, ['html_input'=>'strip','allow_unsafe_links'=>false]) !!}</div><div class="reader-footer">@if(!$completed)<form action="{{ route('materials.complete',$material) }}" method="POST">@csrf<button class="button primary"><i data-lucide="check"></i>{{ __('ui.mark_complete') }}</button></form>@else<x-status status="completed"/>@endif<a class="text-link" href="{{ route('programs.show',$material->module->program) }}">{{ __('ui.continue_learning') }}<i data-lucide="arrow-right"></i></a></div></article>
@endsection
