@extends('layouts.app')
@section('title',__('ui.programs'))
@section('content')
<x-page-heading :title="__('ui.available_programs')"/>
<div class="program-grid">@forelse($programs as $program)<x-program-card :program="$program" :module-count="$packages->filter(fn($p)=>$p->module->program_id===$program->id)->pluck('module_id')->merge($materials->filter(fn($m)=>$m->module->program_id===$program->id)->pluck('module_id'))->unique()->count()"/>@empty<x-empty :text="__('ui.no_access')"/>@endforelse</div>
@endsection
