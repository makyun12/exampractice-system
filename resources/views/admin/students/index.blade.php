@extends('layouts.app')
@section('title',__('ui.students'))
@section('content')
<x-page-heading :title="__('ui.students')"><a class="button primary" href="{{ route('admin.students.create') }}"><i data-lucide="user-plus"></i>{{ __('ui.add_student') }}</a></x-page-heading>
<form class="filter-bar"><div class="search-field"><i data-lucide="search"></i><input name="q" value="{{ request('q') }}" placeholder="{{ __('ui.search_placeholder') }}" aria-label="{{ __('ui.search') }}"></div><button class="button secondary">{{ __('ui.search') }}</button></form>
<div class="table-wrap"><table><thead><tr><th>{{ __('ui.student') }}</th><th>{{ __('ui.username') }}</th><th>{{ __('ui.status') }}</th><th>{{ __('ui.device') }}</th><th>{{ __('ui.practice') }}</th><th>{{ __('ui.actions') }}</th></tr></thead><tbody>@forelse($students as $student)<tr><td><strong>{{ $student->name }}</strong></td><td>{{ $student->username }}</td><td><x-status :status="$student->active?'active':'inactive'"/></td><td>{{ __('ui.'.($student->device_lock?'locked':'unrestricted')) }}</td><td>{{ $student->attempts_count }}</td><td><a class="button secondary compact" href="{{ route('admin.students.edit',$student) }}"><i data-lucide="pencil"></i>{{ __('ui.edit') }}</a></td></tr>@empty<tr><td colspan="6"><x-empty/></td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $students->links() }}</div>
@endsection
